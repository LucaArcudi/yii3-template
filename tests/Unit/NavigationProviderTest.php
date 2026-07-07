<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Core\PermissionGroup\PermissionGroupPolicy;
use App\Core\Permission\PermissionPolicy;
use App\Core\Role\RolePolicy;
use App\Core\User\UserPolicy;
use App\Mes\Task\TaskPolicy;
use App\Shared\Navigation\NavigationProvider;
use App\Shared\Services\PolicyAccessResolver;
use App\Tests\Unit\Support\AllowAccessPolicy;
use App\Tests\Unit\Support\ArrayContainer;
use App\Tests\Unit\Support\DenyAccessPolicy;
use Codeception\Test\Unit;

final class NavigationProviderTest extends Unit
{
    public function testMenuItemsAreFilteredByPolicyCanAccess(): void
    {
        $provider = new NavigationProvider(new PolicyAccessResolver(new ArrayContainer([
            TaskPolicy::class => new DenyAccessPolicy(),
            UserPolicy::class => new DenyAccessPolicy(),
            RolePolicy::class => new DenyAccessPolicy(),
            PermissionPolicy::class => new DenyAccessPolicy(),
            PermissionGroupPolicy::class => new AllowAccessPolicy(),
        ])));

        self::assertSame(['/', '/permission-group'], self::urls($provider->getVisibleTree()));
    }

    /**
     * @param array<array-key, mixed> $items
     * @return list<string>
     */
    private static function urls(array $items): array
    {
        $urls = [];

        foreach ($items as $item) {
            $url = trim((string) ($item['url'] ?? ''));

            if ($url !== '') {
                $urls[] = $url;
            }

            foreach (self::urls($item['_children'] ?? []) as $childUrl) {
                $urls[] = $childUrl;
            }
        }

        return $urls;
    }
}
