<?php

declare(strict_types=1);

namespace App\Services\Core;

use App\Helpers\Translate;
use Yiisoft\Session\SessionInterface;

use function hash_equals;
use function is_array;
use function is_int;
use function is_string;
use function random_int;
use function time;
use function trim;

final readonly class MathCaptchaService
{
    private const SESSION_KEY = 'auth.register_captcha';
    private const TTL_SECONDS = 600;

    public function __construct(
        private SessionInterface $session,
    ) {}

    /**
     * @return array{question: string}
     */
    public function generate(): array
    {
        $left = random_int(2, 12);
        $right = random_int(2, 12);
        $answer = (string) ($left + $right);

        $this->session->set(self::SESSION_KEY, [
            'answer' => $answer,
            'expiresAt' => time() + self::TTL_SECONDS,
        ]);

        return [
            'question' => Translate::t('Quanto fa {left} + {right}?', ['left' => $left, 'right' => $right]),
        ];
    }

    public function validate(?string $answer): bool
    {
        $challenge = $this->session->get(self::SESSION_KEY);
        $this->session->remove(self::SESSION_KEY);

        if (!is_array($challenge)) {
            return false;
        }

        $expected = $challenge['answer'] ?? null;
        $expiresAt = $challenge['expiresAt'] ?? null;

        if (!is_string($expected) || !is_int($expiresAt) || $expiresAt < time()) {
            return false;
        }

        return hash_equals($expected, trim((string) $answer));
    }

    public function clear(): void
    {
        $this->session->remove(self::SESSION_KEY);
    }
}
