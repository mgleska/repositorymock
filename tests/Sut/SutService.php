<?php

declare(strict_types=1);

namespace Tests\Sut;

use Tests\Repository\Repository;

class SutService
{
    public function __construct(
        private readonly Repository $repository,
    )
    {}

    public function service(): void
    {
        $this->repository->find(1);
    }
}
