<?php

declare(strict_types=1);

namespace Tests\Repository;

use Doctrine\ORM\EntityRepository;
use Tests\Entity\Entity;

/**
 * @extends EntityRepository<Entity>
 */
class RepositoryNoSave extends EntityRepository
{
    public function persist(Entity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
