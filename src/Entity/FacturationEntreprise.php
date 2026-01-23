<?php

namespace App\Entity;

use App\Repository\FacturationEntrepriseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FacturationEntrepriseRepository::class)]
class FacturationEntreprise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $total = null;

    #[ORM\ManyToOne(inversedBy: 'facturationEntreprises')]
    private ?Entreprise $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'facturationEntreprises')]
    private ?MoisDeGestion $moisDeGestion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getMoisDeGestion(): ?MoisDeGestion
    {
        return $this->moisDeGestion;
    }

    public function setMoisDeGestion(?MoisDeGestion $moisDeGestion): static
    {
        $this->moisDeGestion = $moisDeGestion;

        return $this;
    }
}
