<?php

namespace App\Entity;

use App\Repository\RoutineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoutineRepository::class)]
class Routine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'routines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, RoutineMachine>
     */
    #[ORM\OneToMany(targetEntity: RoutineMachine::class, mappedBy: 'routine')]
    private Collection $routineMachines;

    /**
     * @var Collection<int, WorkoutSession>
     */
    #[ORM\OneToMany(targetEntity: WorkoutSession::class, mappedBy: 'routine')]
    private Collection $workoutSessions;

    public function __construct()
    {
        $this->routineMachines = new ArrayCollection();
        $this->workoutSessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    /**
     * @return Collection<int, RoutineMachine>
     */
    public function getRoutineMachines(): Collection
    {
        return $this->routineMachines;
    }

    public function addRoutineMachine(RoutineMachine $routineMachine): static
    {
        if (!$this->routineMachines->contains($routineMachine)) {
            $this->routineMachines->add($routineMachine);
            $routineMachine->setRoutine($this);
        }

        return $this;
    }

    public function removeRoutineMachine(RoutineMachine $routineMachine): static
    {
        if ($this->routineMachines->removeElement($routineMachine)) {
            // set the owning side to null (unless already changed)
            if ($routineMachine->getRoutine() === $this) {
                $routineMachine->setRoutine(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, WorkoutSession>
     */
    public function getWorkoutSessions(): Collection
    {
        return $this->workoutSessions;
    }

    public function addWorkoutSession(WorkoutSession $workoutSession): static
    {
        if (!$this->workoutSessions->contains($workoutSession)) {
            $this->workoutSessions->add($workoutSession);
            $workoutSession->setRoutine($this);
        }

        return $this;
    }

    public function removeWorkoutSession(WorkoutSession $workoutSession): static
    {
        if ($this->workoutSessions->removeElement($workoutSession)) {
            // set the owning side to null (unless already changed)
            if ($workoutSession->getRoutine() === $this) {
                $workoutSession->setRoutine(null);
            }
        }

        return $this;
    }
}
