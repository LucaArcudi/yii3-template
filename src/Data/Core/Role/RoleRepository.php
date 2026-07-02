<?php

declare(strict_types=1);

namespace App\Data\Core\Role;

use App\Data\Core\Log\EntityLogRepository;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

final readonly class RoleRepository
{
    public function __construct(
        private ConnectionInterface $db,
        private EntityLogRepository $entityLogRepository,
    ) {}

    public function exists(int $id): bool
    {
        return (new Query($this->db))
            ->from('{{%core_role}}')
            ->where(['id' => $id])
            ->exists();
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = (new Query($this->db))
            ->from('{{%core_role}}')
            ->where(['code' => $code]);

        if ($excludeId !== null) {
            $query = $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists();
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = (new Query($this->db))
            ->from('{{%core_role}}')
            ->where(['name' => trim($name)]);

        if ($excludeId !== null) {
            $query = $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists();
    }

    public function isAssignedToUsers(int $id): bool
    {
        return (new Query($this->db))
            ->from('{{%core_user_role}}')
            ->where(['role_id' => $id])
            ->exists();
    }

    public function findById(int $id): ?RoleEntity
    {
        $row = (new Query($this->db))
            ->from('{{%core_role}}')
            ->where(['id' => $id])
            ->one();

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function findIdByCode(string $code): ?int
    {
        $id = (new Query($this->db))
            ->select(['id'])
            ->from('{{%core_role}}')
            ->where(['code' => $code])
            ->scalar();

        return $id === null || $id === false ? null : (int) $id;
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
            ->from('{{%core_role}}')
            ->where(['id' => $ids])
            ->column();

        return array_map(static fn(mixed $id): int => (int) $id, $existingIds);
    }

    public function findSelectableOptions(): array
    {
        $rows = (new Query($this->db))
            ->select(['id', 'name', 'code'])
            ->from('{{%core_role}}')
            ->orderBy([
                'name' => SORT_ASC,
                'code' => SORT_ASC,
                'id' => SORT_ASC,
            ])
            ->all();

        $options = [];

        foreach ($rows as $row) {
            $options[(int) $row['id']] = sprintf(
                '%s (%s)',
                (string) $row['name'],
                (string) $row['code'],
            );
        }

        return $options;
    }

    public function findByUserId(int $userId): array
    {
        return (new Query($this->db))
            ->select(['r.id', 'r.name', 'r.code'])
            ->from(['r' => '{{%core_role}}'])
            ->innerJoin(['ur' => '{{%core_user_role}}'], 'ur.role_id = r.id')
            ->where(['ur.user_id' => $userId])
            ->orderBy([
                'r.name' => SORT_ASC,
                'r.code' => SORT_ASC,
                'r.id' => SORT_ASC,
            ])
            ->all();
    }

    /**
     * @return int[]
     */
    public function getPermissionIds(int $roleId): array
    {
        $ids = (new Query($this->db))
            ->select(['permission_id'])
            ->from('{{%core_role_permission}}')
            ->where(['role_id' => $roleId])
            ->orderBy(['permission_id' => SORT_ASC])
            ->column();

        return array_map(static fn(mixed $id): int => (int) $id, $ids);
    }

    public function createWithPermissions(RoleEntity $role, array $permissionIds): int
    {
        $transaction = $this->db->beginTransaction();

        try {
            $role->stampCreated($role->createdBy ?? $role->updatedBy);
            $data = [
                'name' => $role->name,
                'code' => $role->code,
                'created_at' => $role->createdAt,
                'updated_at' => $role->updatedAt,
                'created_by' => $role->createdBy,
                'updated_by' => $role->updatedBy,
            ];

            $this->db->createCommand()->insert('{{%core_role}}', $data)->execute();

            $roleId = (int) $this->db->getLastInsertID();
            $role->id = $roleId;
            $this->replacePermissions($roleId, $permissionIds);

            $this->entityLogRepository->record(
                'role',
                $roleId,
                EntityLogRepository::ACTION_CREATE,
                $role,
                'INSERT INTO core_role (name, code, created_at, updated_at, created_by, updated_by) VALUES (:name, :code, :created_at, :updated_at, :created_by, :updated_by); INSERT core_role_permission :permission_ids',
                [...$data, 'permission_ids' => $permissionIds],
            );

            $transaction->commit();

            return $roleId;
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public function updateWithPermissions(RoleEntity $role, array $permissionIds): void
    {
        $transaction = $this->db->beginTransaction();

        try {
            $role->stampUpdated($role->updatedBy);
            $data = [
                'name' => $role->name,
                'code' => $role->code,
                'updated_at' => $role->updatedAt,
                'updated_by' => $role->updatedBy,
            ];

            $this->db->createCommand()->update('{{%core_role}}', $data, [
                'id' => $role->id,
            ])->execute();

            $this->replacePermissions((int) $role->id, $permissionIds);

            $this->entityLogRepository->record(
                'role',
                $role->id,
                EntityLogRepository::ACTION_UPDATE,
                $role,
                'UPDATE core_role SET name = :name, code = :code, updated_at = :updated_at, updated_by = :updated_by WHERE id = :id; REPLACE core_role_permission WITH :permission_ids',
                [...$data, 'id' => $role->id, 'permission_ids' => $permissionIds],
            );

            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public function delete(int $id, ?int $actorId = null): void
    {
        $transaction = $this->db->beginTransaction();

        try {
            $role = $this->findById($id);

            $this->db->createCommand()
                ->delete('{{%core_role_permission}}', ['role_id' => $id])
                ->execute();

            $this->db->createCommand()
                ->delete('{{%core_role}}', ['id' => $id])
                ->execute();

            if ($role !== null) {
                $this->entityLogRepository->record(
                    'role',
                    $id,
                    EntityLogRepository::ACTION_DELETE,
                    $role,
                    'DELETE FROM core_role_permission WHERE role_id = :id; DELETE FROM core_role WHERE id = :id',
                    ['id' => $id],
                    actorId: $actorId,
                );
            }

            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    private function replacePermissions(int $roleId, array $permissionIds): void
    {
        $this->db->createCommand()
            ->delete('{{%core_role_permission}}', ['role_id' => $roleId])
            ->execute();

        foreach ($permissionIds as $permissionId) {
            $this->db->createCommand()->insert('{{%core_role_permission}}', [
                'role_id' => $roleId,
                'permission_id' => (int) $permissionId,
            ])->execute();
        }
    }

    private function mapRow(array $row): RoleEntity
    {
        return new RoleEntity(
            id: (int) $row['id'],
            name: (string) $row['name'],
            code: (string) $row['code'],
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
            createdBy: ($row['created_by'] ?? null) !== null ? (int) $row['created_by'] : null,
            updatedBy: ($row['updated_by'] ?? null) !== null ? (int) $row['updated_by'] : null,
        );
    }
}
