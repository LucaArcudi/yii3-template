<?php

declare(strict_types=1);

namespace App\Data\Mes\Task;

use App\Data\Core\Log\EntityLogRepository;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

final readonly class TaskRepository
{
    public function __construct(
        private ConnectionInterface $db,
        private EntityLogRepository $entityLogRepository,
    ) {
    }

    public function exists(int $id): bool
    {
        return (new Query($this->db))
            ->from('{{%mes_task}}')
            ->where(['id' => $id])
            ->exists();
    }

    public function findById(int $id): ?TaskEntity
    {
        $row = (new Query($this->db))
            ->from('{{%mes_task}}')
            ->where(['id' => $id])
            ->one();

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    /**
     * @return TaskEntity[]
     */
    public function findAll(): array
    {
        $rows = (new Query($this->db))
            ->from('{{%mes_task}}')
            ->orderBy(['id' => SORT_DESC])
            ->all();

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function create(TaskEntity $task): int
    {
        $task->stampCreated($task->createdBy ?? $task->updatedBy);
        $data = [
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'start_date' => $task->startDate,
            'end_date' => $task->endDate,
            'created_at' => $task->createdAt,
            'updated_at' => $task->updatedAt,
            'created_by' => $task->createdBy,
            'updated_by' => $task->updatedBy,
        ];

        $this->db->createCommand()->insert('{{%mes_task}}', $data)->execute();

        $id = (int) $this->db->getLastInsertID();
        $task->id = $id;

        $this->entityLogRepository->record(
            'task',
            $id,
            EntityLogRepository::ACTION_CREATE,
            $task,
            'INSERT INTO mes_task (title, description, status, start_date, end_date, created_at, updated_at, created_by, updated_by) VALUES (:title, :description, :status, :start_date, :end_date, :created_at, :updated_at, :created_by, :updated_by)',
            $data,
        );

        return $id;
    }

    public function update(TaskEntity $task): void
    {
        $task->stampUpdated($task->updatedBy);
        $data = [
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'start_date' => $task->startDate,
            'end_date' => $task->endDate,
            'updated_at' => $task->updatedAt,
            'updated_by' => $task->updatedBy,
        ];

        $this->db->createCommand()->update('{{%mes_task}}', $data, [
            'id' => $task->id,
        ])->execute();

        $this->entityLogRepository->record(
            'task',
            $task->id,
            EntityLogRepository::ACTION_UPDATE,
            $task,
            'UPDATE mes_task SET title = :title, description = :description, status = :status, start_date = :start_date, end_date = :end_date, updated_at = :updated_at, updated_by = :updated_by WHERE id = :id',
            [...$data, 'id' => $task->id],
        );
    }

    public function delete(int $id, ?int $actorId = null): void
    {
        $task = $this->findById($id);

        $this->db->createCommand()
            ->delete('{{%mes_task}}', ['id' => $id])
            ->execute();

        if ($task !== null) {
            $this->entityLogRepository->record(
                'task',
                $id,
                EntityLogRepository::ACTION_DELETE,
                $task,
                'DELETE FROM mes_task WHERE id = :id',
                ['id' => $id],
                actorId: $actorId,
            );
        }
    }

    private function mapRow(array $row): TaskEntity
    {
        return new TaskEntity(
            id: (int) $row['id'],
            title: (string) $row['title'],
            description: $row['description'] !== null ? (string) $row['description'] : null,
            status: (int) $row['status'],
            startDate: $row['start_date'] ?? null,
            endDate: $row['end_date'] ?? null,
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
            createdBy: ($row['created_by'] ?? null) !== null ? (int) $row['created_by'] : null,
            updatedBy: ($row['updated_by'] ?? null) !== null ? (int) $row['updated_by'] : null,
        );
    }
}
