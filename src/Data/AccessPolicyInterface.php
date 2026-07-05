<?php

declare(strict_types=1);

namespace App\Data;

interface AccessPolicyInterface
{
    public function canAccess(): bool;
}
