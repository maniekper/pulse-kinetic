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

        $userSteps = method_exists($user, 'getExerciseIncrementSteps')
            ? $user->getExerciseIncrementSteps()
            : [];
        $machineKey = (string) $machine->getId();
        $increment = isset($userSteps[$machineKey]) && (float) $userSteps[$machineKey] > 0
            ? (float) $userSteps[$machineKey]
            : ($machine->getIncrementStep() ?? 2.5);

        $goalName = strtolower((string) ($user->getCurrentGoal()?->getName() ?? ''));
        $isCutOrTone = str_contains($goalName, 'redukc') || str_contains($goalName, 'rzeźb') || str_contains($goalName, 'rzez');
        $isStrength = str_contains($goalName, 'sił') || str_contains($goalName, 'sil');

        // Pobieramy bazowy czas odpoczynku z bazy danych
        $targets = $this->calculator->calculateTargets($user, $machine);
        $baseRest = $targets['restSeconds'];

        $nextWeight = $weight;
        $restSeconds = $baseRest;

        if ($reps > $targetReps) {
            $extraReps = $reps - $targetReps;

            $baseSteps = match (true) {
                $extraReps >= 5 => 3,
                $extraReps >= 3 => 2,
                default => 1,
            };

            $maxSteps = match (true) {
                $isCutOrTone => 2,
                $isStrength => 2,
                default => 3, // masa / hipertrofia
            };

            $stepsToAdd = min($baseSteps, $maxSteps);
            $safeIncrement = $increment > 0 ? $increment : 2.5;
            $rawNextWeight = $weight + ($stepsToAdd * $safeIncrement);

            $estimated1Rm = $weight * (1 + ($reps / 30));
            $maxSafeRatio = match (true) {
                $isCutOrTone => $isCompound ? 0.80 : 0.75,
                $isStrength => $isCompound ? 0.85 : 0.78,
                default => $isCompound ? 0.85 : 0.80,
            };

            $safeCap = $estimated1Rm * $maxSafeRatio;
            $cappedWeight = min($rawNextWeight, max($weight + $safeIncrement, $safeCap));

            $nextWeight = round($cappedWeight / $safeIncrement) * $safeIncrement;
            if ($nextWeight <= $weight) {
                $nextWeight = $weight + $safeIncrement;
            }

            $restSeconds = $baseRest;
            $message = sprintf(
                'Przebicie celu (+%d powt.: %d/%d). Bezpiecznie podnosimy na %.1f kg (krok: %.1f kg).',
                $extraReps,
                $reps,
                $targetReps,
                $nextWeight,
                $safeIncrement
            );
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
            'message' => $message,
        ];
    }
}
