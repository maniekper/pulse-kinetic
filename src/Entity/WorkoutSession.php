<?php

namespace App\Entity;

use App\Repository\WorkoutSessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkoutSessionRepository::class)]
class WorkoutSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $endedAt = null;

    #[ORM\Column(nullable: true)]
    private ?float $totalTonnage = null;

    #[ORM\ManyToOne(inversedBy: 'workoutSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'workoutSessions')]
    private ?Routine $routine = null;

    /**
     * @var Collection<int, WorkoutSet>
     */
    #[ORM\OneToMany(targetEntity: WorkoutSet::class, mappedBy: 'workoutSession')]
    private Collection $workoutSets;

    public function __construct()
    {
        $this->workoutSets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartedAt(): ?\DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTime $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTime
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTime $endedAt): static
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getTotalTonnage(): ?float
    {
        return $this->totalTonnage;
    }

    public function setTotalTonnage(?float $totalTonnage): static
    {
        $this->totalTonnage = $totalTonnage;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRoutine(): ?Routine
    {
        return $this->routine;
    }

    public function setRoutine(?Routine $routine): static
    {
        $this->routine = $routine;

        return $this;
    }

    /**
     * @return Collection<int, WorkoutSet>
     */
    public function getWorkoutSets(): Collection
    {
        return $this->workoutSets;
    }

    public function addWorkoutSet(WorkoutSet $workoutSet): static
    {
        if (!$this->workoutSets->contains($workoutSet)) {
            $this->workoutSets->add($workoutSet);
            $workoutSet->setWorkoutSession($this);
        }

        return $this;
    }

    public function removeWorkoutSet(WorkoutSet $workoutSet): static
    {
        if ($this->workoutSets->removeElement($workoutSet)) {
            // set the owning side to null (unless already changed)
            if ($workoutSet->getWorkoutSession() === $this) {
                $workoutSet->setWorkoutSession(null);
            }
        }

        return $this;
    }
}
