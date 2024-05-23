<?php

declare(strict_types=1);

namespace Tests\Repository;

use Doctrine\ORM\EntityRepository;
use Tests\Entity\EntityMulti;

class RepositoryMulti extends EntityRepository
{
    /**
     * @method EntityMulti|null find($id, $lockMode = null, $lockVersion = null)
     * @method EntityMulti|null findOneBy(array $criteria, array $orderBy = null)
     * @method EntityMulti[]    findAll()
     * @method EntityMulti[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
     */

    public function save(EntityMulti $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(EntityMulti $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);

        if ($flush) {
            $this->_em->flush();
        }
    }
}
