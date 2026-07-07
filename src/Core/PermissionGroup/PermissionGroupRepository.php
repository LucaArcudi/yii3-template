<?php

declare(strict_types=1);

namespace App\Core\PermissionGroup;

use App\Core\Log\EntityLogRepository;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

use function date;
use function preg_replace;
use function sprintf;
use function strtoupper;
use function trim;

final readonly class PermissionGroupRepository
{
    public function __construct(
        private ConnectionInterface $db,
        private EntityLogRepository $entityLogRepository,
    ) {}

    public function exists(int $id): bool
    {
        return (new Query($this->db))
            ->from('{{%core_permission_group}}')
            ->where(['id' => $id])
            ->exists();
    }

    public function isAssigned(int $id): bool
    {
        return (new Query($this->db))
            ->from('{{%core_permission}}')
            ->where(['group_id' => $id])
            ->exists();
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = (new Query($this->db))
            ->from('{{%core_permission_group}}')
            ->where(['name' => trim($name)]);

        if ($excludeId !== null) {
            $query = $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists();
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = (new Query($this->db))
            ->from('{{%core_permission_group}}')
            ->where(['code' => $this->normalizeCode($code)]);

        if ($excludeId !== null) {
            $query = $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists();
    }

    public function findById(int $id): ?PermissionGroupEntity
    {
        $row = (new Query($this->db))
            ->from('{{%core_permission_group}}')
            ->where(['id' => $id])
            ->one();

        return $row === null ? null : $this->mapRow($row);
    }

    public function findByName(string $name): ?PermissionGroupEntity
    {
        $row = (new Query($this->db))
            ->from('{{%core_permission_group}}')
            ->where(['name' => trim($name)])
            ->one();

        return $row === null ? null : $this->mapRow($row);
    }

    public function findSelectableOptions(): array
    {
        $rows = (new Query($this->db))
            ->select(['id', 'name', 'code'])
            ->from('{{%core_permission_group}}')
            ->orderBy([
                'name' => SORT_ASC,
                'code' => SORT_ASC,
                'id' => SORT_ASC,
            ])
            ->all();

        $options = [];

        foreach ($rows as $row) {
            $options[(int) $row['id']] = sprintf('%s (%s)', (string) $row['name'], (string) $row['code']);
        }

        return $options;
    }

    public function findOrCreateByName(string $name, ?int $actorId = null): PermissionGroupEntity
    {
        $name = trim($name);
        $existing = $this->findByName($name);

        if ($existing !== null) {
            return $existing;
        }

        return $this->create(new PermissionGroupEntity(
            name: $name,
            code: $this->nextAvailableCode($name),
            createdBy: $actorId,
            updatedBy: $actorId,
        ));
    }

    public function create(PermissionGroupEntity $group): PermissionGroupEntity
    {
        $group->code = $this->normalizeCode($group->code);
        $group->stampCreated($group->createdBy ?? $group->updatedBy);

        $data = [
            'name' => $group->name,
            'code' => $group->code,
            'created_at' => $group->createdAt,
            'updated_at' => $group->updatedAt,
            'created_by' => $group->createdBy,
            'updated_by' => $group->updatedBy,
        ];

        $this->db->createCommand()->insert('{{%core_permission_group}}', $data)->execute();
        $group->id = (int) $this->db->getLastInsertID();

        $this->entityLogRepository->record(
            'permission_group',
            $group->id,
            EntityLogRepository::ACTION_CREATE,
            $group,
            'INSERT INTO core_permission_group (name, code, created_at, updated_at, created_by, updated_by) VALUES (:name, :code, :created_at, :updated_at, :created_by, :updated_by)',
            $data,
        );

        return $group;
    }

    public function update(PermissionGroupEntity $group): void
    {
        $group->code = $this->normalizeCode($group->code);
        $group->stampUpdated($group->updatedBy);

        $data = [
            'name' => $group->name,
            'code' => $group->code,
            'updated_at' => $group->updatedAt,
            'updated_by' => $group->updatedBy,
        ];

        $this->db->createCommand()->update('{{%core_permission_group}}', $data, [
            'id' => $group->id,
        ])->execute();

        $this->entityLogRepository->record(
            'permission_group',
            $group->id,
            EntityLogRepository::ACTION_UPDATE,
            $group,
            'UPDATE core_permission_group SET name = :name, code = :code, updated_at = :updated_at, updated_by = :updated_by WHERE id = :id',
            [...$data, 'id' => $group->id],
        );
    }

    public function delete(int $id, ?int $actorId = null): void
    {
        $group = $this->findById($id);

        $this->db->createCommand()
            ->delete('{{%core_permission_group}}', ['id' => $id])
            ->execute();

        if ($group !== null) {
            $this->entityLogRepository->record(
                'permission_group',
                $id,
                EntityLogRepository::ACTION_DELETE,
                $group,
                'DELETE FROM core_permission_group WHERE id = :id',
                ['id' => $id],
                actorId: $actorId,
            );
        }
    }

    private function nextAvailableCode(string $name): string
    {
        $base = $this->normalizeCode($name);
        $base = $base !== '' ? $base : 'GRUPPO';
        $code = $base;
        $suffix = 2;

        while ($this->codeExists($code)) {
            $code = $base . '_' . $suffix;
            ++$suffix;
        }

        return $code;
    }

    private function normalizeCode(string $code): string
    {
        $code = strtoupper(trim($code));
        $code = (string) preg_replace('/[^A-Z0-9]+/', '_', $code);

        return trim($code, '_');
    }

    private function mapRow(array $row): PermissionGroupEntity
    {
        return new PermissionGroupEntity(
            id: (int) $row['id'],
            name: (string) $row['name'],
            code: (string) $row['code'],
            createdAt: $row['created_at'] ?? date('Y-m-d H:i:s'),
            updatedAt: $row['updated_at'] ?? null,
            createdBy: ($row['created_by'] ?? null) !== null ? (int) $row['created_by'] : null,
            updatedBy: ($row['updated_by'] ?? null) !== null ? (int) $row['updated_by'] : null,
        );
    }
}
