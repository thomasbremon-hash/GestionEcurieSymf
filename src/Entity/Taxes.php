<?php

namespace App\Entity;

use App\Repository\TaxesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaxesRepository::class)]
class Taxes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column]
    private ?int $pourcentage = null;

    /**
     * @var Collection<int, ProduitEntrepriseTaxes>
     */
    #[ORM\OneToMany(targetEntity: ProduitEntrepriseTaxes::class, mappedBy: 'taxes')]
    private Collection $produitEntrepriseTaxes;

    public function __construct()
    {
        $this->produitEntrepriseTaxes = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getPourcentage(): ?int
    {
        return $this->pourcentage;
    }

    public function setPourcentage(int $pourcentage): static
    {
        $this->pourcentage = $pourcentage;

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
            $produitEntrepriseTax->setTaxes($this);
        }

        return $this;
    }

    public function removeProduitEntrepriseTax(ProduitEntrepriseTaxes $produitEntrepriseTax): static
    {
        if ($this->produitEntrepriseTaxes->removeElement($produitEntrepriseTax)) {
            // set the owning side to null (unless already changed)
            if ($produitEntrepriseTax->getTaxes() === $this) {
                $produitEntrepriseTax->setTaxes(null);
            }
        }

        return $this;
    }
}
