<?php

namespace App\Entity;

use App\Repository\MuscleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MuscleRepository::class)]
class Muscle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Machine>
     */
    #[ORM\ManyToMany(targetEntity: Machine::class, mappedBy: 'primaryMuscles')]
    private Collection $primaryMachines;

    /**
     * @var Collection<int, Machine>
     */
    #[ORM\ManyToMany(targetEntity: Machine::class, mappedBy: 'secondaryMuscles')]
    private Collection $secondaryMachines;

    public function __construct()
    {
        $this->primaryMachines = new ArrayCollection();
        $this->secondaryMachines = new ArrayCollection();
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

    /**
     * @return Collection<int, Machine>
     */
    public function getPrimaryMachines(): Collection
    {
        return $this->primaryMachines;
    }

    public function addPrimaryMachine(Machine $primaryMachine): static
    {
        if (!$this->primaryMachines->contains($primaryMachine)) {
            $this->primaryMachines->add($primaryMachine);
            $primaryMachine->addPrimaryMuscle($this);
        }

        return $this;
    }

    public function removePrimaryMachine(Machine $primaryMachine): static
    {
        if ($this->primaryMachines->removeElement($primaryMachine)) {
            $primaryMachine->removePrimaryMuscle($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Machine>
     */
    public function getSecondaryMachines(): Collection
    {
        return $this->secondaryMachines;
    }

    public function addSecondaryMachine(Machine $secondaryMachine): static
    {
        if (!$this->secondaryMachines->contains($secondaryMachine)) {
            $this->secondaryMachines->add($secondaryMachine);
            $secondaryMachine->addSecondaryMuscle($this);
        }

        return $this;
    }

    public function removeSecondaryMachine(Machine $secondaryMachine): static
    {
        if ($this->secondaryMachines->removeElement($secondaryMachine)) {
            $secondaryMachine->removeSecondaryMuscle($this);
        }

        return $this;
    }
}
