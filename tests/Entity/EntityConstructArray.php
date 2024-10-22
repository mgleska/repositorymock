<?php

declare(strict_types=1);

namespace Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

class EntityConstructArray
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id; // @phpstan-ignore property.onlyRead

    /** @var int[] */
    public array $array;

    /**
     * @param int[] $array
     */
    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
