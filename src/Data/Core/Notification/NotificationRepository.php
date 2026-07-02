<?php

declare(strict_types=1);

namespace App\Data\Core\Notification;

use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

use function array_values;
use function date;

final readonly class NotificationRepository
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    /**
     * @param int[] $userIds
     */
    public function createForUsers(NotificationEntity $notification, array $userIds): int
    {
        $userIds = $this->normalizeUserIds($userIds);

        if ($userIds === []) {
            return 0;
        }

        $transaction = $this->db->beginTransaction();

        try {
            $notification->stampCreated($notification->createdBy ?? $notification->updatedBy);

            $this->db->createCommand()->insert('{{%core_notification}}', [
                'title' => $notification->title,
                'description' => $notification->description,
                'url' => $notification->url,
                'created_at' => $notification->createdAt,
                'updated_at' => $notification->updatedAt,
                'created_by' => $notification->createdBy,
                'updated_by' => $notification->updatedBy,
            ])->execute();

            $id = (int) $this->db->getLastInsertID();
            $notification->id = $id;

            foreach ($userIds as $userId) {
                $this->db->createCommand()->insert('{{%core_notification_user}}', [
                    'notification_id' => $id,
                    'user_id' => $userId,
                    'is_read' => 0,
                    'created_at' => $notification->createdAt,
                    'updated_at' => $notification->updatedAt,
                    'created_by' => $notification->createdBy,
                    'updated_by' => $notification->updatedBy,
                ])->execute();
            }

            $transaction->commit();

            return $id;
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public function notifyUser(
        int $userId,
        string $title,
        ?string $description = null,
        ?string $url = null,
        ?int $actorId = null,
    ): int {
        return $this->createForUsers(
            new NotificationEntity(
                title: trim($title),
                description: $description !== null ? trim($description) : null,
                url: $url !== null && trim($url) !== '' ? trim($url) : null,
                createdBy: $actorId,
                updatedBy: $actorId,
            ),
            [$userId],
        );
    }

    public function markRead(int $notificationId, int $userId, ?int $actorId = null): void
    {
        $this->db->createCommand()->update('{{%core_notification_user}}', [
            'is_read' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $actorId ?? $userId,
        ], [
            'notification_id' => $notificationId,
            'user_id' => $userId,
        ])->execute();
    }

    public function markAllRead(int $userId, ?int $actorId = null): void
    {
        $this->db->createCommand()->update('{{%core_notification_user}}', [
            'is_read' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $actorId ?? $userId,
        ], [
            'user_id' => $userId,
            'is_read' => 0,
        ])->execute();
    }

    public function urlForUser(int $notificationId, int $userId): ?string
    {
        $row = (new Query($this->db))
            ->select(['n.url'])
            ->from(['n' => '{{%core_notification}}'])
            ->innerJoin(['nu' => '{{%core_notification_user}}'], 'nu.notification_id = n.id')
            ->where([
                'n.id' => $notificationId,
                'nu.user_id' => $userId,
            ])
            ->one();

        if ($row === null) {
            return null;
        }

        $url = $this->normalizeInternalUrl((string) ($row['url'] ?? ''));

        return $url === '' ? '/notification' : $url;
    }

    private function normalizeInternalUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '' || $url[0] !== '/' || str_starts_with($url, '//')) {
            return '';
        }

        return $url;
    }

    /**
     * @param int[] $userIds
     * @return int[]
     */
    private function normalizeUserIds(array $userIds): array
    {
        $normalized = [];

        foreach ($userIds as $userId) {
            $userId = (int) $userId;

            if ($userId > 0) {
                $normalized[$userId] = $userId;
            }
        }

        return array_values($normalized);
    }
}
