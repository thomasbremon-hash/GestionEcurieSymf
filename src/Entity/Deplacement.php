<?php

namespace App\Entity;

use App\Repository\DeplacementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeplacementRepository::class)]
class Deplacement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?int $distance = null;

    #[ORM\ManyToOne(inversedBy: 'deplacements')]
    private ?Cheval $cheval = null;

    #[ORM\ManyToOne(inversedBy: 'deplacements')]
    private ?Structure $structure = null;


    public function __construct()
    {

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDistance(): ?int
    {
        return $this->distance;
    }

    public function setDistance(int $distance): static
    {
        $this->distance = $distance;

        return $this;
    }

    public function getCheval(): ?Cheval
    {
        return $this->cheval;
    }

    public function setCheval(?Cheval $cheval): static
    {
        $this->cheval = $cheval;

        return $this;
    }

    public function getStructure(): ?Structure
    {
        return $this->structure;
    }

    public function setStructure(?Structure $structure): static
    {
        $this->structure = $structure;

        return $this;
    }

}
