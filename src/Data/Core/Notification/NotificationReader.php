<?php

declare(strict_types=1);

namespace App\Data\Core\Notification;

use Throwable;
use Yiisoft\Data\Db\QueryDataReader;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\User\CurrentUser;

final readonly class NotificationReader
{
    public function __construct(
        private ConnectionInterface $db,
        private CurrentUser $currentUser,
    ) {}

    public function getIndex(string $sort = '-created_at'): QueryDataReader
    {
        $query = $this->baseQuery();
        $userId = $this->currentUserId();

        $query = $userId === null
            ? $query->andWhere(new Expression('1 = 0'))
            : $query->andWhere(['nu.user_id' => $userId]);

        $sortDefinition = Sort::only(['id', 'title', 'created_at', 'is_read']);
        $sortDefinition = $sort !== ''
            ? $sortDefinition->withOrderString($sort)
            : $sortDefinition->withOrder(['created_at' => 'desc']);

        return (new QueryDataReader($query))->withSort($sortDefinition);
    }

    public function recentForCurrentUser(int $limit = 5): array
    {
        $userId = $this->currentUserId();

        if ($userId === null) {
            return [];
        }

        try {
            return $this->baseQuery()
                ->where(['nu.user_id' => $userId])
                ->orderBy(['nu.is_read' => SORT_ASC, 'n.created_at' => SORT_DESC, 'n.id' => SORT_DESC])
                ->limit($limit)
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    public function unreadCountForCurrentUser(): int
    {
        $userId = $this->currentUserId();

        if ($userId === null) {
            return 0;
        }

        try {
            $count = (new Query($this->db))
                ->from('{{%core_notification_user}}')
                ->where([
                    'user_id' => $userId,
                    'is_read' => 0,
                ])
                ->count();

            return (int) $count;
        } catch (Throwable) {
            return 0;
        }
    }

    private function baseQuery(): Query
    {
        return (new Query($this->db))
            ->select([
                'n.id',
                'n.title',
                'n.description',
                'n.url',
                'n.created_at',
                'n.updated_at',
                'n.created_by',
                'n.updated_by',
                'nu.is_read',
                'nu.notification_id',
                'nu.updated_at AS user_updated_at',
            ])
            ->from(['n' => '{{%core_notification}}'])
            ->innerJoin(['nu' => '{{%core_notification_user}}'], 'nu.notification_id = n.id');
    }

    private function currentUserId(): ?int
    {
        if ($this->currentUser->isGuest()) {
            return null;
        }

        $id = $this->currentUser->getId();

        return $id === null || $id === '' ? null : (int) $id;
    }
}
