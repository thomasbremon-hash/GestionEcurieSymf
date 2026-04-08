<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class InvoiceCounter
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id;

    #[ORM\Column]
    private int $counter;

    public function __construct(int $id, int $counter)
    {
        $this->id      = $id;
        $this->counter = $counter;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCounter(): int
    {
        return $this->counter;
    }

    public function setCounter(int $counter): void
    {
        $this->counter = $counter;
    }
}
