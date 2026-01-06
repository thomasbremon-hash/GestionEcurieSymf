<?php

namespace App\Entity;

use App\Repository\ProduitEntrepriseTaxesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitEntrepriseTaxesRepository::class)]
class ProduitEntrepriseTaxes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'produitEntrepriseTaxe')]
    private ?Entreprise $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'produitEntrepriseTaxes')]
    private ?Produit $produit = null;

    #[ORM\ManyToOne(inversedBy: 'produitEntrepriseTaxes')]
    private ?Taxes $taxes = null;

    

    public function __construct()
    {

    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;

        return $this;
    }

    public function getTaxes(): ?Taxes
    {
        return $this->taxes;
    }

    public function setTaxes(?Taxes $taxes): static
    {
        $this->taxes = $taxes;

        return $this;
    }

   
}
