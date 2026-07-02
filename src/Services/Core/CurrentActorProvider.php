<?php

declare(strict_types=1);

namespace App\Services\Core;

use Yiisoft\User\CurrentUser;

final readonly class CurrentActorProvider
{
    public function __construct(
        private CurrentUser $currentUser,
    ) {}

    public function id(): ?int
    {
        $id = $this->currentUser->getId();

        return $id === null || $id === '' ? null : (int) $id;
    }
}
