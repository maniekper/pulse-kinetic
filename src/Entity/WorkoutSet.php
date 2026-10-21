<?php

namespace App\Entity;

use App\Repository\WorkoutSetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkoutSetRepository::class)]
class WorkoutSet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $setNumber = null;

    #[ORM\Column]
    private ?float $weight = null;

    #[ORM\Column]
    private ?int $reps = null;

    #[ORM\Column]
    private ?bool $isCompleted = null;

    #[ORM\Column]
    private ?\DateTime $loggedAt = null;

    #[ORM\ManyToOne(inversedBy: 'workoutSets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?WorkoutSession $workoutSession = null;

    #[ORM\ManyToOne(inversedBy: 'workoutSets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Machine $machine = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSetNumber(): ?int
    {
        return $this->setNumber;
    }

    public function setSetNumber(int $setNumber): static
    {
        $this->setNumber = $setNumber;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getReps(): ?int
    {
        return $this->reps;
    }

    public function setReps(int $reps): static
    {
        $this->reps = $reps;

        return $this;
    }

    public function isCompleted(): ?bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): static
    {
        $this->isCompleted = $isCompleted;

        return $this;
    }

    public function getLoggedAt(): ?\DateTime
    {
        return $this->loggedAt;
    }

    public function setLoggedAt(\DateTime $loggedAt): static
    {
        $this->loggedAt = $loggedAt;

        return $this;
    }

    public function getWorkoutSession(): ?WorkoutSession
    {
        return $this->workoutSession;
    }

    public function setWorkoutSession(?WorkoutSession $workoutSession): static
    {
        $this->workoutSession = $workoutSession;

        return $this;
    }

    public function getMachine(): ?Machine
    {
        return $this->machine;
    }

    public function setMachine(?Machine $machine): static
    {
        $this->machine = $machine;

        return $this;
    }
}
