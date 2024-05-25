<?php

/** @noinspection PhpPropertyOnlyWrittenInspection */

declare(strict_types=1);

namespace Tests\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

class Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id; // @phpstan-ignore property.onlyRead

    #[ORM\Column]
    private int $referenceId;

    #[ORM\Column(nullable: true)]
    private ?string $name;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $validTo;

    #[ORM\ManyToOne]
    private EntityUser $updatedBy;

    public function getId(): int
    {
        return $this->id;
    }

    public function getReferenceId(): int
    {
        return $this->referenceId;
    }

    public function setReferenceId(int $referenceId): void
    {
        $this->referenceId = $referenceId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getUpdatedBy(): EntityUser
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(EntityUser $updatedBy): void
    {
        $this->updatedBy = $updatedBy;
    }

    public function getValidTo(): ?DateTime
    {
        return $this->validTo;
    }

    public function setValidTo(?DateTime $validTo): void
    {
        $this->validTo = $validTo;
    }
}
