<?php

namespace App\Entity;

use App\Repository\FacturationUtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FacturationUtilisateurRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_NUM_FACTURE', columns: ['num_facture'])]
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

    #[ORM\Column(length: 255, unique: true)]
    private ?string $numFacture = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = "impayee";

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateEmission = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $datePaiement = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $mailEnvoye = false;

    #[ORM\Column(length: 20, options: ['default' => 'facture'])]
    private string $type = 'facture';

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?FacturationUtilisateur $factureOrigine = null;

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

    public function getNumFacture(): ?string
    {
        return $this->numFacture;
    }

    public function setNumFacture(string $numFacture): static
    {
        $this->numFacture = $numFacture;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function isMailEnvoye(): bool
    {
        return $this->mailEnvoye;
    }

    public function setMailEnvoye(bool $mailEnvoye): self
    {
        $this->mailEnvoye = $mailEnvoye;
        return $this;
    }

    public function getDateEmission(): ?\DateTimeImmutable
    {
        return $this->dateEmission;
    }

    public function setDateEmission(\DateTimeImmutable $dateEmission): static
    {
        $this->dateEmission = $dateEmission;

        return $this;
    }

    public function getDatePaiement(): ?\DateTimeImmutable
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?\DateTimeImmutable $datePaiement): static
    {
        $this->datePaiement = $datePaiement;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getFactureOrigine(): ?self
    {
        return $this->factureOrigine;
    }

    public function setFactureOrigine(?self $factureOrigine): static
    {
        $this->factureOrigine = $factureOrigine;
        return $this;
    }
}
