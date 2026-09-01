<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Checker;

use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class ExpressionLanguageChecker
{
    public function __construct(
        private readonly ?ExpressionLanguage $expressionLanguage = null,
        private readonly ?AuthenticationTrustResolverInterface $authenticationTrustResolver = null,
        private readonly ?RoleHierarchyInterface $roleHierarchy = null,
        private readonly ?TokenStorageInterface $tokenStorage = null,
        private readonly ?AuthorizationCheckerInterface $authorizationChecker = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function isGranted(string $expression): bool
    {
        if (null === $this->expressionLanguage) {
            throw new \LogicException('The "symfony/expression-language" library must be installed to use the "security" attribute.');
        }

        if (null === $this->tokenStorage || null === $this->authenticationTrustResolver) {
            throw new \LogicException('The "symfony/security" library must be installed to use the "security" attribute.');
        }

        if (null === $token = $this->tokenStorage->getToken()) {
            $token = new NullToken();
        }

        $variables = [
            'trust_resolver' => $this->authenticationTrustResolver,
            'auth_checker' => $this->authorizationChecker, // needed for the is_granted expression function
            'token' => $token,
            'user' => $token->getUser(),
            'roles' => $this->getEffectiveRoles($token),
        ];

        $this->logger?->info(
            'Evaluate expression Language',
            ['expression' => $expression, 'variable' => $variables]
        );

        return (bool) $this->expressionLanguage->evaluate($expression, $variables);
    }

    /**
     * @return string[]
     */
    private function getEffectiveRoles(TokenInterface $token): array
    {
        if (null === $this->roleHierarchy) {
            return $token->getRoleNames();
        }

        return $this->roleHierarchy->getReachableRoleNames($token->getRoleNames());
    }
}
