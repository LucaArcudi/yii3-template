<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

final class PolicyNotFoundException extends RuntimeException implements NotFoundExceptionInterface {}
