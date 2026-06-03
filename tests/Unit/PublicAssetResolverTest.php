<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Helpers\PublicAssetResolver;
use Codeception\Test\Unit;

final class PublicAssetResolverTest extends Unit
{
    public function testResolvesExistingPublicAsset(): void
    {
        self::assertSame('/favicon.ico', PublicAssetResolver::url('favicon.ico'));
    }

    public function testRejectsUnsafeOrMissingRelativeAsset(): void
    {
        self::assertNull(PublicAssetResolver::url('../config/common/params.php'));
        self::assertNull(PublicAssetResolver::url('images/missing-logo.png'));
    }

    public function testAllowsAbsoluteUrl(): void
    {
        self::assertSame('https://example.test/logo.png', PublicAssetResolver::url('https://example.test/logo.png'));
    }
}
