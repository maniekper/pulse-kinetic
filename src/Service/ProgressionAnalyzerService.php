<?php

namespace App\Service;

use App\Entity\WorkoutSet;

class ProgressionAnalyzerService
{
    // Wstrzykujemy kalkulator, aby mieć dostęp do ustawień z encji Goal
    public function __construct(private WorkoutCalculatorService $calculator) {}

    public function analyzeSet(WorkoutSet $currentSet, int $targetReps, bool $isCompound = true): array
    {
        $weight = $currentSet->getWeight();
        $reps = $currentSet->getReps();
        $machine = $currentSet->getMachine();
        $user = $currentSet->getWorkoutSession()->getUser();

        $increment = $machine->getIncrementStep() ?? 2.5;

        // Pobieramy precyzyjne dane o przerwach dla aktywnego Celu operatora
        $targets = $this->calculator->calculateTargets($user, $machine);
        $baseRest = $targets['restSeconds'];

        $nextWeight = $weight;

        if ($reps > $targetReps) {
            $nextWeight = $weight + $increment;
            $message = sprintf('Przebicie celu! Zrobiłeś %d powt. (cel: %d). Jesteś gotowy, dokładamy na %.1f kg w następnej serii.', $reps, $targetReps, $nextWeight);
            // Dodajemy 30 sekund ekstra do bazowego czasu za pobicie rekordu (układ nerwowy potrzebuje oddechu)
            $restSeconds = $baseRest + 30;
        } elseif ($reps === $targetReps) {
            $nextWeight = $weight;
            $message = sprintf('Cel osiągnięty (%d/%d). Zostań przy %.1f kg, utrwal ten wynik i zregeneruj układ nerwowy.', $reps, $targetReps, $nextWeight);
            $restSeconds = $baseRest;
        } else {
            $shortfall = $targetReps - $reps;
            if ($shortfall <= 2) {
                $nextWeight = $weight;
                $message = sprintf('Blisko celu (%d/%d). Zostań przy %.1f kg i spróbuj dociągnąć w następnej serii.', $reps, $targetReps, $weight);
                $restSeconds = $baseRest + 30; // Zwiększone zmęczenie wymaga dłuższej przerwy przed kolejną próbą
            } else {
                $nextWeight = max(0, $weight - $increment);
                $message = sprintf('Wyraźny spadek mocy. Zmniejszamy ciężar do %.1f kg, żeby zachować formę i bezpieczeństwo.', $nextWeight);
                $restSeconds = $baseRest;
            }
        }

        return [
            'next_weight' => $nextWeight,
            'rest_seconds' => $restSeconds,
            'message' => $message
        ];
    }
}
