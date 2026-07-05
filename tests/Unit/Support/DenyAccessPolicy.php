<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Data\AccessPolicyInterface;

final class DenyAccessPolicy implements AccessPolicyInterface
{
    public function canAccess(): bool
    {
        return false;
    }
}
