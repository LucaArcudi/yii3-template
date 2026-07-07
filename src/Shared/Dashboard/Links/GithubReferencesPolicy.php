<?php

declare(strict_types=1);

namespace App\Shared\Dashboard\Links;

use App\Shared\Data\AccessPolicyInterface;
use Yiisoft\User\CurrentUser;

final readonly class GithubReferencesPolicy implements AccessPolicyInterface
{
    public function __construct(
        private CurrentUser $currentUser,
    ) {}

    public function canAccess(): bool
    {
        return !$this->currentUser->isGuest();
    }
}
