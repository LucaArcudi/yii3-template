<?php

declare(strict_types=1);

namespace App\Data\Core\User;

use Yiisoft\Auth\IdentityInterface;
use Yiisoft\User\Login\Cookie\CookieLoginIdentityInterface;

use function password_verify;

final readonly class UserIdentity implements IdentityInterface, CookieLoginIdentityInterface
{
    public function __construct(
        private string $id,
        private string $email,
        private string $name,
        private ?string $cookieLoginKeyHash = null,
        private ?string $cookieLoginKey = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCookieLoginKey(): string
    {
        return $this->cookieLoginKey ?? '';
    }

    public function validateCookieLoginKey(string $key): bool
    {
        return $this->cookieLoginKeyHash !== null
            && $this->cookieLoginKeyHash !== ''
            && password_verify($key, $this->cookieLoginKeyHash);
    }
}
