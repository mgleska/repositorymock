<?php

/**
 * @noinspection PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RepositoryMock\RepositoryMockObject;
use RepositoryMock\RepositoryMockTrait;
use Tests\Entity\Entity;
use Tests\Repository\Repository;
use Tests\Sut\SutService;

class SutServiceTest extends TestCase
{
    use RepositoryMockTrait;

    private Sutservice $sut;

    private Repository|RepositoryMockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createRepositoryMock(Repository::class);

        $this->sut = new SutService(
            $this->repository,
        );
    }

    #[Test]
    public function found(): void
    {
        $this->repository->loadStore([
            [
                'id' => 1,
                'name' => 'test',
            ],
        ]);

        $result = $this->sut->getFirst();

        $this->assertInstanceOf(Entity::class, $result);
    }

    #[Test]
    public function notFound(): void
    {
        $this->repository->loadStore([
            [
                'id' => 22,
                'name' => 'test 22',
            ],
        ]);

        $result = $this->sut->getFirst();

        $this->assertNull($result);
    }
}
