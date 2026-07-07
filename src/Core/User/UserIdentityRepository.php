<?php

declare(strict_types=1);

namespace App\Core\User;

use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;

final readonly class UserIdentityRepository implements IdentityRepositoryInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function findIdentity(string $id): ?IdentityInterface
    {
        $user = $this->userRepository->findById((int) $id);

        if ($user === null || !$user->isActive()) {
            return null;
        }

        return new UserIdentity(
            (string) $user->id,
            $user->email,
            $user->name,
            $user->rememberTokenHash,
        );
    }

    public function findIdentityByToken(string $token, string $type): ?IdentityInterface
    {
        return null;
    }
}
