<?php

namespace App\Entity;

use App\Repository\StructureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructureRepository::class)]
class Structure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $rue = null;

    #[ORM\Column(length: 255)]
    private ?string $ville = null;

    #[ORM\Column(length: 255)]
    private ?string $cp = null;

    /**
     * @var Collection<int, Deplacement>
     */
    #[ORM\OneToMany(targetEntity: Deplacement::class, mappedBy: 'structure')]
    private Collection $deplacements;

    /**
     * @var Collection<int, DistanceStructure>
     */
    #[ORM\OneToMany(targetEntity: DistanceStructure::class, mappedBy: 'structure')]
    private Collection $structureDistance;


    public function __construct()
    {
        $this->deplacements = new ArrayCollection();
        $this->structureDistance = new ArrayCollection();
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

    public function getRue(): ?string
    {
        return $this->rue;
    }

    public function setRue(string $rue): static
    {
        $this->rue = $rue;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getCp(): ?string
    {
        return $this->cp;
    }

    public function setCp(string $cp): static
    {
        $this->cp = $cp;

        return $this;
    }

    /**
     * @return Collection<int, Deplacement>
     */
    public function getDeplacements(): Collection
    {
        return $this->deplacements;
    }

    public function addDeplacement(Deplacement $deplacement): static
    {
        if (!$this->deplacements->contains($deplacement)) {
            $this->deplacements->add($deplacement);
            $deplacement->setStructure($this);
        }

        return $this;
    }

    public function removeDeplacement(Deplacement $deplacement): static
    {
        if ($this->deplacements->removeElement($deplacement)) {
            // set the owning side to null (unless already changed)
            if ($deplacement->getStructure() === $this) {
                $deplacement->setStructure(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DistanceStructure>
     */
    public function getStructureDistance(): Collection
    {
        return $this->structureDistance;
    }

    public function addStructureDistance(DistanceStructure $structureDistance): static
    {
        if (!$this->structureDistance->contains($structureDistance)) {
            $this->structureDistance->add($structureDistance);
            $structureDistance->setStructure($this);
        }

        return $this;
    }

    public function removeStructureDistance(DistanceStructure $structureDistance): static
    {
        if ($this->structureDistance->removeElement($structureDistance)) {
            // set the owning side to null (unless already changed)
            if ($structureDistance->getStructure() === $this) {
                $structureDistance->setStructure(null);
            }
        }

        return $this;
    }

    public function getAdresseComplete(): string
    {
        return trim(sprintf(
            '%s %s %s',
            $this->getRue(),
            $this->getCp(),
            $this->getVille()
        ));
    }
}
