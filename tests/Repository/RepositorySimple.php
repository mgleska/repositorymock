<?php

declare(strict_types=1);

namespace Tests\Repository;

use Tests\Entity\Entity;
use Exception;

class RepositorySimple
{
    /**
     * @throws Exception
     */
    public function find($id, $lockMode = null, $lockVersion = null): Entity|null
    {
        throw new Exception('not mocked');
    }

    /**
     * @throws Exception
     */
    public function findOneBy(array $criteria, array $orderBy = null): Entity|null
    {
        throw new Exception('not mocked');
    }

    /**
     * @throws Exception
     */
    public function findAll(): array
    {
        throw new Exception('not mocked');
    }

    /**
     * @throws Exception
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
    {
        throw new Exception('not mocked');
    }

    /**
     * @throws Exception
     */
    public function save(Entity $entity, bool $flush = true)
    {
        throw new Exception('not mocked');
    }

    public function public3(): string
    {
        return 'from public3: ' . $this->private1();
    }

    public function public4(): string
    {
        return 'from public4';
    }

    private function private1(): string
    {
        return 'result from private1';
    }
}
