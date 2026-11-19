<?php

namespace App\Controller;

use App\Entity\Machine;
use App\Entity\User;
use App\Service\WorkoutPlanGeneratorService;
use App\Entity\WorkoutExercise;
use App\Entity\WorkoutPlan;
use App\Repository\GoalRepository;
use App\Repository\MachineRepository;
use App\Repository\MuscleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GeneratorController extends AbstractController
{
    #[Route('/generator/start', name: 'app_generator_start')]
    public function start(Request $request, GoalRepository $goalRepository): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $allGoals = $goalRepository->findAll();

        return $this->render('generator/step1_days.html.twig', [
            'goals' => $allGoals,
        ]);
    }

    #[Route('/generator/goal/change', name: 'app_generator_change_goal', methods: ['POST'])]
    public function changeGoal(Request $request, GoalRepository $goalRepository, EntityManagerInterface $entityManager): Response
    {
        $newGoalId = $request->request->get('new_goal_id');
        $newGoal = $goalRepository->find($newGoalId);

        $user = $this->getUser();
        if ($newGoal && $user instanceof User) {
            $user->setCurrentGoal($newGoal);

            $goalName = $newGoal->getName();
            $plans = $entityManager->getRepository(WorkoutPlan::class)->findBy(['user' => $user]);

            foreach ($plans as $plan) {
                $plan->setName('Plan: ' . $goalName . ' (Zaktualizowano)');

                foreach ($plan->getWorkoutExercises() as $exercise) {
                    $machine = $exercise->getMachine();
                    $isCompound = $machine ? ($machine->getMechanicType() === 'compound') : false;

                    $minReps = $isCompound ? $newGoal->getBaseCompoundRepsMin() : $newGoal->getBaseIsolationRepsMin();
                    $maxReps = $isCompound ? $newGoal->getBaseCompoundRepsMax() : $newGoal->getBaseIsolationRepsMax();

                    $exercise->setSets($isCompound ? 4 : 3);
                    $exercise->setReps($minReps . '-' . $maxReps);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Parametry planu zostały pomyślnie zaktualizowane dla nowego celu.');
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_generator_start');
    }

    #[Route('/generator/step1/save', name: 'app_generator_step1_save', methods: ['POST'])]
    public function saveDays(Request $request): Response
    {
        $days = (int) $request->request->get('days');

        if ($days === 2) {
            $this->addFlash('info', 'Trening 2 razy w tygodniu jest świetny na start!');
        }

        $request->getSession()->set('gen_days', $days);
        return $this->redirectToRoute('app_generator_duration');
    }

    #[Route('/generator/duration', name: 'app_generator_duration')]
    public function duration(Request $request): Response
    {
        if (!$request->getSession()->has('gen_days')) {
            return $this->redirectToRoute('app_generator_start');
        }

        $daysPerWeek = (int) $request->getSession()->get('gen_days');

        if ($daysPerWeek == 2) {
            $minWeeks = 8;
            $maxWeeks = 12;
            $desc = "Bodźce są rzadsze, więc organizm potrzebuje więcej czasu, aby w pełni opanować ruchy i się do nich zaadaptować. Możesz znacznie dłużej dokładać ciężar bez ryzyka przetrenowania.";
        } elseif ($daysPerWeek == 3) {
            $minWeeks = 6;
            $maxWeeks = 10;
            $desc = "Złoty środek. Układ nerwowy i mięśnie dostają regularny bodziec, co przyspiesza adaptację, ale wciąż pozwala na stosunkowo długi okres stałego progresu siłowego.";
        } elseif ($daysPerWeek == 4) {
            $minWeeks = 4;
            $maxWeeks = 8;
            $desc = "Szybsze nabudowywanie zmęczenia i szybsza adaptacja. Przy takiej częstotliwości mięśnie szybciej przestają reagować na ten sam bodziec i wymagają rotacji ćwiczeń.";
        } else {
            $minWeeks = 4;
            $maxWeeks = 6;
            $desc = "Bardzo wysoka częstotliwość. Potencjał do progresywnego przeładowania wyczerpuje się najszybciej ze względu na olbrzymią kumulację mikrouszkodzeń i zmęczenia (fatigue).";
        }

        return $this->render('generator/step2_duration.html.twig', [
            'minWeeks' => $minWeeks,
            'maxWeeks' => $maxWeeks,
            'defaultWeeks' => (int) round(($minWeeks + $maxWeeks) / 2),
            'daysPerWeek' => $daysPerWeek,
            'mesocycleDesc' => $desc,
        ]);
    }

    #[Route('/generator/step2/save', name: 'app_generator_step2_save', methods: ['POST'])]
    public function saveDuration(Request $request): Response
    {
        $weeks = (int) $request->request->get('weeks');
        $request->getSession()->set('gen_weeks', $weeks);
        return $this->redirectToRoute('app_generator_exclusions');
    }

    #[Route('/generator/exclusions', name: 'app_generator_exclusions')]
    public function exclusions(MuscleRepository $muscleRepository, Request $request): Response
    {
        if (!$request->getSession()->has('gen_weeks')) {
            return $this->redirectToRoute('app_generator_duration');
        }

        $muscles = $muscleRepository->findAll();
        return $this->render('generator/step3_exclusions.html.twig', [
            'muscles' => $muscles,
        ]);
    }

    #[Route('/generator/build', name: 'app_generator_build', methods: ['POST'])]
    public function buildPlan(
        Request $request,
        WorkoutPlanGeneratorService $planGenerator
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        $daysPerWeek = (int) $request->getSession()->get('gen_days', 3);
        $weeks = (int) $request->getSession()->get('gen_weeks', 4);
        $excludedMuscleIds = $request->request->all('excluded_muscles') ?? [];

        try {
            $plan = $planGenerator->generatePlan($user, $daysPerWeek, $weeks, $excludedMuscleIds);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('app_generator_exclusions');
        }

        $request->getSession()->remove('gen_days');
        $request->getSession()->remove('gen_weeks');

        return $this->redirectToRoute('app_generator_success', ['id' => $plan->getId()]);
    }

    #[Route('/generator/success/{id}', name: 'app_generator_success')]
    public function success(WorkoutPlan $plan, Request $request, MachineRepository $machineRepo): Response
    {
        if ($plan->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $sessions = [];
        foreach ($plan->getWorkoutExercises() as $exercise) {
            $sessions[$exercise->getDayNumber()][] = $exercise;
        }
        ksort($sessions);

        return $this->render('generator/success.html.twig', [
            'plan' => $plan,
            'sessions' => $sessions,
            'daysPerWeek' => count($sessions),
            'allMachines' => $machineRepo->findAll(),
        ]);
    }

    private function getBlueprint(int $daysPerWeek, bool $isFemale): array
    {
        if ($daysPerWeek === 2) {
            if ($isFemale) {
                return [
                    ['Nogi' => 3, 'Pośladki' => 1, 'Brzuch' => 2],
                    ['Plecy' => 2, 'Barki' => 2, 'Klatka piersiowa' => 1, 'Biceps' => 1, 'Triceps' => 1],
                ];
            }

            return [
                ['Klatka piersiowa' => 2, 'Plecy' => 2, 'Barki' => 1, 'Biceps' => 1, 'Triceps' => 1],
                ['Nogi' => 4, 'Pośladki' => 1, 'Brzuch' => 1],
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
                    ['Nogi' => 3, 'Pośladki' => 1, 'Brzuch' => 2],
                    ['Plecy' => 3, 'Barki' => 2, 'Biceps' => 2], // Biceps spadnie z 2 na 1 po synergii z plecami
                    ['Nogi' => 4, 'Pośladki' => 1, 'Brzuch' => 1],
                    ['Klatka piersiowa' => 3, 'Triceps' => 2, 'Brzuch' => 1], // Triceps spadnie z 2 na 1 po synergii z klatką
                ];
            }

            return [
                ['Klatka piersiowa' => 4, 'Biceps' => 2],   // Antagoniści: pełna objętość (6 ćw.)
                ['Plecy' => 4, 'Triceps' => 2],             // Antagoniści: pełna objętość (6 ćw.)
                ['Nogi' => 5, 'Pośladki' => 1],             // Pełny dzień nóg (6 ćw.)
                ['Barki' => 3, 'Brzuch' => 2],              // 3 aktony barków + 2 brzuch (5 ćw.)
            ];
        }

        // 5+ dni: Push / Pull / Legs (z automatyczną redukcją synergistów)
        return [
            ['Klatka piersiowa' => 3, 'Barki (Push)' => 2, 'Triceps' => 2], // Triceps -> 1 po synergii = 6 ćw.
            ['Plecy' => 3, 'Barki (Pull)' => 1, 'Biceps' => 3],             // Biceps -> 2 po synergii = 6 ćw.
            ['Nogi' => 4, 'Pośladki' => 1, 'Brzuch' => 1],                  // 6 ćw.
        ];
    }

    private function pickMachineForSlot(
        array $pool,
        array $slot,
        array $usedInSession,
        array $usedInPlan,
        array $usedPrimariesInGroup
    ): ?Machine {
        $matches = array_values(array_filter($pool, function (Machine $machine) use ($slot, $usedInSession) {
            if (in_array($machine->getId(), $usedInSession, true)) {
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

        $sorted = $this->sortCandidatesByVariety($matches, $usedInPlan, $usedPrimariesInGroup);
        return $sorted[0];
    }

    /**
     * Preferuje: 1) inny akton niż już wybrany w tej grupie, 2) maszynę jeszcze nieużytą w innym dniu tygodnia, 3) losowość.
     */
    private function sortCandidatesByVariety(array $candidates, array $usedInPlan, array $usedPrimariesInGroup): array
    {
        shuffle($candidates);

        usort($candidates, function (Machine $a, Machine $b) use ($usedInPlan, $usedPrimariesInGroup) {
            $aPrimaryUsed = !empty(array_intersect($this->getMuscleNames($a->getPrimaryMuscles()), $usedPrimariesInGroup)) ? 1 : 0;
            $bPrimaryUsed = !empty(array_intersect($this->getMuscleNames($b->getPrimaryMuscles()), $usedPrimariesInGroup)) ? 1 : 0;

            if ($aPrimaryUsed !== $bPrimaryUsed) {
                return $aPrimaryUsed <=> $bPrimaryUsed;
            }

            $aPlanUsed = in_array($a->getId(), $usedInPlan, true) ? 1 : 0;
            $bPlanUsed = in_array($b->getId(), $usedInPlan, true) ? 1 : 0;

            return $aPlanUsed <=> $bPlanUsed;
        });

        return $candidates;
    }

    private function isCardioMachine(Machine $machine): bool
    {
        $nameLower = strtolower($machine->getName() ?? '');
        $mechType = strtolower($machine->getMechanicType() ?? '');

        return $mechType === 'cardio'
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
