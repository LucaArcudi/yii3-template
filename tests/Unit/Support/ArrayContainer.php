<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use Psr\Container\ContainerInterface;

final readonly class ArrayContainer implements ContainerInterface
{
    /**
     * @param array<class-string, object> $services
     */
    public function __construct(
        private array $services,
    ) {}

    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new PolicyNotFoundException($id);
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
