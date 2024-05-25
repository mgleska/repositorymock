<?php

declare(strict_types=1);

namespace Tests\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

class EntityMulti
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id; // @phpstan-ignore-line

    #[ORM\ManyToOne]
    private EntityUser $owner; // @phpstan-ignore-line

    /**
     * @var Collection<int, EntityUser>
     */
    #[ORM\OneToMany(targetEntity: EntityUser::class)]
    private Collection $users;

    #[ORM\OneToOne]
    private EntityMulti $subMulti;

    /**
     * @var Collection<int, EntityUser>
     */
    #[ORM\ManyToMany(targetEntity: EntityUser::class)]
    private Collection $groups;

    /**
     * @var Collection<int, EntityUser>
     */
    #[ORM\OneToMany]
    private Collection $badUsers;

    public function getId(): int
    {
        return $this->id;
    }

    public function getOwner(): EntityUser
    {
        return $this->owner;
    }

    /**
     * @return Collection<int, EntityUser>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @param Collection<int, EntityUser> $users
     */
    public function setUsers(Collection $users): void
    {
        $this->users = $users;
    }

    public function getSubMulti(): EntityMulti
    {
        return $this->subMulti;
    }

    public function setSubMulti(EntityMulti $subMulti): void
    {
        $this->subMulti = $subMulti;
    }

    /**
     * @return Collection<int, EntityUser>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    /**
     * @param Collection<int, EntityUser> $groups
     */
    public function setGroups(Collection $groups): void
    {
        $this->groups = $groups;
    }

    /**
     * @return Collection<int, EntityUser>
     */

    public function getBadUsers(): Collection
    {
        return $this->badUsers;
    }

    /**
     * @param Collection<int, EntityUser> $badUsers
     */
    public function setBadUsers(Collection $badUsers): void
    {
        $this->badUsers = $badUsers;
    }
}
