<?php

declare(strict_types=1);

namespace Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tests\Entity\Entity;

/**
 * @extends ServiceEntityRepository<Entity>
 */
class RepositorySaveWithoutType extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entity::class);
    }

    public function save($entity, bool $flush = false): void // @phpstan-ignore-line
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
