<?php

declare(strict_types=1);

namespace Tests\Repository;

use Doctrine\ORM\EntityRepository;
use Tests\Entity\EntityUser;

/**
 * @extends EntityRepository<EntityUser>
 */
class RepositoryUser extends EntityRepository
{
}
