<?php

declare(strict_types=1);

namespace Tests\Sut;

use RuntimeException;
use Tests\Entity\Entity;
use Tests\Repository\Repository;
use Tests\Repository\RepositoryUser;

use function is_null;

class SutService
{
    private const TEMPORARY_PLACEHOLDER = -1;

    public function __construct(
        private readonly Repository $repository,
        private readonly RepositoryUser $repositoryUser,
    ) {
    }

    public function getFirst(): ?Entity
    {
        return $this->repository->find(1);
    }

    public function makeTwoEntities(): void
    {
        $user = $this->repositoryUser->find(1);
        if (is_null($user)) {
            throw new RuntimeException('User not found');
        }

        // We have requirement, that auto generated id of first entity should be set as referenceId for both entities.

        $first = new Entity();
        $first->setUpdatedBy($user);
        $first->setReferenceId(self::TEMPORARY_PLACEHOLDER);

        $this->repository->save($first, true);
        $first->setReferenceId($first->getId());
        $this->repository->save($first, true);

        $second = new Entity();
        $second->setUpdatedBy($user);
        $second->setReferenceId($first->getReferenceId());
        $this->repository->save($second, true);
    }
}
