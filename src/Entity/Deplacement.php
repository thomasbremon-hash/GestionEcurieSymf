<?php

namespace App\Entity;

use App\Repository\DeplacementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeplacementRepository::class)]
class Deplacement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?int $distance = null;

    #[ORM\ManyToOne(inversedBy: 'deplacements')]
    private ?Structure $structure = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'deplacement')]
    private ?Entreprise $entreprise = null;

    /**
     * @var Collection<int, Cheval>
     */
    #[ORM\ManyToMany(targetEntity: Cheval::class, inversedBy: 'deplacements')]
    #[ORM\JoinTable(name: 'deplacement_cheval')]
    private Collection $chevaux;


    public function __construct()
    {
        $this->chevaux = new ArrayCollection();
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

    public function getDistance(): ?int
    {
        return $this->distance;
    }

    public function setDistance(int $distance): static
    {
        $this->distance = $distance;

        return $this;
    }

    public function getStructure(): ?Structure
    {
        return $this->structure;
    }

    public function setStructure(?Structure $structure): static
    {
        $this->structure = $structure;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

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
     * @return Collection<int, Cheval>
     */
    public function getChevaux(): Collection
    {
        return $this->chevaux;
    }

    public function addCheval(Cheval $cheval): static
    {
        if (!$this->chevaux->contains($cheval)) {
            $this->chevaux->add($cheval);
            $cheval->addDeplacement($this);
        }

        return $this;
    }

    public function removeCheval(Cheval $cheval): static
    {
        if ($this->chevaux->removeElement($cheval)) {
            $cheval->removeDeplacement($this);
        }

        return $this;
    }
}
