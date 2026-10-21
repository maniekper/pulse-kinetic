<?php

namespace App\Entity;

use App\Repository\RoutineMachineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoutineMachineRepository::class)]
class RoutineMachine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $sortOrder = null;

    #[ORM\ManyToOne(inversedBy: 'routineMachines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Routine $routine = null;

    #[ORM\ManyToOne(inversedBy: 'routineMachines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Machine $machine = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

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
