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
            
            $goalName = $newGoal->getName();

            $plans = $entityManager->getRepository(WorkoutPlan::class)->findBy(['user' => $user]);

            foreach ($plans as $plan) {
                $plan->setName('Plan: ' . $goalName . ' (Zaktualizowano)');

                foreach ($plan->getWorkoutExercises() as $exercise) {
                    $machine = $exercise->getMachine();
                    $isCompound = $machine ? ($machine->getMechanicType() === 'compound') : false;

                    // Dynamiczne powtórzenia prosto z encji Goal
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
        if (!$request->getSession()->has('gen_days')) return $this->redirectToRoute('app_generator_start');
        
        $daysPerWeek = (int) $request->getSession()->get('gen_days');
        
        // NOWA LOGIKA: Adaptacja układu nerwowego (Mezocykl)
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
            'mesocycleDesc' => $desc
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

        $user = $this->getUser();
        $userGoal = $user instanceof \App\Entity\User ? $user->getCurrentGoal() : null;
        $goalName = $userGoal ? $userGoal->getName() : 'Trening';

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

        $gender = strtolower($user instanceof \App\Entity\User ? ($user->getGender() ?? '') : '');
        $isFemale = in_array($gender, ['kobieta', 'female']);

        // SPERSONALIZOWANY BLUEPRINT TRENINGOWY (GÓRA/DÓŁ DLA 2 DNI)
        if ($daysPerWeek == 2) {
            if ($isFemale) {
                $masterBlueprint = [
                    ['Nogi', 'Pośladki', 'Łydki', 'Brzuch'], // Dół
                    ['Plecy', 'Barki', 'Klatka piersiowa', 'Biceps', 'Triceps'] // Góra
                ];
                $exercisesPerMuscle = 2; 
            } else {
                $masterBlueprint = [
                    ['Klatka piersiowa', 'Plecy', 'Barki', 'Biceps', 'Triceps'], // Góra
                    ['Nogi', 'Łydki', 'Brzuch'] // Dół
                ];
                $exercisesPerMuscle = 2; 
            }
        } elseif ($daysPerWeek == 3) {
            if ($isFemale) {
                $masterBlueprint = [['Nogi', 'Pośladki', 'Plecy', 'Klatka piersiowa', 'Barki']];
                $exercisesPerMuscle = 1;
            } else {
                $masterBlueprint = [['Nogi', 'Plecy', 'Klatka piersiowa', 'Barki', 'Biceps', 'Triceps']];
                $exercisesPerMuscle = 1; 
            }
        } elseif ($daysPerWeek == 4) {
            if ($isFemale) {
                $masterBlueprint = [
                    ['Nogi', 'Pośladki', 'Brzuch'],           
                    ['Plecy', 'Barki', 'Biceps'],             
                    ['Nogi', 'Pośladki', 'Łydki'],            
                    ['Klatka piersiowa', 'Triceps', 'Brzuch'] 
                ];
                $exercisesPerMuscle = 2; 
            } else {
                $masterBlueprint = [
                    ['Klatka piersiowa', 'Biceps'],
                    ['Plecy', 'Triceps'],
                    ['Nogi'],
                    ['Barki', 'Brzuch']
                ];
                $exercisesPerMuscle = 3; 
            }
        } else {
            $masterBlueprint = [['Klatka piersiowa', 'Barki', 'Triceps'], ['Plecy', 'Biceps'], ['Nogi', 'Brzuch']];
            $exercisesPerMuscle = 2;
        }

        $anatomyMap = [
            'Klatka piersiowa' => ['Klatka piersiowa'],
            'Plecy'            => ['Plecy', 'Najszerszy grzbietu', 'Czworoboczny grzbietu'],
            'Nogi'             => ['Nogi', 'Czworogłowy uda', 'Dwugłowy uda', 'Przywodziciele'],
            'Pośladki'         => ['Pośladki', 'Odwodziciele'], 
            'Barki'            => ['Barki'],
            'Biceps'           => ['Biceps'],
            'Triceps'          => ['Triceps'],
            'Brzuch'           => ['Brzuch', 'Prosty brzucha', 'Skośne brzucha'],
            'Łydki'            => ['Łydki'] 
        ];

        $totalSessions = $daysPerWeek;
        $activeBlueprint = $masterBlueprint;
        $allMachines = $machineRepository->findAll();
        $bannedMachinesIds = $excludedMusclesIds;

        $plan = new WorkoutPlan();
        $plan->setUser($user);
        
        $planSuffix = ($daysPerWeek == 2) ? ' (Split: Góra/Dół)' : ' (' . $daysPerWeek . ' dni / ' . $weeks . ' tyg.)';
        $plan->setName($goalName . $planSuffix);
        
        $entityManager->persist($plan);

        for ($sessionNum = 1; $sessionNum <= $totalSessions; $sessionNum++) {
            $blueprintIndex = ($sessionNum - 1) % count($activeBlueprint);
            $targetMuscles = $activeBlueprint[$blueprintIndex];

            $dailyExercises = [];
            foreach ($targetMuscles as $targetMuscle) {
                $specificMusclesForTarget = $anatomyMap[$targetMuscle] ?? [$targetMuscle];
                $muscleSpecificMachines = [];
                
                foreach ($allMachines as $machine) {
                    if (in_array($machine->getId(), $bannedMachinesIds)) continue;
                    
                    $machineNameLower = strtolower($machine->getName());
                    $mechType = strtolower($machine->getMechanicType() ?? '');
                    
                    if ($mechType === 'cardio' || 
                        str_contains($machineNameLower, 'rower') || 
                        str_contains($machineNameLower, 'bieżnia') || 
                        str_contains($machineNameLower, 'orbitrek')) {
                        continue;
                    }
                    
                    $primaryNames = array_map(fn($m) => $m->getName(), $machine->getPrimaryMuscles()->toArray());
                    if (!empty(array_intersect($primaryNames, $specificMusclesForTarget))) {
                        $muscleSpecificMachines[] = $machine;
                    }
                }
                
                shuffle($muscleSpecificMachines);
                $dailyExercises = array_merge($dailyExercises, array_slice($muscleSpecificMachines, 0, $exercisesPerMuscle));
            }

            $usedMachineIdsInSession = []; 
            $exerciseCount = 0;

            foreach ($dailyExercises as $machine) {
                if (in_array($machine->getId(), $usedMachineIdsInSession)) continue;
                
                if ($exerciseCount >= 6) break;

                $usedMachineIdsInSession[] = $machine->getId();

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
                    $exercise->setSets(3);
                    $exercise->setReps('8-12');
                }
                
                $entityManager->persist($exercise);
                $exerciseCount++;
            }
        }

        $entityManager->flush();
        $request->getSession()->remove('gen_days');
        $request->getSession()->remove('gen_weeks');

        return $this->redirectToRoute('app_generator_success', ['id' => $plan->getId()]);
    }#[Route('/generator/success/{id}', name: 'app_generator_success')]
    public function success(WorkoutPlan $plan, Request $request, \App\Repository\MachineRepository $machineRepo): Response
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
            'daysPerWeek' => count($sessions),
            'allMachines' => $machineRepo->findAll()
        ]);
    }
}