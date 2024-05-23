<?php

declare(strict_types=1);

namespace Tests\Repository;

use Doctrine\ORM\EntityRepository;
use Tests\Entity\Entity;

class RepositorySaveWithoutType extends EntityRepository
{
    /**
     * @method Entity|null find($id, $lockMode = null, $lockVersion = null)
     * @method Entity|null findOneBy(array $criteria, array $orderBy = null)
     * @method Entity[]    findAll()
     * @method Entity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
     */

    public function save($entity, bool $flush = true): void
    {
        $this->_em->persist($entity);

        if ($flush) {
            $this->_em->flush();
        }
    }
}
