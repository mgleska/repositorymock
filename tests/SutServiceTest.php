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
use RuntimeException;
use Tests\Entity\Entity;
use Tests\Entity\EntityUser;
use Tests\Repository\Repository;
use Tests\Repository\RepositoryUser;
use Tests\Sut\SutService;

class SutServiceTest extends TestCase
{
    use RepositoryMockTrait;

    private Sutservice $sut;

    private Repository|RepositoryMockObject $repository;
    private RepositoryUser|RepositoryMockObject $repositoryUser;

    protected function setUp(): void
    {
        $this->repository = $this->createRepositoryMock(Repository::class);
        $this->repositoryUser = $this->createRepositoryMock(RepositoryUser::class, EntityUser::class);

        $this->sut = new SutService(
            $this->repository,
            $this->repositoryUser,
        );
    }

    #[Test]
    public function getFirstFound(): void
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
    public function getFirstNotFound(): void
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

    #[Test]
    public function makeTwoEntities(): void
    {
        $this->repository->loadStore([
            [
                'id' => 10,
                'name' => 'test 10',
            ],
        ]);
        $this->repositoryUser->loadStore([
            [
                'id' => 1,
                'fullName' => 'User Name',
            ],
        ]);

        $this->sut->makeTwoEntities();

        /** @var Entity[] $entities */
        $entities = $this->repository->getStoreContent();
        $this->assertArrayHasKey(11, $entities); // 10 + 1
        $this->assertArrayHasKey(12, $entities);
        $this->assertSame($entities[11]->getId(), $entities[11]->getReferenceId());
        $this->assertSame($entities[11]->getReferenceId(), $entities[12]->getReferenceId());
    }

    #[Test]
    public function makeTwoEntitiesException(): void
    {
        // notice lack of $this->repositoryUser->loadStore([ .... ])

        $this->expectException(RuntimeException::class);

        $this->sut->makeTwoEntities();
    }
}
