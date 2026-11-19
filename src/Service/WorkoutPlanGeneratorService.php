<?php

namespace App\Service;

use App\Entity\Machine;
use App\Entity\User;
use App\Entity\WorkoutExercise;
use App\Entity\WorkoutPlan;
use App\Repository\MachineRepository;
use App\Repository\MuscleRepository;
use Doctrine\ORM\EntityManagerInterface;

class WorkoutPlanGeneratorService
{
    /**
     * Kompletne mapowanie grup treningowych na docelowy słownik 24 mięśni.
     * Dodanie nowych maszyn na dowolny z tych mięśni automatycznie włączy je do generatora.
     */
    private const ANATOMY_MAP = [
        'Klatka piersiowa' => [
            'Klatka piersiowa - środek',
            'Klatka piersiowa - góra',
            'Klatka piersiowa - dół',
        ],
        'Plecy' => [
            'Najszerszy grzbietu',
            'Górna część pleców',
            'Prostownik grzbietu',
            'Kaptur',
        ],
        'Barki' => [
            'Barki - przód',
            'Barki - bok',
            'Barki - tył',
        ],
        'Barki (Push)' => [
            'Barki - przód',
            'Barki - bok',
        ],
        'Barki (Pull)' => [
            'Barki - tył',
        ],
        'Biceps' => [
            'Biceps',
        ],
        'Triceps' => [
            'Triceps - głowa długa',
            'Triceps - głowa boczna',
            'Triceps - głowa przyśrodkowa',
        ],
        'Przedramiona' => [
            'Przedramiona - zginacze (przód)',
            'Przedramiona - prostowniki (tył)',
        ],
        'Nogi' => [
            'Czworogłowy uda',
            'Tył uda (Kulszowo-goleniowe)',
            'Przywodziciele',
            'Odwodziciele',
        ],
        'Pośladki' => [
            'Pośladki',
        ],
        'Łydki' => [
            'Łydki',
        ],
        'Brzuch' => [
            'Prosty brzucha',
            'Skośne brzucha',
        ],
    ];

    /**
     * Sloty priorytetowe dla każdej grupy.
     * Jeśli baza nie ma jeszcze ćwiczenia dla konkretnego slotu (np. Kaptur, Skośne brzucha, Odwodziciele),
     * slot jest pomijany i uzupełniany z ogólnej puli tej grupy (fallback).
     */
    private const SLOT_DEFINITIONS = [
        'Klatka piersiowa' => [
            ['primary' => ['Klatka piersiowa - środek'], 'mechanic' => 'compound'],
            ['primary' => ['Klatka piersiowa - góra'], 'mechanic' => 'compound'],
            ['primary' => ['Klatka piersiowa - środek', 'Klatka piersiowa - góra', 'Klatka piersiowa - dół'], 'mechanic' => 'isolation'],
            ['primary' => ['Klatka piersiowa - dół', 'Klatka piersiowa - góra'], 'mechanic' => 'compound'],
        ],
        'Plecy' => [
            ['pattern' => 'vertical_pull'],
            ['pattern' => 'horizontal_pull'],
            ['pattern' => 'vertical_pull'],
            ['primary' => ['Górna część pleców', 'Prostownik grzbietu', 'Kaptur']],
        ],
        'Barki' => [
            ['primary' => ['Barki - przód'], 'pattern' => 'overhead_press'],
            ['primary' => ['Barki - bok']],
            ['primary' => ['Barki - tył']],
        ],
        'Barki (Push)' => [
            ['primary' => ['Barki - bok']],
            ['primary' => ['Barki - przód'], 'pattern' => 'overhead_press'],
        ],
        'Barki (Pull)' => [
            ['primary' => ['Barki - tył']],
        ],
        'Nogi' => [
            ['primary' => ['Czworogłowy uda'], 'pattern' => 'squat'],
            ['primary' => ['Tył uda (Kulszowo-goleniowe)'], 'pattern' => 'hinge'],
            ['primary' => ['Czworogłowy uda'], 'mechanic' => 'isolation'],
            ['primary' => ['Tył uda (Kulszowo-goleniowe)'], 'mechanic' => 'isolation'],
            ['primary' => ['Przywodziciele', 'Odwodziciele']],
        ],
        'Pośladki' => [
            ['primary' => ['Pośladki'], 'mechanic' => 'compound'],
            ['primary' => ['Pośladki'], 'mechanic' => 'isolation'],
        ],
        'Łydki' => [
            ['primary' => ['Łydki']],
        ],
        'Biceps' => [
            ['primary' => ['Biceps']],
            ['primary' => ['Biceps']],
            ['primary' => ['Biceps']],
        ],
        'Triceps' => [
            ['primary' => ['Triceps - głowa długa']],
            ['primary' => ['Triceps - głowa boczna']],
            ['primary' => ['Triceps - głowa przyśrodkowa']],
        ],
        'Brzuch' => [
            ['primary' => ['Prosty brzucha']],
            ['primary' => ['Skośne brzucha']],
        ],
        'Przedramiona' => [
            ['primary' => ['Przedramiona - zginacze (przód)']],
            ['primary' => ['Przedramiona - prostowniki (tył)']],
        ],
    ];

