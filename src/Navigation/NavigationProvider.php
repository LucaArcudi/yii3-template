<?php

declare(strict_types=1);

namespace App\Navigation;

use App\Core\PermissionGroup\PermissionGroupPolicy;
use App\Core\Permission\PermissionPolicy;
use App\Core\Role\RolePolicy;
use App\Core\User\UserPolicy;
use App\Mes\Task\TaskPolicy;
use App\Helpers\Translate;
use App\Services\Core\PolicyAccessResolver;

use function is_string;

final readonly class NavigationProvider
{
    public function __construct(
        private PolicyAccessResolver $policyAccess,
    ) {}

    public function getVisibleTree(): array
    {
        return NavigationTreeVisibility::filter(self::tree(), fn(array $item): bool => $this->hasAccess($item));
    }

    private function hasAccess(array $item): bool
    {
        $policyClass = $item['policyClass'] ?? null;

        return $this->policyAccess->canAccess(is_string($policyClass) ? $policyClass : null);
    }

    private static function tree(): array
    {
        return [
            [
                'name' => Translate::t('Home'),
                'header' => true,
                '_children' => [
                    [
                        'name' => Translate::t('Homepage'),
                        'icon' => 'pe-7s-rocket',
                        'url' => '/',
                        '_children' => [],
                    ],
                ],
            ],
            [
                'name' => Translate::t('Gestione dati'),
                'header' => true,
                '_children' => [
                    [
                        'name' => Translate::t('Task'),
                        'icon' => 'pe-7s-check',
                        'url' => '/task',
                        'policyClass' => TaskPolicy::class,
                        '_children' => [],
                    ],
                ],
            ],
            [
                'name' => Translate::t('Sistema'),
                'header' => true,
                '_children' => [
                    [
                        'name' => Translate::t('Utenti'),
                        'icon' => 'pe-7s-users',
                        'url' => '/user',
                        'policyClass' => UserPolicy::class,
                        '_children' => [],
                    ],
                    [
                        'name' => Translate::t('Ruoli'),
                        'icon' => 'pe-7s-shield',
                        'url' => '/role',
                        'policyClass' => RolePolicy::class,
                        '_children' => [],
                    ],
                    [
                        'name' => Translate::t('Permessi'),
                        'icon' => 'pe-7s-key',
                        'toggle' => true,
                        '_children' => [
                            [
                                'name' => Translate::t('Permessi'),
                                'icon' => 'pe-7s-note2',
                                'url' => '/permission',
                                'policyClass' => PermissionPolicy::class,
                                '_children' => [],
                            ],
                            [
                                'name' => Translate::t('Gruppi'),
                                'icon' => 'pe-7s-network',
                                'url' => '/permission-group',
                                'policyClass' => PermissionGroupPolicy::class,
                                '_children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
