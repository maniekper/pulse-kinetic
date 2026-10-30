<?php

namespace App\Entity;

use App\Repository\WorkoutExerciseRepository;
use App\Entity\Machine;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkoutExerciseRepository::class)]
class WorkoutExercise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'workoutExercises')]
    #[ORM\JoinColumn(nullable: false)]
    private ?WorkoutPlan $workoutPlan = null;

    #[ORM\ManyToOne(targetEntity: Machine::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Machine $machine = null;

    #[ORM\Column]
    private ?int $dayNumber = null;

    #[ORM\Column]
    private ?int $sets = null;

    #[ORM\Column(length: 255)]
    private ?string $reps = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkoutPlan(): ?WorkoutPlan
    {
        return $this->workoutPlan;
    }

    public function setWorkoutPlan(?WorkoutPlan $workoutPlan): static
    {
        $this->workoutPlan = $workoutPlan;

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

    public function getDayNumber(): ?int
    {
        return $this->dayNumber;
    }

    public function setDayNumber(int $dayNumber): static
    {
        $this->dayNumber = $dayNumber;

        return $this;
    }

    public function getSets(): ?int
    {
        return $this->sets;
    }

    public function setSets(int $sets): static
    {
        $this->sets = $sets;

        return $this;
    }

    public function getReps(): ?string
    {
        return $this->reps;
    }

    public function setReps(string $reps): static
    {
        $this->reps = $reps;

        return $this;
    }
}