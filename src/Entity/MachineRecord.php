<?php

namespace App\Entity;

use App\Repository\MachineRecordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MachineRecordRepository::class)]
class MachineRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $maxWeight = null;

    #[ORM\Column]
    private ?int $maxRepsAtMaxWeight = null;

    #[ORM\Column]
    private ?\DateTime $achievedAt = null;

    #[ORM\ManyToOne(inversedBy: 'machineRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'machineRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Machine $machine = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaxWeight(): ?float
    {
        return $this->maxWeight;
    }

    public function setMaxWeight(float $maxWeight): static
    {
        $this->maxWeight = $maxWeight;

        return $this;
    }

    public function getMaxRepsAtMaxWeight(): ?int
    {
        return $this->maxRepsAtMaxWeight;
    }

    public function setMaxRepsAtMaxWeight(int $maxRepsAtMaxWeight): static
    {
        $this->maxRepsAtMaxWeight = $maxRepsAtMaxWeight;

        return $this;
    }

    public function getAchievedAt(): ?\DateTime
    {
        return $this->achievedAt;
    }

    public function setAchievedAt(\DateTime $achievedAt): static
    {
        $this->achievedAt = $achievedAt;

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
