<?php

namespace App\Service;

use App\Entity\WorkoutSet;

class ProgressionAnalyzerService
{
    public function __construct(private WorkoutCalculatorService $calculator) {}

    public function analyzeSet(WorkoutSet $currentSet, int $targetReps, bool $isCompound = true): array
    {
        $weight = (float) $currentSet->getWeight();
        $reps = (int) $currentSet->getReps();
        $machine = $currentSet->getMachine();
        $user = $currentSet->getWorkoutSession()->getUser();

        $increment = $this->resolveEffectiveIncrement($user, $machine, $weight);
        $safeIncrement = $increment > 0 ? $increment : 2.5;

        $goalName = strtolower((string) ($user->getCurrentGoal()?->getName() ?? ''));
        $isCutOrTone = str_contains($goalName, 'redukc')
            || str_contains($goalName, 'rzeźb')
            || str_contains($goalName, 'rzez');
        $isStrength = str_contains($goalName, 'sił')
            || str_contains($goalName, 'sil');

        // Pobieramy bazowy czas odpoczynku z bazy danych
        $targets = $this->calculator->calculateTargets($user, $machine);
        $baseRest = $targets['restSeconds'];

        $nextWeight = $weight;
        $restSeconds = $baseRest;

        if ($reps > $targetReps) {
            $extraReps = $reps - $targetReps;

            // Przy +1 oraz +2 powtórzeniach ponad cel ZAWSZE dokładamy tylko 1 najmniejszy krok.
            // Przeliczanie na większy skok (2 lub 3 kroki) uruchamiamy dopiero od +3 powtórzeń zapasu.
            if ($extraReps <= 2) {
                $nextWeight = $weight + $safeIncrement;
            } else {
                $baseSteps = match (true) {
                    $extraReps >= 5 => 3,
                    default => 2, // 3 lub 4 powtórzenia ponad cel
                };

                $maxSteps = match (true) {
                    $isCutOrTone => 2,
                    $isStrength => 2,
                    default => 3, // masa / hipertrofia
                };

                $stepsToAdd = min($baseSteps, $maxSteps);
                $rawNextWeight = $weight + ($stepsToAdd * $safeIncrement);

                // Bezpiecznik 1RM służy wyłącznie do ograniczenia zbyt dużego skoku, nigdy do jego zawyżania
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
            }

            $nextWeight = round($nextWeight, 2);
            $restSeconds = $baseRest;

            $message = $extraReps <= 2
                ? sprintf(
                    'Lekki zapas (+%d powt.: %d/%d). Dokładamy minimalny krok +%.1f kg → %.1f kg.',
                    $extraReps,
                    $reps,
                    $targetReps,
                    $safeIncrement,
                    $nextWeight
                )
                : sprintf(
                    'Duży zapas (+%d powt.: %d/%d). Przeliczamy obciążenie na %.1f kg (krok: %.1f kg).',
                    $extraReps,
                    $reps,
                    $targetReps,
                    $nextWeight,
                    $safeIncrement
                );
        } elseif ($reps === $targetReps) {
            $nextWeight = $weight;
            $restSeconds = $baseRest;
            $message = sprintf(
                'Cel osiągnięty (%d/%d). Zostań przy %.1f kg, utrwal ten wynik i zregeneruj siły.',
                $reps,
                $targetReps,
                $nextWeight
            );
        } else {
            $shortfall = $targetReps - $reps;
            if ($shortfall <= 2) {
                $nextWeight = $weight;
                // Inteligentny bufor 30 sekund przy minimalnym braku powtórzeń
                $restSeconds = $baseRest + 30;
                $message = sprintf(
                    'Ciężka walka (%d/%d). Dorzucamy ekstra 30s oddechu. Zostań przy %.1f kg i spróbuj dociągnąć.',
                    $reps,
                    $targetReps,
                    $weight
                );
            } else {
                $nextWeight = max(0, round($weight - $safeIncrement, 2));
                $restSeconds = $baseRest + 15;
                $message = sprintf(
                    'Spadek mocy. Zmniejszamy ciężar do %.1f kg i dajemy chwilę na złapanie oddechu.',
                    $nextWeight
                );
            }
        }

        return [
            'next_weight' => $nextWeight,
            'rest_seconds' => $restSeconds,
            'message' => $message,
        ];
    }

    private function resolveEffectiveIncrement(object $user, object $machine, float $currentWeightKg): float
    {
        $nameLower = mb_strtolower((string) ($machine->getName() ?? ''));
        $categoryLower = mb_strtolower((string) (method_exists($machine, 'getCategory') ? ($machine->getCategory() ?? '') : ''));
        $haystack = $nameLower . ' ' . $categoryLower;

        $isPlateLoaded = false;
        $plateKeywords = ['sztang', 'barbell', 'gryf', 'krążk', 'krazk', 'talerz', 'plate', 'smith'];
        foreach ($plateKeywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                $isPlateLoaded = true;
                break;
            }
        }

        // 1. Priorytet: indywidualny krok wyuczony lub ustawiony dla tego użytkownika na tej maszynie
        $userSteps = method_exists($user, 'getExerciseIncrementSteps')
            ? $user->getExerciseIncrementSteps()
            : [];
        $machineKey = (string) $machine->getId();

        if (isset($userSteps[$machineKey]) && (float) $userSteps[$machineKey] > 0) {
            $learnedStep = (float) $userSteps[$machineKey];
            // Na sztandze / wolnym ciężarze nie traktujemy dużego skoku rozgrzewkowego (np. +10 kg) jako najmniejszego kroku
            if (!$isPlateLoaded || $learnedStep <= 5.0) {
                return $learnedStep;
            }
        }

        // 2. Hantle (system EU 2.0 kg vs US 2.5 kg)
        if (str_contains($haystack, 'hantl') || str_contains($haystack, 'dumbbell')) {
            $system = method_exists($machine, 'getDumbbellStepSystem') && $machine->getDumbbellStepSystem()
                ? (string) $machine->getDumbbellStepSystem()
                : 'us_2_5kg';

            return $system === 'eu_2kg' ? 2.0 : 2.5;
        }

        // 3. Krążki / sztanga / wolny ciężar ładowany talerzami (para 1.25 kg = 2.5 kg)
        if ($isPlateLoaded) {
            return (float) ($machine->getIncrementStep() > 0 ? $machine->getIncrementStep() : 2.5);
        }

        // 4. Wyciągi i bramy (częste przełożenie 2:1 lub dokładki +2.5 kg)
        $cableKeywords = ['bram', 'wyciąg', 'wyciag', 'link', 'cable', 'crossover', 'pulley'];
        foreach ($cableKeywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return 2.5;
            }
        }

        // 5. Maszyny ze stosem (selectorized)
        $configuredStep = (float) ($machine->getIncrementStep() ?? 0);
        if ($configuredStep > 0 && $configuredStep !== 2.5) {
            return $configuredStep;
        }

        $smallStackKeywords = [
            'biceps',
            'triceps',
            'bark',
            'wznos',
            'odwodz',
            'przywodz',
            'prostowanie nóg',
            'prostowanie nog',
            'uginanie nóg',
            'uginanie nog',
            'brzuch',
        ];
        foreach ($smallStackKeywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return $currentWeightKg > 0 && $currentWeightKg <= 20.0 ? 2.5 : 5.0;
            }
        }

        // Domyślny standard dla ciężkich maszyn ze stosem
        return 5.0;
    }
}