    /**
     * Wzorce ruchowe, których NIE dublujemy w ramach jednej sesji podczas dobierania zapasowych ćwiczeń.
     */
    private const UNIQUE_PATTERNS_IN_SESSION = [
        'squat',
        'hinge',
        'overhead_press',
    ];

    public function __construct(
        private MuscleRepository $muscleRepository,
        private MachineRepository $machineRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param int[] $excludedMuscleIds
     */
    public function generatePlan(User $user, int $daysPerWeek, int $weeks, array $excludedMuscleIds = []): WorkoutPlan
    {
        $excludedMuscleIds = array_map('intval', $excludedMuscleIds);
        $userGoal = $user->getCurrentGoal();
        $goalName = $userGoal ? $userGoal->getName() : 'Trening';

        // 1. Rozwiązujemy wykluczenia (wykluczenie grupy ogólnej wyklucza też jej aktony)
        $allMuscles = $this->muscleRepository->findAll();
        $excludedMuscleNames = [];
        $activeMuscleNames = [];

        foreach ($allMuscles as $muscle) {
            if (in_array($muscle->getId(), $excludedMuscleIds, true)) {
                $name = $muscle->getName();
                $excludedMuscleNames[] = $name;
                if (isset(self::ANATOMY_MAP[$name])) {
                    $excludedMuscleNames = array_merge($excludedMuscleNames, self::ANATOMY_MAP[$name]);
                }
            } else {
                $activeMuscleNames[] = $muscle->getName();
            }
        }
        $excludedMuscleNames = array_unique($excludedMuscleNames);

        if (empty($activeMuscleNames)) {
            throw new \InvalidArgumentException('Wykluczyłeś wszystkie partie ciała! System nie ma z czego zbudować planu.');
        }

        // 2. Filtrujemy dostępne maszyny siłowe (bez cardio i bez wykluczonych partii)
        $availableMachines = [];
        foreach ($this->machineRepository->findAll() as $machine) {
            if ($this->isCardioMachine($machine)) {
                continue;
            }

            $primaryNames = $this->getMuscleNames($machine->getPrimaryMuscles());
            if (!empty(array_intersect($primaryNames, $excludedMuscleNames))) {
                continue;
            }

            $availableMachines[] = $machine;
        }

        if (empty($availableMachines)) {
            throw new \InvalidArgumentException('Brak dostępnych ćwiczeń po zastosowaniu wykluczeń.');
        }

        // 3. Wybieramy blueprint pod liczbę dni i płeć
        $gender = strtolower($user->getGender() ?? '');
        $isFemale = in_array($gender, ['kobieta', 'female'], true);
        $masterBlueprint = $this->getBlueprint($daysPerWeek, $isFemale);

        $plan = new WorkoutPlan();
        $plan->setUser($user);
        $planSuffix = ($daysPerWeek === 2)
            ? ' (Split: Góra/Dół)'
            : sprintf(' (%d dni / %d tyg.)', $daysPerWeek, $weeks);
        $plan->setName($goalName . $planSuffix);
        $this->entityManager->persist($plan);

        // Śledzimy użycie maszyn w całym tygodniu, żeby kolejne dni rotowały sprzętem
        $usedMachineIdsInPlan = [];

        for ($sessionNum = 1; $sessionNum <= $daysPerWeek; $sessionNum++) {
            $blueprintIndex = ($sessionNum - 1) % count($masterBlueprint);
            $dayBlueprint = $masterBlueprint[$blueprintIndex];

            $pickedByGroup = [];
            $groupPools = [];
            $usedMachineIdsInSession = [];
            $usedPatternsInSession = [];
            $secondaryHitsInSession = [];
            $unfilledDeficit = 0;

            // --- PASS 1: Dobieramy ćwiczenia dla każdej grupy wg slotów i synergii ---
            foreach ($dayBlueprint as $targetGroup => $baseTargetCount) {
                $groupSpecificMuscles = self::ANATOMY_MAP[$targetGroup] ?? [$targetGroup];

                $targetCount = $this->adjustCountForSynergy(
                    $targetGroup,
                    $baseTargetCount,
                    $groupSpecificMuscles,
                    $secondaryHitsInSession
                );

                $groupPool = array_values(array_filter(
                    $availableMachines,
                    fn(Machine $m) => !empty(array_intersect($this->getMuscleNames($m->getPrimaryMuscles()), $groupSpecificMuscles))
                ));

                $groupPools[$targetGroup] = $groupPool;
                $pickedByGroup[$targetGroup] = [];

                if (empty($groupPool)) {
                    // Brak maszyn w bazie (np. Łydki/Przedramiona przed dodaniem lub wykluczenie) -> zapamiętujemy deficyt
                    $unfilledDeficit += $targetCount;
                    continue;
                }

                $slots = self::SLOT_DEFINITIONS[$targetGroup] ?? [];

                // Jeśli Barki są po Klatce i przód barku dostał >= 2 bodźce, przesuwamy przód na koniec kolejki slotów
                if ($targetGroup === 'Barki' && ($secondaryHitsInSession['Barki - przód'] ?? 0) >= 2 && count($slots) > 1) {
                    $frontSlot = array_shift($slots);
                    $slots[] = $frontSlot;
                }

                $usedPrimariesInGroup = [];

                // Krok A: Dobieramy maszyny do zdefiniowanych slotów
                foreach ($slots as $slot) {
                    if (count($pickedByGroup[$targetGroup]) >= $targetCount) {
                        break;
                    }

                    $candidate = $this->pickMachineForSlot(
                        $groupPool,
                        $slot,
                        $usedMachineIdsInSession,
                        $usedPatternsInSession,
                        $usedMachineIdsInPlan,
                        $usedPrimariesInGroup
                    );

                    if ($candidate) {
                        $this->registerPick(
                            $candidate,
                            $targetGroup,
                            $pickedByGroup,
                            $usedMachineIdsInSession,
                            $usedPatternsInSession,
                            $usedMachineIdsInPlan,
                            $usedPrimariesInGroup,
                            $secondaryHitsInSession
                        );
                    }
                }

                // Krok B: Fallback w ramach tej samej grupy (gdy któryś slot był pusty)
                if (count($pickedByGroup[$targetGroup]) < $targetCount) {
                    $fallbackCandidates = $this->sortCandidatesByVariety(
                        $groupPool,
                        $usedMachineIdsInPlan,
                        $usedPrimariesInGroup,
                        $usedPatternsInSession
                    );

                    foreach ($fallbackCandidates as $candidate) {
                        if (count($pickedByGroup[$targetGroup]) >= $targetCount) {
                            break;
                        }
                        if (!$this->canUseMachineInSession($candidate, $usedMachineIdsInSession, $usedPatternsInSession)) {
                            continue;
                        }

                        $this->registerPick(
                            $candidate,
                            $targetGroup,
                            $pickedByGroup,
                            $usedMachineIdsInSession,
                            $usedPatternsInSession,
                            $usedMachineIdsInPlan,
                            $usedPrimariesInGroup,
                            $secondaryHitsInSession
                        );
                    }
                }

                if (count($pickedByGroup[$targetGroup]) < $targetCount) {
                    $unfilledDeficit += ($targetCount - count($pickedByGroup[$targetGroup]));
                }
            }

            // --- PASS 2: Dopełnienie deficytu (gdy zabrakło maszyn dla którejś grupy, np. Łydki / Przedramiona) ---
            if ($unfilledDeficit > 0) {
                $extraAddedPerGroup = [];

                foreach (array_keys($dayBlueprint) as $targetGroup) {
                    if ($unfilledDeficit <= 0) {
                        break;
                    }
                    if (empty($groupPools[$targetGroup])) {
                        continue;
                    }
                    // Maksymalnie +1 dodatkowe ćwiczenie na grupę, żeby nie przeładować jednej partii
                    if (($extraAddedPerGroup[$targetGroup] ?? 0) >= 1) {
                        continue;
                    }

                    $usedPrimariesInGroup = [];
                    foreach ($pickedByGroup[$targetGroup] as $picked) {
                        $usedPrimariesInGroup = array_merge($usedPrimariesInGroup, $this->getMuscleNames($picked->getPrimaryMuscles()));
                    }

                    $candidates = $this->sortCandidatesByVariety(
                        $groupPools[$targetGroup],
                        $usedMachineIdsInPlan,
                        $usedPrimariesInGroup,
                        $usedPatternsInSession
                    );

                    foreach ($candidates as $candidate) {
                        if (!$this->canUseMachineInSession($candidate, $usedMachineIdsInSession, $usedPatternsInSession)) {
                            continue;
                        }

                        $this->registerPick(
                            $candidate,
                            $targetGroup,
                            $pickedByGroup,
                            $usedMachineIdsInSession,
                            $usedPatternsInSession,
                            $usedMachineIdsInPlan,
                            $usedPrimariesInGroup,
                            $secondaryHitsInSession
                        );

                        $extraAddedPerGroup[$targetGroup] = ($extraAddedPerGroup[$targetGroup] ?? 0) + 1;
                        $unfilledDeficit--;
                        break;
                    }
                }
            }

            // --- SKŁADANIE SESJI: wewnątrz każdej partii zawsze najpierw compound, potem isolation ---
            $dailyExercises = [];
            foreach ($pickedByGroup as $groupMachines) {
                usort($groupMachines, function (Machine $a, Machine $b) {
                    $aWeight = $a->getMechanicType() === 'compound' ? 0 : 1;
                    $bWeight = $b->getMechanicType() === 'compound' ? 0 : 1;
                    return $aWeight <=> $bWeight;
                });

                foreach ($groupMachines as $machine) {
                    $dailyExercises[] = $machine;
                }
            }

            // Zapis ćwiczeń w bazie (bezpieczny sufit 7 ćwiczeń na sesję)
            $exerciseCount = 0;
            foreach ($dailyExercises as $machine) {
                if ($exerciseCount >= 7) {
                    break;
                }

                $exercise = new WorkoutExercise();
                $exercise->setWorkoutPlan($plan);
                $exercise->setMachine($machine);
                $exercise->setDayNumber($sessionNum);

                $isCompound = ($machine->getMechanicType() === 'compound');

                if ($userGoal) {
                    $minReps = $isCompound ? $userGoal->getBaseCompoundRepsMin() : $userGoal->getBaseIsolationRepsMin();
                    $maxReps = $isCompound ? $userGoal->getBaseCompoundRepsMax() : $userGoal->getBaseIsolationRepsMax();

                    $exercise->setSets($isCompound ? 4 : 3);
                    $exercise->setReps($minReps . '-' . $maxReps);
                } else {
                    $exercise->setSets($isCompound ? 4 : 3);
                    $exercise->setReps('8-12');
                }

                $this->entityManager->persist($exercise);
                $exerciseCount++;
            }
        }

        $this->entityManager->flush();

        return $plan;
    }

    private function getBlueprint(int $daysPerWeek, bool $isFemale): array
    {
        if ($daysPerWeek === 2) {
            if ($isFemale) {
                return [
                    ['Nogi' => 3, 'Pośladki' => 1, 'Łydki' => 1, 'Brzuch' => 1],
                    ['Plecy' => 2, 'Barki' => 2, 'Klatka piersiowa' => 1, 'Biceps' => 1, 'Triceps' => 1],
                ];
            }

            return [
                ['Klatka piersiowa' => 2, 'Plecy' => 2, 'Barki' => 1, 'Biceps' => 1, 'Triceps' => 1],
                ['Nogi' => 3, 'Pośladki' => 1, 'Łydki' => 1, 'Brzuch' => 1],
            ];
        }

        if ($daysPerWeek === 3) {
            if ($isFemale) {
                return [
                    ['Nogi' => 2, 'Pośladki' => 1, 'Plecy' => 1, 'Klatka piersiowa' => 1, 'Brzuch' => 1],
                    ['Nogi' => 2, 'Pośladki' => 1, 'Plecy' => 1, 'Barki' => 1, 'Biceps' => 1],
                    ['Nogi' => 2, 'Pośladki' => 1, 'Klatka piersiowa' => 1, 'Barki' => 1, 'Triceps' => 1],
                ];
            }

            return [
                ['Nogi' => 2, 'Klatka piersiowa' => 1, 'Plecy' => 1, 'Barki' => 1, 'Biceps' => 1],
                ['Nogi' => 2, 'Plecy' => 1, 'Klatka piersiowa' => 1, 'Barki' => 1, 'Triceps' => 1],
                ['Nogi' => 2, 'Klatka piersiowa' => 1, 'Plecy' => 1, 'Biceps' => 1, 'Brzuch' => 1],
            ];
        }

        if ($daysPerWeek === 4) {
            if ($isFemale) {
                return [
                    ['Nogi' => 3, 'Pośladki' => 1, 'Łydki' => 1, 'Brzuch' => 1],
                    ['Plecy' => 3, 'Barki' => 2, 'Biceps' => 2], // Biceps -> 1 po synergii z plecami
                    ['Nogi' => 3, 'Pośladki' => 2, 'Łydki' => 1],
                    ['Klatka piersiowa' => 3, 'Triceps' => 2, 'Brzuch' => 2], // Triceps -> 1 po synergii z klatką
                ];
            }

            return [
                ['Klatka piersiowa' => 4, 'Biceps' => 2],               // Antagoniści: 6 ćw.
                ['Plecy' => 4, 'Triceps' => 2],                         // Antagoniści: 6 ćw.
                ['Nogi' => 4, 'Pośladki' => 1, 'Łydki' => 1],           // Dzień nóg: 6 ćw. (dziś Łydki przejmuje Nogi -> 5+1)
                ['Barki' => 3, 'Brzuch' => 2, 'Przedramiona' => 1],     // 5–6 ćw. (Przedramiona włączą się automatycznie po dodaniu)
            ];
        }

        // 5+ dni: Push / Pull / Legs
        return [
            ['Klatka piersiowa' => 3, 'Barki (Push)' => 2, 'Triceps' => 2], // Triceps -> 1 po synergii = 6 ćw.
            ['Plecy' => 3, 'Barki (Pull)' => 1, 'Biceps' => 3],             // Biceps -> 2 po synergii = 6 ćw.
            ['Nogi' => 3, 'Pośladki' => 1, 'Łydki' => 1, 'Brzuch' => 1],    // 6 ćw.
        ];
    }

    private function adjustCountForSynergy(
        string $targetGroup,
        int $baseCount,
        array $groupSpecificMuscles,
        array $secondaryHitsInSession
    ): int {
        if (!in_array($targetGroup, ['Biceps', 'Triceps'], true) || $baseCount <= 1) {
            return $baseCount;
        }

        $synergyHits = 0;
        foreach ($groupSpecificMuscles as $muscleName) {
            $synergyHits += $secondaryHitsInSession[$muscleName] ?? 0;
        }

        return $synergyHits >= 2 ? max(1, $baseCount - 1) : $baseCount;
    }

    private function pickMachineForSlot(
        array $pool,
        array $slot,
        array $usedInSession,
        array $usedPatternsInSession,
        array $usedInPlan,
        array $usedPrimariesInGroup
    ): ?Machine {
        $matches = array_values(array_filter($pool, function (Machine $machine) use ($slot, $usedInSession, $usedPatternsInSession) {
            if (!$this->canUseMachineInSession($machine, $usedInSession, $usedPatternsInSession)) {
                return false;
            }

            if (isset($slot['mechanic']) && $machine->getMechanicType() !== $slot['mechanic']) {
                return false;
            }

            if (isset($slot['pattern']) && $machine->getMovementPattern() !== $slot['pattern']) {
                return false;
            }

            if (isset($slot['primary'])) {
                $primaryNames = $this->getMuscleNames($machine->getPrimaryMuscles());
                if (empty(array_intersect($primaryNames, $slot['primary']))) {
                    return false;
                }
            }

            return true;
        }));

        if (empty($matches)) {
            return null;
        }

        $sorted = $this->sortCandidatesByVariety($matches, $usedInPlan, $usedPrimariesInGroup, $usedPatternsInSession);
        return $sorted[0];
    }

    private function canUseMachineInSession(Machine $machine, array $usedInSession, array $usedPatternsInSession): bool
    {
        if (in_array($machine->getId(), $usedInSession, true)) {
            return false;
        }

        $pattern = $machine->getMovementPattern() ?? 'other';
        if (in_array($pattern, self::UNIQUE_PATTERNS_IN_SESSION, true) && in_array($pattern, $usedPatternsInSession, true)) {
            return false;
        }

        return true;
    }

    /**
     * Kolejność preferencji:
     * 1) Inny akton niż już wybrany w tej grupie (np. długa vs boczna głowa tricepsa, środek vs góra klatki)
     * 2) Inny wzorzec ruchu niż już użyty dzisiaj (np. vertical_pull vs horizontal_pull)
     * 3) Maszyna jeszcze nieużyta w innym dniu tygodnia (rotacja między dniami w FBW/Upper-Lower)
     */
    private function sortCandidatesByVariety(
        array $candidates,
        array $usedInPlan,
        array $usedPrimariesInGroup,
        array $usedPatternsInSession
    ): array {
        shuffle($candidates);

        usort($candidates, function (Machine $a, Machine $b) use ($usedInPlan, $usedPrimariesInGroup, $usedPatternsInSession) {
            $aPrimaryUsed = !empty(array_intersect($this->getMuscleNames($a->getPrimaryMuscles()), $usedPrimariesInGroup)) ? 1 : 0;
            $bPrimaryUsed = !empty(array_intersect($this->getMuscleNames($b->getPrimaryMuscles()), $usedPrimariesInGroup)) ? 1 : 0;
            if ($aPrimaryUsed !== $bPrimaryUsed) {
                return $aPrimaryUsed <=> $bPrimaryUsed;
            }

            $aPat = $a->getMovementPattern() ?? 'other';
            $bPat = $b->getMovementPattern() ?? 'other';
            $aPatUsed = ($aPat !== 'isolation' && $aPat !== 'other' && in_array($aPat, $usedPatternsInSession, true)) ? 1 : 0;
            $bPatUsed = ($bPat !== 'isolation' && $bPat !== 'other' && in_array($bPat, $usedPatternsInSession, true)) ? 1 : 0;
            if ($aPatUsed !== $bPatUsed) {
                return $aPatUsed <=> $bPatUsed;
            }

            $aPlanUsed = in_array($a->getId(), $usedInPlan, true) ? 1 : 0;
            $bPlanUsed = in_array($b->getId(), $usedInPlan, true) ? 1 : 0;

            return $aPlanUsed <=> $bPlanUsed;
        });

        return $candidates;
    }

    private function registerPick(
        Machine $machine,
        string $targetGroup,
        array &$pickedByGroup,
        array &$usedMachineIdsInSession,
        array &$usedPatternsInSession,
        array &$usedMachineIdsInPlan,
        array &$usedPrimariesInGroup,
        array &$secondaryHitsInSession
    ): void {
        $pickedByGroup[$targetGroup][] = $machine;
        $usedMachineIdsInSession[] = $machine->getId();
        $usedMachineIdsInPlan[] = $machine->getId();

        $pattern = $machine->getMovementPattern() ?? 'other';
        if ($pattern !== 'other') {
            $usedPatternsInSession[] = $pattern;
        }

        $usedPrimariesInGroup = array_merge(
            $usedPrimariesInGroup,
            $this->getMuscleNames($machine->getPrimaryMuscles())
        );

        foreach ($this->getMuscleNames($machine->getSecondaryMuscles()) as $secName) {
            $secondaryHitsInSession[$secName] = ($secondaryHitsInSession[$secName] ?? 0) + 1;
        }
    }

    private function isCardioMachine(Machine $machine): bool
    {
        $category = strtolower($machine->getCategory() ?? '');
        $mechType = strtolower($machine->getMechanicType() ?? '');
        $nameLower = strtolower($machine->getName() ?? '');

        return $mechType === 'cardio'
            || $category === 'cardio'
            || str_contains($nameLower, 'rower')
            || str_contains($nameLower, 'bieżnia')
            || str_contains($nameLower, 'orbitrek');
    }

    private function getMuscleNames(iterable $muscles): array
    {
        $names = [];
        foreach ($muscles as $muscle) {
            $names[] = $muscle->getName();
        }
        return $names;
    }
}
