<?php

declare(strict_types=1);

namespace Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

class EntityConstruct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id; // @phpstan-ignore property.onlyRead

    public int $int;
    public bool $bool;
    public float $float;
    public string $string;
    public $mixed; // @phpstan-ignore missingType.property

    public function __construct( // @phpstan-ignore missingType.parameter
        int $int,
        bool $bool,
        float $float,
        string $string,
        $mixed
    ) {
        $this->int = $int;
        $this->bool = $bool;
        $this->float = $float;
        $this->string = $string;
        $this->mixed = $mixed;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
