<?php

namespace App\Service;

use App\Entity\WorkoutSet;

class ProgressionAnalyzerService
{
    public function analyzeSet(WorkoutSet $currentSet, int $targetReps, bool $isCompound = true): array
    {
        $weight = $currentSet->getWeight();
        $reps = $currentSet->getReps();
        
        // Pobieramy skok ciężaru prosto z maszyny (domyślnie 2.5 kg)
        $increment = $currentSet->getMachine()->getIncrementStep() ?? 2.5;

        $nextWeight = $weight;
        $restSeconds = $isCompound ? 120 : 90;

        if ($reps > $targetReps) {
            $nextWeight = $weight + $increment;
            $message = sprintf('Przebicie celu! Zrobiłeś %d powt. (cel: %d). Jesteś gotowy, dokładamy na %.1f kg w następnej serii.', $reps, $targetReps, $nextWeight);
            $restSeconds = $isCompound ? 180 : 120; 
            
        } elseif ($reps === $targetReps) {
            $nextWeight = $weight; 
            $message = sprintf('Cel osiągnięty (%d/%d). Zostań przy %.1f kg, utrwal ten wynik i zregeneruj układ nerwowy.', $reps, $targetReps, $nextWeight);
            $restSeconds = $isCompound ? 150 : 90;
            
        } else {
            $shortfall = $targetReps - $reps;
            if ($shortfall <= 2) {
                $nextWeight = $weight;
                $message = sprintf('Blisko celu (%d/%d). Zostań przy %.1f kg i spróbuj dociągnąć w następnej serii.', $reps, $targetReps, $weight);
                $restSeconds = $isCompound ? 180 : 120;
            } else {
                $nextWeight = max(0, $weight - $increment);
                $message = sprintf('Wyraźny spadek mocy. Zmniejszamy ciężar do %.1f kg, żeby zachować formę i bezpieczeństwo.', $nextWeight);
                $restSeconds = $isCompound ? 120 : 90;
            }
        }

        return [
            'next_weight' => $nextWeight,
            'rest_seconds' => $restSeconds,
            'message' => $message
        ];
    }
}