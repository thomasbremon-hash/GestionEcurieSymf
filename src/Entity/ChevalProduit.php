<?php

namespace App\Entity;

use App\Repository\ChevalProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChevalProduitRepository::class)]
class ChevalProduit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'chevalProduits')]
    private ?Cheval $cheval = null;

    #[ORM\ManyToOne(inversedBy: 'chevalProduits')]
    private ?Produit $produit = null;

    #[ORM\ManyToOne(inversedBy: 'chevalProduits')]
    private ?MoisDeGestion $moisDeGestion = null;

    #[ORM\Column]
    private ?float $quantite = null;

    #[ORM\Column]
    private ?float $prixUnitaire = null;

    #[ORM\Column]
    private ?float $total = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

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

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;

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

    public function getQuantite(): ?float
    {
        return $this->quantite;
    }

    public function setQuantite(float $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPrixUnitaire(): ?float
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(float $prixUnitaire): static
    {
        $this->prixUnitaire = $prixUnitaire;

        return $this;
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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }
}
