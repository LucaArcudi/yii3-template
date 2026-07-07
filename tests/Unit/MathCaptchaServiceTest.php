<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Services\MathCaptchaService;
use Codeception\Test\Unit;
use Yiisoft\Session\SessionInterface;

final class MathCaptchaServiceTest extends Unit
{
    public function testGeneratedCaptchaValidatesOnce(): void
    {
        $service = new MathCaptchaService(new InMemorySession());
        $challenge = $service->generate();

        preg_match('/Quanto fa (\d+) \+ (\d+)\?/', $challenge['question'], $matches);
        $answer = (string) ((int) $matches[1] + (int) $matches[2]);

        self::assertTrue($service->validate($answer));
        self::assertFalse($service->validate($answer));
    }

    public function testCaptchaRejectsWrongAnswer(): void
    {
        $service = new MathCaptchaService(new InMemorySession());
        $service->generate();

        self::assertFalse($service->validate('999'));
    }
}

final class InMemorySession implements SessionInterface
{
    private array $data = [];

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function close(): void {}

    public function open(): void {}

    public function isActive(): bool
    {
        return true;
    }

    public function getId(): ?string
    {
        return 'test-session';
    }

    public function setId(string $sessionId): void {}

    public function regenerateId(): void {}

    public function discard(): void {}

    public function getName(): string
    {
        return 'test';
    }

    public function all(): array
    {
        return $this->data;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function pull(string $key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->remove($key);

        return $value;
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function destroy(): void
    {
        $this->clear();
    }

    public function getCookieParameters(): array
    {
        return [];
    }
}
