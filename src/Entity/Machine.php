<?php

namespace App\Entity;

use App\Repository\MachineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MachineRepository::class)]
class Machine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $tips = null;

    #[ORM\Column(length: 255)]
    private ?string $imageStart = null;

    #[ORM\Column(length: 255)]
    private ?string $imageEnd = null;

    #[ORM\Column(length: 255)]
    private ?string $category = null;

    #[ORM\Column(length: 255)]
    private ?string $mechanicType = null;

    /**
     * @var Collection<int, Muscle>
     */
    #[ORM\ManyToMany(targetEntity: Muscle::class, inversedBy: 'primaryMachines')]
    #[ORM\JoinTable(name: 'machine_primary_muscle')]
    private Collection $primaryMuscles;

    /**
     * @var Collection<int, Muscle>
     */
    #[ORM\ManyToMany(targetEntity: Muscle::class, inversedBy: 'secondaryMachines')]
    #[ORM\JoinTable(name: 'machine_secondary_muscle')]
    private Collection $secondaryMuscles;

    /**
     * @var Collection<int, MachineRecord>
     */
    #[ORM\OneToMany(targetEntity: MachineRecord::class, mappedBy: 'machine')]
    private Collection $machineRecords;

    /**
     * @var Collection<int, RoutineMachine>
     */
    #[ORM\OneToMany(targetEntity: RoutineMachine::class, mappedBy: 'machine')]
    private Collection $routineMachines;

    /**
     * @var Collection<int, WorkoutSet>
     */
    #[ORM\OneToMany(targetEntity: WorkoutSet::class, mappedBy: 'machine')]
    private Collection $workoutSets;

    public function __construct()
    {
        $this->primaryMuscles = new ArrayCollection();
        $this->secondaryMuscles = new ArrayCollection();
        $this->machineRecords = new ArrayCollection();
        $this->routineMachines = new ArrayCollection();
        $this->workoutSets = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTips(): ?string
    {
        return $this->tips;
    }

    public function setTips(string $tips): static
    {
        $this->tips = $tips;

        return $this;
    }

    public function getImageStart(): ?string
    {
        return $this->imageStart;
    }

    public function setImageStart(string $imageStart): static
    {
        $this->imageStart = $imageStart;

        return $this;
    }

    public function getImageEnd(): ?string
    {
        return $this->imageEnd;
    }

    public function setImageEnd(string $imageEnd): static
    {
        $this->imageEnd = $imageEnd;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getMechanicType(): ?string
    {
        return $this->mechanicType;
    }

    public function setMechanicType(string $mechanicType): static
    {
        $this->mechanicType = $mechanicType;

        return $this;
    }

    /**
     * @return Collection<int, Muscle>
     */
    public function getPrimaryMuscles(): Collection
    {
        return $this->primaryMuscles;
    }

    public function addPrimaryMuscle(Muscle $primaryMuscle): static
    {
        if (!$this->primaryMuscles->contains($primaryMuscle)) {
            $this->primaryMuscles->add($primaryMuscle);
        }

        return $this;
    }

    public function removePrimaryMuscle(Muscle $primaryMuscle): static
    {
        $this->primaryMuscles->removeElement($primaryMuscle);

        return $this;
    }

    /**
     * @return Collection<int, Muscle>
     */
    public function getSecondaryMuscles(): Collection
    {
        return $this->secondaryMuscles;
    }

    public function addSecondaryMuscle(Muscle $secondaryMuscle): static
    {
        if (!$this->secondaryMuscles->contains($secondaryMuscle)) {
            $this->secondaryMuscles->add($secondaryMuscle);
        }

        return $this;
    }

    public function removeSecondaryMuscle(Muscle $secondaryMuscle): static
    {
        $this->secondaryMuscles->removeElement($secondaryMuscle);

        return $this;
    }

    /**
     * @return Collection<int, MachineRecord>
     */
    public function getMachineRecords(): Collection
    {
        return $this->machineRecords;
    }

    public function addMachineRecord(MachineRecord $machineRecord): static
    {
        if (!$this->machineRecords->contains($machineRecord)) {
            $this->machineRecords->add($machineRecord);
            $machineRecord->setMachine($this);
        }

        return $this;
    }

    public function removeMachineRecord(MachineRecord $machineRecord): static
    {
        if ($this->machineRecords->removeElement($machineRecord)) {
            // set the owning side to null (unless already changed)
            if ($machineRecord->getMachine() === $this) {
                $machineRecord->setMachine(null);
            }
        }

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
            $routineMachine->setMachine($this);
        }

        return $this;
    }

    public function removeRoutineMachine(RoutineMachine $routineMachine): static
    {
        if ($this->routineMachines->removeElement($routineMachine)) {
            // set the owning side to null (unless already changed)
            if ($routineMachine->getMachine() === $this) {
                $routineMachine->setMachine(null);
            }
        }

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
            $workoutSet->setMachine($this);
        }

        return $this;
    }

    public function removeWorkoutSet(WorkoutSet $workoutSet): static
    {
        if ($this->workoutSets->removeElement($workoutSet)) {
            // set the owning side to null (unless already changed)
            if ($workoutSet->getMachine() === $this) {
                $workoutSet->setMachine(null);
            }
        }

        return $this;
    }
}
