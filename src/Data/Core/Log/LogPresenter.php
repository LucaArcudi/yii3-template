<?php

declare(strict_types=1);

namespace App\Data\Core\Log;

use App\Helpers\Translate;

use function date;
use function json_decode;
use function json_encode;
use function sprintf;
use function strtotime;
use function trim;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final readonly class LogPresenter
{
    public function __construct(
        private array $data,
    ) {}

    public function id(): ?int
    {
        $value = $this->get('id');

        return $value === null ? null : (int) $value;
    }

    public function action(): string
    {
        return (string) $this->get('action', '');
    }

    public function actionLabel(): string
    {
        return match ($this->action()) {
            EntityLogRepository::ACTION_CREATE => Translate::t('Creazione'),
            EntityLogRepository::ACTION_UPDATE => Translate::t('Modifica'),
            EntityLogRepository::ACTION_DELETE => Translate::t('Eliminazione'),
            default => $this->action(),
        };
    }

    public function actionVariant(): string
    {
        return match ($this->action()) {
            EntityLogRepository::ACTION_CREATE => 'success',
            EntityLogRepository::ACTION_UPDATE => 'warning',
            EntityLogRepository::ACTION_DELETE => 'danger',
            default => 'secondary',
        };
    }

    public function actor(): string
    {
        $name = trim((string) $this->get('actor_name', ''));
        $email = trim((string) $this->get('actor_email', ''));
        $id = $this->get('actor_id');

        if ($name !== '' && $email !== '') {
            return sprintf('%s <%s>', $name, $email);
        }

        if ($name !== '') {
            return $name;
        }

        return $id === null || $id === '' ? '-' : '#' . (int) $id;
    }

    public function source(): string
    {
        return match ((string) $this->get('source', 'web')) {
            'console' => 'Console',
            'system' => Translate::t('Sistema'),
            default => 'Web',
        };
    }

    public function createdAt(): string
    {
        return $this->formatDate($this->get('created_at'));
    }

    public function entityCreatedAt(): string
    {
        return $this->formatDate($this->get('entity_created_at'));
    }

    public function entityUpdatedAt(): string
    {
        return $this->formatDate($this->get('entity_updated_at'));
    }

    public function query(): string
    {
        return (string) $this->get('sql_query', '');
    }

    public function params(): string
    {
        return $this->prettyJson((string) $this->get('sql_params', '{}'));
    }

    public function requestBody(): string
    {
        return $this->prettyJson((string) $this->get('request_body', ''));
    }

    public function requestQuery(): string
    {
        return $this->prettyJson((string) $this->get('request_query', ''));
    }

    public function consoleCommand(): string
    {
        return (string) $this->get('console_command', '-');
    }

    public function url(): string
    {
        return (string) $this->get('url', '-');
    }

    public function method(): string
    {
        return (string) $this->get('method', '-');
    }

    public function ipAddress(): string
    {
        return (string) $this->get('ip_address', '-');
    }

    private function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    private function formatDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return date('d/m/Y H:i:s', strtotime((string) $value));
    }

    private function prettyJson(string $json): string
    {
        if (trim($json) === '') {
            return '';
        }

        $decoded = json_decode($json, true);

        if ($decoded === null) {
            return $json;
        }

        return (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
