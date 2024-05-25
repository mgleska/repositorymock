<?php

declare(strict_types=1);

namespace Tests\Repository;

use Doctrine\ORM\EntityRepository;
use Tests\Entity\Entity;

/**
 * @extends EntityRepository<Entity>
 */
class RepositorySaveWithoutType extends EntityRepository
{
    public function save($entity, bool $flush = false): void // @phpstan-ignore-line
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
