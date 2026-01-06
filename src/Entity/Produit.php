<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    /**
     * @var Collection<int, ProduitEntrepriseTaxes>
     */
    #[ORM\OneToMany(targetEntity: ProduitEntrepriseTaxes::class, mappedBy: 'produit')]
    private Collection $produitEntrepriseTaxes;

    public function __construct()
    {
        $this->produitEntrepriseTaxes = new ArrayCollection();
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

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, ProduitEntrepriseTaxes>
     */
    public function getProduitEntrepriseTaxes(): Collection
    {
        return $this->produitEntrepriseTaxes;
    }

    public function addProduitEntrepriseTax(ProduitEntrepriseTaxes $produitEntrepriseTax): static
    {
        if (!$this->produitEntrepriseTaxes->contains($produitEntrepriseTax)) {
            $this->produitEntrepriseTaxes->add($produitEntrepriseTax);
            $produitEntrepriseTax->setProduit($this);
        }

        return $this;
    }

    public function removeProduitEntrepriseTax(ProduitEntrepriseTaxes $produitEntrepriseTax): static
    {
        if ($this->produitEntrepriseTaxes->removeElement($produitEntrepriseTax)) {
            // set the owning side to null (unless already changed)
            if ($produitEntrepriseTax->getProduit() === $this) {
                $produitEntrepriseTax->setProduit(null);
            }
        }

        return $this;
    }

}
