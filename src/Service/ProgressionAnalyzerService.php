<?php

namespace App\Service;

use App\Entity\WorkoutSet;

class ProgressionAnalyzerService
{
    public function __construct(private WorkoutCalculatorService $calculator) {}

    public function analyzeSet(WorkoutSet $currentSet, int $targetReps, bool $isCompound = true): array
    {
        $weight = $currentSet->getWeight();
        $reps = $currentSet->getReps();
        $machine = $currentSet->getMachine();
        $user = $currentSet->getWorkoutSession()->getUser();

        $increment = $machine->getIncrementStep() ?? 2.5;

        // Pobieramy bazowy czas odpoczynku z bazy danych
        $targets = $this->calculator->calculateTargets($user, $machine);
        $baseRest = $targets['restSeconds'];

        $nextWeight = $weight;
        $restSeconds = $baseRest;

        if ($reps > $targetReps) {
            $nextWeight = $weight + $increment;
            $restSeconds = $baseRest;
            $message = sprintf('Przebicie celu! Zrobiłeś %d powt. (cel: %d). Dokładamy na %.1f kg w następnej serii.', $reps, $targetReps, $nextWeight);
        } elseif ($reps === $targetReps) {
            $nextWeight = $weight;
            $restSeconds = $baseRest;
            $message = sprintf('Cel osiągnięty (%d/%d). Zostań przy %.1f kg, utrwal ten wynik i zregeneruj siły.', $reps, $targetReps, $nextWeight);
        } else {
            $shortfall = $targetReps - $reps;
            if ($shortfall <= 2) {
                $nextWeight = $weight;
                // Inteligentny bufor 30 sekund przy minimalnym braku powtórzeń
                $restSeconds = $baseRest + 30;
                $message = sprintf('Ciężka walka (%d/%d). Dorzucamy ekstra 30s oddechu. Zostań przy %.1f kg i spróbuj dociągnąć.', $reps, $targetReps, $weight);
            } else {
                $nextWeight = max(0, $weight - $increment);
                $restSeconds = $baseRest + 15;
                $message = sprintf('Spadek mocy. Zmniejszamy ciężar do %.1f kg i dajemy chwilę na złapanie oddechu.', $nextWeight);
            }
        }

        return [
            'next_weight' => $nextWeight,
            'rest_seconds' => $restSeconds,
            'message' => $message
        ];
    }
}
