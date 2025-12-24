<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cheval $cheval = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $temps = null;

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

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getTemps(): ?\DateTimeInterface
    {
        return $this->temps;
    }

    public function setTemps(?\DateTimeInterface $temps): static
    {
        $this->temps = $temps;

        return $this;
    }
}
