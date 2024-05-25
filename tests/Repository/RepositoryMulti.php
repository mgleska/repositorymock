<?php

declare(strict_types=1);

namespace Tests\Repository;

use Doctrine\ORM\EntityRepository;
use Tests\Entity\EntityMulti;

/**
 * @extends EntityRepository<EntityMulti>
 */
class RepositoryMulti extends EntityRepository
{
    public function save(EntityMulti $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EntityMulti $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
