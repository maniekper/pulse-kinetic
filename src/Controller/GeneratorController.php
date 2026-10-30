<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\GoalRepository;
use App\Repository\MuscleRepository;
use App\Repository\MachineRepository;
use App\Entity\WorkoutPlan;
use App\Entity\WorkoutExercise;
use Doctrine\ORM\EntityManagerInterface;

class GeneratorController extends AbstractController
{
    #[Route('/generator/start', name: 'app_generator_start')]
    public function start(Request $request, GoalRepository $goalRepository): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // Pobieramy wszystkie cele z bazy, aby wyświetlić je w oknie wyboru
        $allGoals = $goalRepository->findAll();

        return $this->render('generator/step1_days.html.twig', [
            'goals' => $allGoals
        ]);
    }

    #[Route('/generator/goal/change', name: 'app_generator_change_goal', methods: ['POST'])]
    public function changeGoal(Request $request, GoalRepository $goalRepository, EntityManagerInterface $entityManager): Response
    {
        $newGoalId = $request->request->get('new_goal_id');
        $newGoal = $goalRepository->find($newGoalId);

        $user = $this->getUser();
        if ($newGoal && $user instanceof \App\Entity\User) {
            $user->setCurrentGoal($newGoal);
            $entityManager->flush();
            
            $this->addFlash('success', 'Twój cel treningowy został pomyślnie zmieniony. Nowe plany będą dostosowane do: ' . $newGoal->getName());
        }

        // Po zmianie celu odświeżamy stronę startową
        return $this->redirectToRoute('app_generator_start');
    }

    #[Route('/generator/step1/save', name: 'app_generator_step1_save', methods: ['POST'])]
    public function saveDays(Request $request): Response
    {
        $days = (int) $request->request->get('days');
        
        if ($days === 2) {
            $this->addFlash('info', 'Trening 2 razy w tygodniu jest świetny na start! Wygenerujemy dla Ciebie plan rozłożony na 2 tygodnie.');
        }

        $request->getSession()->set('gen_days', $days);
        return $this->redirectToRoute('app_generator_duration');
    }

    #[Route('/generator/duration', name: 'app_generator_duration')]
    public function duration(Request $request): Response
    {
        if (!$request->getSession()->has('gen_days')) return $this->redirectToRoute('app_generator_start');
        
        $daysPerWeek = $request->getSession()->get('gen_days');
        $minWeeks = ($daysPerWeek <= 3) ? 2 : 1;

        return $this->render('generator/step2_duration.html.twig', [
            'minWeeks' => $minWeeks,
            'maxWeeks' => 8
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
        if (!$request->getSession()->has('gen_weeks')) return $this->redirectToRoute('app_generator_duration');

        $muscles = $muscleRepository->findAll();
        return $this->render('generator/step3_exclusions.html.twig', [
            'muscles' => $muscles
        ]);
    }

    #[Route('/generator/build', name: 'app_generator_build', methods: ['POST'])]
    public function buildPlan(
        Request $request, 
        MuscleRepository $muscleRepository, 
        MachineRepository $machineRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $daysPerWeek = (int) $request->getSession()->get('gen_days', 3);
        $weeks = (int) $request->getSession()->get('gen_weeks', 4);
        $excludedMusclesIds = $request->request->all('excluded_muscles') ?? [];

        // Pobieramy cel bezpośrednio z profilu zalogowanego użytkownika
        $user = $this->getUser();
        $userGoal = $user instanceof \App\Entity\User ? $user->getCurrentGoal() : null;
        $goalName = $userGoal ? $userGoal->getName() : 'Hipertrofia';

        $allMuscles = $muscleRepository->findAll();
        $activeMuscleNames = [];
        foreach ($allMuscles as $m) {
            if (!in_array($m->getId(), $excludedMusclesIds)) {
                $activeMuscleNames[] = $m->getName();
            }
        }

        if (empty($activeMuscleNames)) {
            $this->addFlash('danger', 'Wykluczyłeś wszystkie partie ciała! System nie ma z czego zbudować planu.');
            return $this->redirectToRoute('app_generator_exclusions');
        }

        if ($daysPerWeek <= 3) {
            $masterBlueprint = [
                ['Nogi', 'Plecy', 'Klatka piersiowa', 'Barki', 'Biceps', 'Triceps'] 
            ];
            $exercisesPerMuscle = 1; 
        } elseif ($daysPerWeek == 4) {
            $masterBlueprint = [
                ['Klatka piersiowa', 'Biceps'],
                ['Plecy', 'Triceps'],
                ['Nogi'],
                ['Barki', 'Brzuch']
            ];
            $exercisesPerMuscle = 3; 
        } else {
            $masterBlueprint = [
                ['Klatka piersiowa', 'Barki', 'Triceps'], 
                ['Plecy', 'Biceps'], 
                ['Nogi', 'Brzuch'] 
            ];
            $exercisesPerMuscle = 2;
        }

        $activeBlueprint = [];
        foreach ($masterBlueprint as $dayMuscles) {
            $validMusclesForDay = array_intersect($dayMuscles, $activeMuscleNames);
            if (!empty($validMusclesForDay)) {
                $activeBlueprint[] = array_values($validMusclesForDay);
            }
        }

        $totalSessions = $daysPerWeek * $weeks;
        $plan = new WorkoutPlan();
        $plan->setName('Plan: ' . $goalName . ' (' . $daysPerWeek . ' dni/tyg)');
        $plan->setUser($this->getUser());
        $entityManager->persist($plan);

        $allMachines = $machineRepository->findAll();

        for ($sessionNum = 1; $sessionNum <= $totalSessions; $sessionNum++) {
            $blueprintIndex = ($sessionNum - 1) % count($activeBlueprint);
            $targetMuscles = $activeBlueprint[$blueprintIndex];

            $dailyExercises = [];
            foreach ($targetMuscles as $targetMuscle) {
                $muscleSpecificMachines = [];
                
                foreach ($allMachines as $machine) {
                    $primaryNames = array_map(fn($m) => $m->getName(), $machine->getPrimaryMuscles()->toArray());
                    if (in_array($targetMuscle, $primaryNames)) {
                        $muscleSpecificMachines[] = $machine;
                    }
                }
                
                shuffle($muscleSpecificMachines);
                $selectedForThisMuscle = array_slice($muscleSpecificMachines, 0, $exercisesPerMuscle);
                $dailyExercises = array_merge($dailyExercises, $selectedForThisMuscle);
            }

            foreach ($dailyExercises as $machine) {
                $exercise = new WorkoutExercise();
                $exercise->setWorkoutPlan($plan);
                $exercise->setMachine($machine);
                $exercise->setDayNumber($sessionNum);
                
                $isCompound = ($machine->getMechanicType() === 'compound');

                // Bezpieczna zamiana na małe litery z obsługą polskich znaków (UTF-8)
                $lowerGoalName = mb_strtolower($goalName, 'UTF-8');

                // Szukamy rdzenia słowa, aby ominąć problemy z odmianą (Siły vs Siła)
                if (str_contains($lowerGoalName, 'redukcj') || str_contains($lowerGoalName, 'rzeźb')) {
                    $exercise->setSets($isCompound ? 3 : 3);
                    $exercise->setReps($isCompound ? '10-12' : '12-15');
                } elseif (str_contains($lowerGoalName, 'sił')) {
                    $exercise->setSets($isCompound ? 5 : 3);
                    $exercise->setReps($isCompound ? '3-6' : '6-8');
                } else {
                    $exercise->setSets($isCompound ? 4 : 3);
                    $exercise->setReps($isCompound ? '8-10' : '10-12');
                }
                
                $entityManager->persist($exercise);
            }
        }

        $entityManager->flush();
        $request->getSession()->remove('gen_days');
        $request->getSession()->remove('gen_weeks');

        return $this->redirectToRoute('app_generator_success', ['id' => $plan->getId()]);
    }

    #[Route('/generator/success/{id}', name: 'app_generator_success')]
    public function success(WorkoutPlan $plan, Request $request): Response
    {
        if ($plan->getUser() !== $this->getUser()) throw $this->createAccessDeniedException();

        $sessions = [];
        foreach ($plan->getWorkoutExercises() as $exercise) {
            $sessions[$exercise->getDayNumber()][] = $exercise;
        }
        ksort($sessions);

        return $this->render('generator/success.html.twig', [
            'plan' => $plan,
            'sessions' => $sessions,
            'daysPerWeek' => 4 
        ]);
    }
}