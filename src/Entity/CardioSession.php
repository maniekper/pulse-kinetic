<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class CardioSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cardioSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?WorkoutSession $workoutSession = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Machine $machine = null;

    #[ORM\Column(nullable: true)]
    private ?int $durationMinutes = null;

    // NOWE POLE: Prędkość
#[ORM\Column(nullable: true)]
private ?float $speed = null;

// NOWE POLE: Pochylenie / Poziom trudności
#[ORM\Column(nullable: true)]
private ?float $incline = null;

    #[ORM\Column(nullable: true)]
    private ?float $distance = null;

    #[ORM\Column(nullable: true)]
    private ?int $averageHeartRate = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxHeartRate = null;

    // --- GETTERY I SETTERY ---
    public function getId(): ?int { return $this->id; }
    
    public function getWorkoutSession(): ?WorkoutSession { return $this->workoutSession; }
    public function setWorkoutSession(?WorkoutSession $workoutSession): static { $this->workoutSession = $workoutSession; return $this; }

    public function getMachine(): ?Machine { return $this->machine; }
    public function setMachine(?Machine $machine): static { $this->machine = $machine; return $this; }

    public function getDurationMinutes(): ?int { return $this->durationMinutes; }
    public function setDurationMinutes(?int $durationMinutes): static { $this->durationMinutes = $durationMinutes; return $this; }

    public function getDistance(): ?float { return $this->distance; }
    public function setDistance(?float $distance): static { $this->distance = $distance; return $this; }

    public function getAverageHeartRate(): ?int { return $this->averageHeartRate; }
    public function setAverageHeartRate(?int $averageHeartRate): static { $this->averageHeartRate = $averageHeartRate; return $this; }

    public function getMaxHeartRate(): ?int { return $this->maxHeartRate; }
    public function setMaxHeartRate(?int $maxHeartRate): static { $this->maxHeartRate = $maxHeartRate; return $this; }

    public function getSpeed(): ?float { return $this->speed; }
    public function setSpeed(?float $speed): static { $this->speed = $speed; return $this; }

    public function getIncline(): ?float { return $this->incline; }
    public function setIncline(?float $incline): static { $this->incline = $incline; return $this; }
}