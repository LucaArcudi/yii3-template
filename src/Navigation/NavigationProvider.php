<?php

declare(strict_types=1);

namespace App\Navigation;

use App\Services\Core\AuthorizationService;
use Yiisoft\User\CurrentUser;

final readonly class NavigationProvider
{
    public function __construct(
        private CurrentUser $currentUser,
        private AuthorizationService $authorizationService,
    ) {}

    public function getVisibleTree(): array
    {
        return NavigationTreeVisibility::filter(self::tree(), fn(array $item): bool => $this->hasPermission($item));
    }

    private function hasPermission(array $item): bool
    {
        $permissionGroupCode = trim((string) ($item['permission_group_code'] ?? ''));
        $permissionCode = trim((string) ($item['permission_code'] ?? ''));

        if ($permissionCode === '') {
            return true;
        }

        if ($permissionGroupCode === '' || $this->currentUser->isGuest()) {
            return false;
        }

        return $this->authorizationService->userHasPermission(
            $this->currentUser->getId(),
            $permissionGroupCode,
            $permissionCode,
        );
    }

    private static function tree(): array
    {
        return [
            [
                'name' => 'Home',
                'header' => true,
                '_children' => [
                    [
                        'name' => 'Homepage',
                        'icon' => 'pe-7s-rocket',
                        'url' => '/',
                        '_children' => [],
                    ],
                ],
            ],
            [
                'name' => 'Gestione dati',
                'header' => true,
                '_children' => [
                    [
                        'name' => 'Task',
                        'icon' => 'pe-7s-check',
                        'url' => '/task',
                        'permission_group_code' => 'TASK',
                        'permission_code' => 'TASK_ACCESS',
                        '_children' => [],
                    ],
                ],
            ],
            [
                'name' => 'Sistema',
                'header' => true,
                '_children' => [
                    [
                        'name' => 'Utenti',
                        'icon' => 'pe-7s-users',
                        'url' => '/user',
                        'permission_group_code' => 'USER',
                        'permission_code' => 'USER_ACCESS',
                        '_children' => [],
                    ],
                    [
                        'name' => 'Ruoli',
                        'icon' => 'pe-7s-shield',
                        'url' => '/role',
                        'permission_group_code' => 'ROLE',
                        'permission_code' => 'ROLE_ACCESS',
                        '_children' => [],
                    ],
                    [
                        'name' => 'Permessi',
                        'icon' => 'pe-7s-key',
                        'toggle' => true,
                        'permission_group_code' => 'PERMISSION',
                        'permission_code' => 'PERMISSION_ACCESS',
                        '_children' => [
                            [
                                'name' => 'Permessi',
                                'icon' => 'pe-7s-note2',
                                'url' => '/permission',
                                'permission_group_code' => 'PERMISSION',
                                'permission_code' => 'PERMISSION_ACCESS',
                                '_children' => [],
                            ],
                            [
                                'name' => 'Gruppi',
                                'icon' => 'pe-7s-network',
                                'url' => '/permission-group',
                                'permission_group_code' => 'PERMISSION_GROUP',
                                'permission_code' => 'PERMISSION_GROUP_ACCESS',
                                '_children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
