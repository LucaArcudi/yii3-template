<?php

declare(strict_types=1);

namespace App\Services\Core;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

use function array_map;
use function in_array;
use function strtoupper;

final readonly class AuthorizationService
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function userHasPermission(int|string $userId, string $group, string $permission): bool
    {
        $row = (new Query($this->db))
            ->from(['ur' => '{{%core_user_role}}'])
            ->innerJoin(['rp' => '{{%core_role_permission}}'], 'rp.role_id = ur.role_id')
            ->innerJoin(['p' => '{{%core_permission}}'], 'p.id = rp.permission_id')
            ->innerJoin(['pg' => '{{%core_permission_group}}'], 'pg.id = p.group_id')
            ->where([
                'ur.user_id' => $userId,
                'pg.code' => $group,
                'p.code' => $permission,
            ])
            ->limit(1)
            ->exists();

        return $row;
    }

    public function userHasAnyPermissionInGroup(int|string $userId, string $group): bool
    {
        return (new Query($this->db))
            ->from(['ur' => '{{%core_user_role}}'])
            ->innerJoin(['rp' => '{{%core_role_permission}}'], 'rp.role_id = ur.role_id')
            ->innerJoin(['p' => '{{%core_permission}}'], 'p.id = rp.permission_id')
            ->innerJoin(['pg' => '{{%core_permission_group}}'], 'pg.id = p.group_id')
            ->where([
                'ur.user_id' => $userId,
                'pg.code' => $group,
            ])
            ->limit(1)
            ->exists();
    }

    /**
     * @return list<string>
     */
    public function userRoleCodes(int|string $userId): array
    {
        $codes = (new Query($this->db))
            ->select(['r.code'])
            ->from(['r' => '{{%core_role}}'])
            ->innerJoin(['ur' => '{{%core_user_role}}'], 'ur.role_id = r.id')
            ->where(['ur.user_id' => $userId])
            ->orderBy([
                'r.code' => SORT_ASC,
                'r.id' => SORT_ASC,
            ])
            ->column();

        return array_map(static fn(mixed $code): string => strtoupper((string) $code), $codes);
    }

    public function userHasRole(int|string $userId, string $roleCode): bool
    {
        return in_array(strtoupper($roleCode), $this->userRoleCodes($userId), true);
    }

    /**
     * @param list<string> $roleCodes
     */
    public function userHasAnyRole(int|string $userId, array $roleCodes): bool
    {
        foreach ($roleCodes as $roleCode) {
            if ($this->userHasRole($userId, $roleCode)) {
                return true;
            }
        }

        return false;
    }
}
