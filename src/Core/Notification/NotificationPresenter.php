<?php

declare(strict_types=1);

namespace App\Core\Notification;

use App\Shared\Helpers\Translate;
use Yiisoft\Html\Html;

final readonly class NotificationPresenter
{
    public function __construct(
        private array|object $row,
    ) {}

    public function id(): int
    {
        return (int) $this->value('id', 0);
    }

    public function title(): string
    {
        return (string) $this->value('title', '');
    }

    public function description(): string
    {
        return (string) ($this->value('description') ?? '');
    }

    public function url(): string
    {
        $url = trim((string) ($this->value('url') ?? ''));

        return $url === '' ? '/notification' : $url;
    }

    public function createdAt(): string
    {
        $value = (string) ($this->value('created_at') ?? '');

        return $value === '' ? '-' : $value;
    }

    public function isRead(): bool
    {
        return (int) $this->value('is_read', 0) === 1;
    }

    public function statusBadge(): string
    {
        return $this->isRead()
            ? (string) Html::span(Translate::t('Letta'), ['class' => 'badge bg-light text-muted app-status-badge'])
            : (string) Html::span(Translate::t('Nuova'), ['class' => 'badge bg-info app-status-badge']);
    }

    private function value(string $name, mixed $default = null): mixed
    {
        if (is_array($this->row)) {
            return $this->row[$name] ?? $default;
        }

        return $this->row->{$name} ?? $default;
    }
}
