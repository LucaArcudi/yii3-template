<?php

declare(strict_types=1);

namespace App\Data\Core\Log;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

final readonly class LogReader
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function findByEntity(string $entityType, int|string $entityId, int $limit = 10): array
    {
        return (new Query($this->db))
            ->select([
                'l.id',
                'l.entity_type',
                'l.entity_id',
                'l.action',
                'l.source',
                'l.actor_id',
                'l.entity_created_at',
                'l.entity_updated_at',
                'l.sql_query',
                'l.sql_params',
                'l.url',
                'l.method',
                'l.request_query',
                'l.request_body',
                'l.console_command',
                'l.ip_address',
                'l.user_agent',
                'l.created_at',
                'actor_name' => 'u.name',
                'actor_email' => 'u.email',
            ])
            ->from(['l' => '{{%core_log}}'])
            ->leftJoin(['u' => '{{%core_user}}'], 'u.id = l.actor_id')
            ->where([
                'l.entity_type' => $entityType,
                'l.entity_id' => (string) $entityId,
            ])
            ->orderBy(['l.id' => SORT_DESC])
            ->limit($limit)
            ->all();
    }
}
