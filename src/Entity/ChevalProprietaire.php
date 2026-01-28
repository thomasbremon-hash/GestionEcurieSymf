<?php

namespace App\Entity;

use App\Repository\ChevalProprietaireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChevalProprietaireRepository::class)]
class ChevalProprietaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'chevalProprietaires')]
    private ?Cheval $cheval = null;

    #[ORM\ManyToOne(inversedBy: 'chevalProprietaires')]
    private ?User $proprietaire = null;

    #[ORM\Column]
    private ?float $pourcentage = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getProprietaire(): ?User
    {
        return $this->proprietaire;
    }

    public function setProprietaire(?User $proprietaire): static
    {
        $this->proprietaire = $proprietaire;

        return $this;
    }

    public function getPourcentage(): ?float
    {
        return $this->pourcentage;
    }

    public function setPourcentage(float $pourcentage): static
    {
        $this->pourcentage = $pourcentage;

        return $this;
    }
}
