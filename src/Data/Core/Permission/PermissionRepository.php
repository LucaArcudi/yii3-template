<?php

declare(strict_types=1);

namespace App\Data\Core\Permission;

use App\Data\Core\Log\EntityLogRepository;
use App\Helpers\Translate;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

final readonly class PermissionRepository
{
    public function __construct(
        private ConnectionInterface $db,
        private EntityLogRepository $entityLogRepository,
    ) {}

    public function exists(int $id): bool
    {
        return (new Query($this->db))
            ->from('{{%core_permission}}')
            ->where(['id' => $id])
            ->exists();
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = (new Query($this->db))
            ->from('{{%core_permission}}')
            ->where(['code' => $code]);

        if ($excludeId !== null) {
            $query = $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists();
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = (new Query($this->db))
            ->from('{{%core_permission}}')
            ->where(['name' => trim($name)]);

        if ($excludeId !== null) {
            $query = $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists();
    }

    public function isAssigned(int $id): bool
    {
        return (new Query($this->db))
            ->from('{{%core_role_permission}}')
            ->where(['permission_id' => $id])
            ->exists();
    }

    public function findById(int $id): ?PermissionEntity
    {
        $row = (new Query($this->db))
            ->select([
                'p.*',
                'group_name' => 'pg.name',
                'group_code' => 'pg.code',
            ])
            ->from(['p' => '{{%core_permission}}'])
            ->leftJoin(['pg' => '{{%core_permission_group}}'], 'pg.id = p.group_id')
            ->where(['p.id' => $id])
            ->one();

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    /**
     * @return PermissionEntity[]
     */
    public function findAll(): array
    {
        $rows = (new Query($this->db))
            ->select([
                'p.*',
                'group_name' => 'pg.name',
                'group_code' => 'pg.code',
            ])
            ->from(['p' => '{{%core_permission}}'])
            ->leftJoin(['pg' => '{{%core_permission_group}}'], 'pg.id = p.group_id')
            ->orderBy(['p.id' => SORT_DESC])
            ->all();

        return array_map(fn(array $row) => $this->mapRow($row), $rows);
    }

    /**
     * @return int[]
     */
    public function findExistingIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $existingIds = (new Query($this->db))
            ->select(['id'])
            ->from('{{%core_permission}}')
            ->where(['id' => $ids])
            ->column();

        return array_map(static fn(mixed $id): int => (int) $id, $existingIds);
    }

    public function findGroupedForRoleAssignment(): array
    {
        $rows = (new Query($this->db))
            ->select([
                'p.id',
                'p.group_id',
                'p.name',
                'p.code',
                'p.weight',
                'group_name' => 'pg.name',
                'group_code' => 'pg.code',
            ])
            ->from(['p' => '{{%core_permission}}'])
            ->leftJoin(['pg' => '{{%core_permission_group}}'], 'pg.id = p.group_id')
            ->orderBy([
                'pg.name' => SORT_ASC,
                'p.name' => SORT_ASC,
                'p.code' => SORT_ASC,
                'p.id' => SORT_ASC,
            ])
            ->all();

        return $this->groupRows($rows);
    }

    public function findSelectableOptions(): array
    {
        $rows = (new Query($this->db))
            ->select([
                'p.id',
                'p.name',
                'p.code',
                'p.weight',
                'group_name' => 'pg.name',
            ])
            ->from(['p' => '{{%core_permission}}'])
            ->leftJoin(['pg' => '{{%core_permission_group}}'], 'pg.id = p.group_id')
            ->orderBy([
                'pg.name' => SORT_ASC,
                'p.name' => SORT_ASC,
                'p.code' => SORT_ASC,
                'p.id' => SORT_ASC,
            ])
            ->all();

        $options = [];

        foreach ($rows as $row) {
            $options[(int) $row['id']] = sprintf(
                '%s%s (%s)',
                ($row['group_name'] ?? null) !== null ? (string) $row['group_name'] . ' - ' : '',
                (string) $row['name'],
                (string) $row['code'],
            );
        }

        return $options;
    }

    public function findSelectableOptionsByGroupCode(string $groupCode): array
    {
        $rows = (new Query($this->db))
            ->select([
                'p.id',
                'p.name',
                'p.code',
                'p.weight',
            ])
            ->from(['p' => '{{%core_permission}}'])
            ->innerJoin(['pg' => '{{%core_permission_group}}'], 'pg.id = p.group_id')
            ->where(['pg.code' => $groupCode])
            ->orderBy([
                'p.weight' => SORT_DESC,
                'p.name' => SORT_ASC,
                'p.code' => SORT_ASC,
                'p.id' => SORT_ASC,
            ])
            ->all();

        $options = [];

        foreach ($rows as $row) {
            $options[(int) $row['id']] = sprintf(
                '%s (%s, peso %d)',
                (string) $row['name'],
                (string) $row['code'],
                (int) ($row['weight'] ?? 1),
            );
        }

        return $options;
    }

    public function findSelectableComponentOptions(): array
    {
        return $this->optionRows(
            (new Query($this->db))
                ->select([
                    'p.id',
                    'p.name',
                    'p.code',
                    'p.weight',
                ])
                ->from(['p' => '{{%core_permission}}'])
                ->orderBy([
                    'p.weight' => SORT_DESC,
                    'p.name' => SORT_ASC,
                    'p.code' => SORT_ASC,
                    'p.id' => SORT_ASC,
                ])
                ->all(),
        );
    }

    public function existsInGroupCode(int $id, string $groupCode): bool
    {
        return (new Query($this->db))
            ->from(['p' => '{{%core_permission}}'])
            ->innerJoin(['pg' => '{{%core_permission_group}}'], 'pg.id = p.group_id')
            ->where([
                'p.id' => $id,
                'pg.code' => $groupCode,
            ])
            ->exists();
    }

    public function findGroupedByRoleId(int $roleId): array
    {
        $rows = (new Query($this->db))
            ->select([
                'p.id',
                'p.group_id',
                'p.name',
                'p.code',
                'p.weight',
                'group_name' => 'pg.name',
                'group_code' => 'pg.code',
            ])
            ->from(['p' => '{{%core_permission}}'])
            ->innerJoin(['rp' => '{{%core_role_permission}}'], 'rp.permission_id = p.id')
            ->leftJoin(['pg' => '{{%core_permission_group}}'], 'pg.id = p.group_id')
            ->where(['rp.role_id' => $roleId])
            ->orderBy([
                'pg.name' => SORT_ASC,
                'p.name' => SORT_ASC,
                'p.code' => SORT_ASC,
                'p.id' => SORT_ASC,
            ])
            ->all();

        return $this->groupRows($rows);
    }

    public function create(PermissionEntity $permission): int
    {
        $permission->stampCreated($permission->createdBy ?? $permission->updatedBy);
        $data = [
            'group_id' => $permission->groupId,
            'name' => $permission->name,
            'code' => $permission->code,
            'weight' => $permission->weight,
            'created_at' => $permission->createdAt,
            'updated_at' => $permission->updatedAt,
            'created_by' => $permission->createdBy,
            'updated_by' => $permission->updatedBy,
        ];

        $this->db->createCommand()->insert('{{%core_permission}}', $data)->execute();

        $id = (int) $this->db->getLastInsertID();
        $permission->id = $id;

        $this->entityLogRepository->record(
            'permission',
            $id,
            EntityLogRepository::ACTION_CREATE,
            $permission,
            'INSERT INTO core_permission (group_id, name, code, weight, created_at, updated_at, created_by, updated_by) VALUES (:group_id, :name, :code, :weight, :created_at, :updated_at, :created_by, :updated_by)',
            $data,
        );

        return $id;
    }

    public function update(PermissionEntity $permission): void
    {
        $permission->stampUpdated($permission->updatedBy);
        $data = [
            'group_id' => $permission->groupId,
            'name' => $permission->name,
            'code' => $permission->code,
            'weight' => $permission->weight,
            'updated_at' => $permission->updatedAt,
            'updated_by' => $permission->updatedBy,
        ];

        $this->db->createCommand()->update('{{%core_permission}}', $data, [
            'id' => $permission->id,
        ])->execute();

        $this->entityLogRepository->record(
            'permission',
            $permission->id,
            EntityLogRepository::ACTION_UPDATE,
            $permission,
            'UPDATE core_permission SET group_id = :group_id, name = :name, code = :code, weight = :weight, updated_at = :updated_at, updated_by = :updated_by WHERE id = :id',
            [...$data, 'id' => $permission->id],
        );
    }

    public function delete(int $id, ?int $actorId = null): void
    {
        $permission = $this->findById($id);

        $this->db->createCommand()
            ->delete('{{%core_permission}}', ['id' => $id])
            ->execute();

        if ($permission !== null) {
            $this->entityLogRepository->record(
                'permission',
                $id,
                EntityLogRepository::ACTION_DELETE,
                $permission,
                'DELETE FROM core_permission WHERE id = :id',
                ['id' => $id],
                actorId: $actorId,
            );
        }
    }

    private function groupRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $groups = [];

        foreach ($rows as $row) {
            $permissionId = (int) $row['id'];
            $groupKey = $this->resolveGroupKey($row);

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'key' => $groupKey,
                    'label' => $this->resolveGroupLabel($row, $groupKey),
                    'items' => [],
                ];
            }

            $groups[$groupKey]['items'][] = [
                'id' => $permissionId,
                'name' => (string) $row['name'],
                'code' => (string) $row['code'],
                'weight' => (int) ($row['weight'] ?? 1),
            ];
        }

        usort(
            $groups,
            static fn(array $left, array $right): int => (string) ($left['label'] ?? '')
                <=> (string) ($right['label'] ?? ''),
        );

        return array_values($groups);
    }

    private function resolveGroupKey(array $row): string
    {
        if (($row['group_id'] ?? null) !== null) {
            return 'group:' . (int) $row['group_id'];
        }

        $groupCode = trim((string) ($row['group_code'] ?? ''));

        if ($groupCode !== '') {
            return strtolower($groupCode);
        }

        $groupName = trim((string) ($row['group_name'] ?? ''));

        if ($groupName !== '') {
            return strtolower($groupName);
        }

        $name = trim((string) ($row['name'] ?? ''));

        if ($name !== '' && str_contains($name, '.')) {
            $prefix = explode('.', $name, 2)[0];

            if ($prefix !== '') {
                return strtolower($prefix);
            }
        }

        $code = trim((string) ($row['code'] ?? ''));

        if ($code !== '' && str_contains($code, '_')) {
            $prefix = explode('_', $code, 2)[0];

            if ($prefix !== '') {
                return strtolower($prefix);
            }
        }

        return 'generale';
    }

    private function resolveGroupLabel(array $row, string $groupKey): string
    {
        $groupName = trim((string) ($row['group_name'] ?? ''));

        if ($groupName !== '') {
            return $groupName;
        }

        $normalized = str_replace(['_', '-', '.'], ' ', strtolower(trim($groupKey)));
        $normalized = str_replace('group:', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim((string) $normalized);

        return $normalized !== '' ? ucwords($normalized) : Translate::t('Generale');
    }

    private function optionRows(array $rows): array
    {
        $options = [];

        foreach ($rows as $row) {
            $options[(int) $row['id']] = sprintf(
                '%s (%s, peso %d)',
                (string) $row['name'],
                (string) $row['code'],
                (int) ($row['weight'] ?? 1),
            );
        }

        return $options;
    }

    private function mapRow(array $row): PermissionEntity
    {
        return new PermissionEntity(
            id: (int) $row['id'],
            groupId: ($row['group_id'] ?? null) !== null ? (int) $row['group_id'] : null,
            name: (string) $row['name'],
            code: (string) $row['code'],
            weight: (int) ($row['weight'] ?? 1),
            groupName: ($row['group_name'] ?? null) !== null ? (string) $row['group_name'] : null,
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
            createdBy: ($row['created_by'] ?? null) !== null ? (int) $row['created_by'] : null,
            updatedBy: ($row['updated_by'] ?? null) !== null ? (int) $row['updated_by'] : null,
        );
    }
}
