<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ChevalRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChevalRepository::class)]
#[Assert\Callback('validatePourcentages')]
class Cheval
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $race = null;

    #[ORM\Column(length: 255)]
    private ?string $sexe = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateNaissance = null;


    /**
     * @var Collection<int, Participation>
     */
    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'cheval')]
    private Collection $participations;


    #[ORM\ManyToOne(inversedBy: 'cheval')]
    private ?Entreprise $entreprise = null;


    /**
     * @var Collection<int, Deplacement>
     */
    #[ORM\ManyToMany(targetEntity: Deplacement::class, inversedBy: 'chevaux')]
    private Collection $deplacements;

    /**
     * @var Collection<int, ChevalProduit>
     */
    #[ORM\OneToMany(targetEntity: ChevalProduit::class, mappedBy: 'cheval')]
    private Collection $chevalProduits;

    /**
     * @var Collection<int, ChevalProprietaire>
     */
    #[ORM\OneToMany(
        targetEntity: ChevalProprietaire::class,
        mappedBy: 'cheval',
        cascade: ['persist', 'remove'], // <- ajoute le cascade ici
        orphanRemoval: true // optionnel mais pratique pour supprimer via le formulaire
    )]
    private Collection $chevalProprietaires;


    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->deplacements = new ArrayCollection();
        $this->chevalProduits = new ArrayCollection();
        $this->chevalProprietaires = new ArrayCollection();
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

    public function getRace(): ?string
    {
        return $this->race;
    }

    public function setRace(string $race): static
    {
        $this->race = $race;

        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(string $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTime $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }


    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setCheval($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getCheval() === $this) {
                $participation->setCheval(null);
            }
        }

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

    /**
     * @return Collection<int, Deplacement>
     */
    public function getDeplacement(): Collection
    {
        return $this->deplacements;
    }

    public function addDeplacement(Deplacement $deplacement): static
    {
        if (!$this->deplacements->contains($deplacement)) {
            $this->deplacements->add($deplacement);
        }

        return $this;
    }

    public function removeDeplacement(Deplacement $deplacement): static
    {
        $this->deplacements->removeElement($deplacement);

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
            $chevalProduit->setCheval($this);
        }

        return $this;
    }

    public function removeChevalProduit(ChevalProduit $chevalProduit): static
    {
        if ($this->chevalProduits->removeElement($chevalProduit)) {
            // set the owning side to null (unless already changed)
            if ($chevalProduit->getCheval() === $this) {
                $chevalProduit->setCheval(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ChevalProprietaire>
     */
    public function getChevalProprietaires(): Collection
    {
        return $this->chevalProprietaires;
    }

    public function addChevalProprietaire(ChevalProprietaire $chevalProprietaire): static
    {
        if (!$this->chevalProprietaires->contains($chevalProprietaire)) {
            $this->chevalProprietaires->add($chevalProprietaire);
            $chevalProprietaire->setCheval($this);
        }

        return $this;
    }

    public function removeChevalProprietaire(ChevalProprietaire $chevalProprietaire): static
    {
        if ($this->chevalProprietaires->removeElement($chevalProprietaire)) {
            // set the owning side to null (unless already changed)
            if ($chevalProprietaire->getCheval() === $this) {
                $chevalProprietaire->setCheval(null);
            }
        }

        return $this;
    }

    public function validatePourcentages(ExecutionContextInterface $context): void
    {
        $total = 0;


        foreach ($this->getChevalProprietaires() as $cp) {
            $total += (float) $cp->getPourcentage();
        }


        if ($total > 100) {
            $context
                ->buildViolation('Le total des pourcentages ne peut pas dépasser 100 % (actuellement {{ total }} %).')
                ->setParameter('{{ total }}', number_format($total, 2, ',', ' '))
                ->atPath('chevalProprietaires')
                ->addViolation();
        }
    }
}