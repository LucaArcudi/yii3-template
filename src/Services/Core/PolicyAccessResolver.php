<?php

declare(strict_types=1);

namespace App\Services\Core;

use App\Data\AccessPolicyInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function is_a;
use function trim;

final readonly class PolicyAccessResolver
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function canAccess(?string $policyClass): bool
    {
        $policyClass = trim((string) $policyClass);

        if ($policyClass === '') {
            return true;
        }

        if (!is_a($policyClass, AccessPolicyInterface::class, true)) {
            return false;
        }

        try {
            $policy = $this->container->get($policyClass);
        } catch (ContainerExceptionInterface | NotFoundExceptionInterface) {
            return false;
        }

        return $policy instanceof AccessPolicyInterface && $policy->canAccess();
    }
}
