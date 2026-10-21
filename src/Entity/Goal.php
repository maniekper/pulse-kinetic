<?php

namespace App\Entity;

use App\Repository\GoalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GoalRepository::class)]
class Goal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $baseCompoundRepsMin = null;

    #[ORM\Column]
    private ?int $baseCompoundRepsMax = null;

    #[ORM\Column]
    private ?int $baseIsolationRepsMin = null;

    #[ORM\Column]
    private ?int $baseIsolationRepsMax = null;

    #[ORM\Column]
    private ?int $restCompoundSeconds = null;

    #[ORM\Column]
    private ?int $restIsolationSeconds = null;

    #[ORM\Column]
    private ?int $restBetweenMachinesSeconds = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'currentGoal')]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
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

    public function getBaseCompoundRepsMin(): ?int
    {
        return $this->baseCompoundRepsMin;
    }

    public function setBaseCompoundRepsMin(int $baseCompoundRepsMin): static
    {
        $this->baseCompoundRepsMin = $baseCompoundRepsMin;

        return $this;
    }

    public function getBaseCompoundRepsMax(): ?int
    {
        return $this->baseCompoundRepsMax;
    }

    public function setBaseCompoundRepsMax(int $baseCompoundRepsMax): static
    {
        $this->baseCompoundRepsMax = $baseCompoundRepsMax;

        return $this;
    }

    public function getBaseIsolationRepsMin(): ?int
    {
        return $this->baseIsolationRepsMin;
    }

    public function setBaseIsolationRepsMin(int $baseIsolationRepsMin): static
    {
        $this->baseIsolationRepsMin = $baseIsolationRepsMin;

        return $this;
    }

    public function getBaseIsolationRepsMax(): ?int
    {
        return $this->baseIsolationRepsMax;
    }

    public function setBaseIsolationRepsMax(int $baseIsolationRepsMax): static
    {
        $this->baseIsolationRepsMax = $baseIsolationRepsMax;

        return $this;
    }

    public function getRestCompoundSeconds(): ?int
    {
        return $this->restCompoundSeconds;
    }

    public function setRestCompoundSeconds(int $restCompoundSeconds): static
    {
        $this->restCompoundSeconds = $restCompoundSeconds;

        return $this;
    }

    public function getRestIsolationSeconds(): ?int
    {
        return $this->restIsolationSeconds;
    }

    public function setRestIsolationSeconds(int $restIsolationSeconds): static
    {
        $this->restIsolationSeconds = $restIsolationSeconds;

        return $this;
    }

    public function getRestBetweenMachinesSeconds(): ?int
    {
        return $this->restBetweenMachinesSeconds;
    }

    public function setRestBetweenMachinesSeconds(int $restBetweenMachinesSeconds): static
    {
        $this->restBetweenMachinesSeconds = $restBetweenMachinesSeconds;

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
            $user->setCurrentGoal($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getCurrentGoal() === $this) {
                $user->setCurrentGoal(null);
            }
        }

        return $this;
    }
}
