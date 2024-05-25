<?php

declare(strict_types=1);

namespace Tests\Repository;

use Doctrine\ORM\EntityRepository;
use Tests\Entity\Entity;

/**
 * @extends EntityRepository<Entity>
 */
class Repository extends EntityRepository
{
    public function save(Entity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Entity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
