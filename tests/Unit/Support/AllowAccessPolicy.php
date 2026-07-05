<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Data\AccessPolicyInterface;

final class AllowAccessPolicy implements AccessPolicyInterface
{
    public function canAccess(): bool
    {
        return true;
    }
}
