<?php

namespace App\Entity;

use App\Repository\MoisDeGestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MoisDeGestionRepository::class)]
class MoisDeGestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $mois = null;

    #[ORM\Column]
    private ?int $annee = null;

    /**
     * @var Collection<int, ChevalProduit>
     */
    #[ORM\OneToMany(mappedBy: 'moisDeGestion', targetEntity: ChevalProduit::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $chevalProduits;

    /**
     * @var Collection<int, FacturationEntreprise>
     */
    #[ORM\OneToMany(targetEntity: FacturationEntreprise::class, mappedBy: 'moisDeGestion')]
    private Collection $facturationEntreprises;

    public function __construct()
    {
        $this->chevalProduits = new ArrayCollection();
        $this->facturationEntreprises = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMois(): ?int
    {
        return $this->mois;
    }

    public function setMois(int $mois): static
    {
        $this->mois = $mois;

        return $this;
    }

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(int $annee): static
    {
        $this->annee = $annee;

        return $this;
    }


    /**
     * @return Collection<int, ChevalProduit>
     */
    public function getChevalProduits(): Collection
    {
        return $this->chevalProduits;
    }

    public function addChevalProduit(ChevalProduit $chevalProduit): static
    {
        if (!$this->chevalProduits->contains($chevalProduit)) {
            $this->chevalProduits->add($chevalProduit);
            $chevalProduit->setMoisDeGestion($this);
        }

        return $this;
    }

    public function removeChevalProduit(ChevalProduit $chevalProduit): static
    {
        if ($this->chevalProduits->removeElement($chevalProduit)) {
            // set the owning side to null (unless already changed)
            if ($chevalProduit->getMoisDeGestion() === $this) {
                $chevalProduit->setMoisDeGestion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FacturationEntreprise>
     */
    public function getFacturationEntreprises(): Collection
    {
        return $this->facturationEntreprises;
    }

    public function addFacturationEntreprise(FacturationEntreprise $facturationEntreprise): static
    {
        if (!$this->facturationEntreprises->contains($facturationEntreprise)) {
            $this->facturationEntreprises->add($facturationEntreprise);
            $facturationEntreprise->setMoisDeGestion($this);
        }

        return $this;
    }

    public function removeFacturationEntreprise(FacturationEntreprise $facturationEntreprise): static
    {
        if ($this->facturationEntreprises->removeElement($facturationEntreprise)) {
            // set the owning side to null (unless already changed)
            if ($facturationEntreprise->getMoisDeGestion() === $this) {
                $facturationEntreprise->setMoisDeGestion(null);
            }
        }

        return $this;
    }
}
