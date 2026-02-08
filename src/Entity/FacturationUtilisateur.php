<?php

namespace App\Entity;

use App\Repository\FacturationUtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FacturationUtilisateurRepository::class)]
class FacturationUtilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $total = null;

    #[ORM\ManyToOne(inversedBy: 'facturationUtilisateurs')]
    private ?User $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'facturationUtilisateurs')]
    private ?MoisDeGestion $moisDeGestion = null;

    #[ORM\ManyToOne(inversedBy: 'ManyToOne')]
    private ?Entreprise $entreprise = null;

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

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

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

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }
}
