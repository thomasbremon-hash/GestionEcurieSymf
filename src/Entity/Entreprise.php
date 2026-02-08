<?php

namespace App\Entity;

use App\Repository\EntrepriseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
class Entreprise
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

    #[ORM\Column(length: 255)]
    private ?string $pays = null;

    #[ORM\Column(length: 255)]
    private ?string $siren = null;

    #[ORM\Column(length: 255)]
    private ?string $siret = null;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'entreprise')]
    private Collection $users;

    /**
     * @var Collection<int, Cheval>
     */
    #[ORM\OneToMany(targetEntity: Cheval::class, mappedBy: 'entreprise')]
    private Collection $cheval;

    /**
     * @var Collection<int, ProduitEntrepriseTaxes>
     */
    #[ORM\OneToMany(targetEntity: ProduitEntrepriseTaxes::class, mappedBy: 'entreprise')]
    private Collection $produitEntrepriseTaxe;

    /**
     * @var Collection<int, DistanceStructure>
     */
    #[ORM\OneToMany(targetEntity: DistanceStructure::class, mappedBy: 'entreprise')]
    private Collection $distanceEntreprise;

    /**
     * @var Collection<int, Deplacement>
     */
    #[ORM\OneToMany(targetEntity: Deplacement::class, mappedBy: 'entreprise')]
    private Collection $deplacement;

    /**
     * @var Collection<int, FacturationEntreprise>
     */
    #[ORM\OneToMany(targetEntity: FacturationEntreprise::class, mappedBy: 'entreprise')]
    private Collection $facturationEntreprises;

    #[ORM\Column(length: 255)]
    private ?string $numTVA = null;

    /**
     * @var Collection<int, FacturationUtilisateur>
     */
    #[ORM\OneToMany(targetEntity: FacturationUtilisateur::class, mappedBy: 'entreprise')]
    private Collection $ManyToOne;


    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->cheval = new ArrayCollection();
        $this->produitEntrepriseTaxe = new ArrayCollection();
        $this->distanceEntreprise = new ArrayCollection();
        $this->deplacement = new ArrayCollection();
        $this->facturationEntreprises = new ArrayCollection();
        $this->ManyToOne = new ArrayCollection();
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

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(string $pays): static
    {
        $this->pays = $pays;

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(string $siren): static
    {
        $this->siren = $siren;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addEntreprise($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeEntreprise($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Cheval>
     */
    public function getCheval(): Collection
    {
        return $this->cheval;
    }

    public function addCheval(Cheval $cheval): static
    {
        if (!$this->cheval->contains($cheval)) {
            $this->cheval->add($cheval);
            $cheval->setEntreprise($this);
        }

        return $this;
    }

    public function removeCheval(Cheval $cheval): static
    {
        if ($this->cheval->removeElement($cheval)) {
            // set the owning side to null (unless already changed)
            if ($cheval->getEntreprise() === $this) {
                $cheval->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProduitEntrepriseTaxes>
     */
    public function getProduitEntrepriseTaxe(): Collection
    {
        return $this->produitEntrepriseTaxe;
    }

    public function addProduitEntrepriseTaxe(ProduitEntrepriseTaxes $produitEntrepriseTaxe): static
    {
        if (!$this->produitEntrepriseTaxe->contains($produitEntrepriseTaxe)) {
            $this->produitEntrepriseTaxe->add($produitEntrepriseTaxe);
            $produitEntrepriseTaxe->setEntreprise($this);
        }

        return $this;
    }

    public function removeProduitEntrepriseTaxe(ProduitEntrepriseTaxes $produitEntrepriseTaxe): static
    {
        if ($this->produitEntrepriseTaxe->removeElement($produitEntrepriseTaxe)) {
            // set the owning side to null (unless already changed)
            if ($produitEntrepriseTaxe->getEntreprise() === $this) {
                $produitEntrepriseTaxe->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DistanceStructure>
     */
    public function getDistanceEntreprise(): Collection
    {
        return $this->distanceEntreprise;
    }

    public function addDistanceEntreprise(DistanceStructure $distanceEntreprise): static
    {
        if (!$this->distanceEntreprise->contains($distanceEntreprise)) {
            $this->distanceEntreprise->add($distanceEntreprise);
            $distanceEntreprise->setEntreprise($this);
        }

        return $this;
    }

    public function removeDistanceEntreprise(DistanceStructure $distanceEntreprise): static
    {
        if ($this->distanceEntreprise->removeElement($distanceEntreprise)) {
            // set the owning side to null (unless already changed)
            if ($distanceEntreprise->getEntreprise() === $this) {
                $distanceEntreprise->setEntreprise(null);
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

    /**
     * @return Collection<int, Deplacement>
     */
    public function getDeplacement(): Collection
    {
        return $this->deplacement;
    }

    public function addDeplacement(Deplacement $deplacement): static
    {
        if (!$this->deplacement->contains($deplacement)) {
            $this->deplacement->add($deplacement);
            $deplacement->setEntreprise($this);
        }

        return $this;
    }

    public function removeDeplacement(Deplacement $deplacement): static
    {
        if ($this->deplacement->removeElement($deplacement)) {
            // set the owning side to null (unless already changed)
            if ($deplacement->getEntreprise() === $this) {
                $deplacement->setEntreprise(null);
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
            $facturationEntreprise->setEntreprise($this);
        }

        return $this;
    }

    public function removeFacturationEntreprise(FacturationEntreprise $facturationEntreprise): static
    {
        if ($this->facturationEntreprises->removeElement($facturationEntreprise)) {
            // set the owning side to null (unless already changed)
            if ($facturationEntreprise->getEntreprise() === $this) {
                $facturationEntreprise->setEntreprise(null);
            }
        }

        return $this;
    }

    public function getNumTVA(): ?string
    {
        return $this->numTVA;
    }

    public function setNumTVA(string $numTVA): static
    {
        $this->numTVA = $numTVA;

        return $this;
    }

    /**
     * @return Collection<int, FacturationUtilisateur>
     */
    public function getManyToOne(): Collection
    {
        return $this->ManyToOne;
    }

    public function addManyToOne(FacturationUtilisateur $manyToOne): static
    {
        if (!$this->ManyToOne->contains($manyToOne)) {
            $this->ManyToOne->add($manyToOne);
            $manyToOne->setEntreprise($this);
        }

        return $this;
    }

    public function removeManyToOne(FacturationUtilisateur $manyToOne): static
    {
        if ($this->ManyToOne->removeElement($manyToOne)) {
            // set the owning side to null (unless already changed)
            if ($manyToOne->getEntreprise() === $this) {
                $manyToOne->setEntreprise(null);
            }
        }

        return $this;
    }
}
