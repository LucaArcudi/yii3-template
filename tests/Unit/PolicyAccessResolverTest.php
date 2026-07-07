<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Services\PolicyAccessResolver;
use App\Tests\Unit\Support\AllowAccessPolicy;
use App\Tests\Unit\Support\ArrayContainer;
use App\Tests\Unit\Support\DenyAccessPolicy;
use Codeception\Test\Unit;
use stdClass;

final class PolicyAccessResolverTest extends Unit
{
    public function testEmptyPolicyClassAllowsAccess(): void
    {
        $resolver = new PolicyAccessResolver(new ArrayContainer([]));

        self::assertTrue($resolver->canAccess(null));
        self::assertTrue($resolver->canAccess(''));
    }

    public function testPolicyCanAllowOrDenyAccess(): void
    {
        $resolver = new PolicyAccessResolver(new ArrayContainer([
            AllowAccessPolicy::class => new AllowAccessPolicy(),
            DenyAccessPolicy::class => new DenyAccessPolicy(),
        ]));

        self::assertTrue($resolver->canAccess(AllowAccessPolicy::class));
        self::assertFalse($resolver->canAccess(DenyAccessPolicy::class));
    }

    public function testInvalidOrMissingPolicyClassDeniesAccess(): void
    {
        $resolver = new PolicyAccessResolver(new ArrayContainer([]));

        self::assertFalse($resolver->canAccess(stdClass::class));
        self::assertFalse($resolver->canAccess(AllowAccessPolicy::class));
    }
}
