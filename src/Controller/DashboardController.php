<?php

namespace App\Controller;

use App\Entity\WorkoutPlan;
use App\Entity\WorkoutSession;
use App\Entity\WorkoutSet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $plan = $em->getRepository(WorkoutPlan::class)->findOneBy(['user' => $user], ['id' => 'DESC']);

        if (!$plan) {
            $this->addFlash('warning', 'Witaj w systemie! Zanim ruszysz, wygeneruj swój pierwszy protokół operacyjny.');
            return $this->redirectToRoute('app_generator_start'); 
        }

        // --- ZLICZAMY TYLKO FIZYCZNIE ZAKOŃCZONE SESJE ---
        $sessions = $em->getRepository(WorkoutSession::class)->findBy(['user' => $user]);
        $totalTonnage = 0;
        $completedSessionsCount = 0;
        
        foreach ($sessions as $session) {
            if ($session->getEndedAt() !== null) {
                $completedSessionsCount++;
                $totalTonnage += $session->getTotalTonnage();
            }
        }

        $qb = $em->createQueryBuilder();
        $maxLifts = $qb->select('m.name as machineName, MAX(ws.weight) as maxWeight')
            ->from(WorkoutSet::class, 'ws')
            ->join('ws.machine', 'm')
            ->join('ws.workoutSession', 'sess')
            ->where('sess.user = :user')
            ->andWhere('ws.isCompleted = true')
            ->setParameter('user', $user)
            ->groupBy('m.id')
            ->orderBy('maxWeight', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        $estimatedTimeMinutes = 0;
        if ($plan && $user instanceof \App\Entity\User && $user->getCurrentGoal()) {
            $goal = $user->getCurrentGoal();
            $totalSeconds = 600; 
            
            $maxDays = 0;
            foreach ($plan->getWorkoutExercises() as $ex) {
                if ($ex->getDayNumber() > $maxDays) {
                    $maxDays = $ex->getDayNumber();
                }
            }
            
            $nextDayNumber = $maxDays > 0 ? ($completedSessionsCount % $maxDays) + 1 : 1;

            foreach ($plan->getWorkoutExercises() as $workoutExercise) {
                if ($workoutExercise->getDayNumber() === $nextDayNumber) {
                    $machine = $workoutExercise->getMachine();
                    $sets = $workoutExercise->getSets();
                    
                    $repsRaw = $workoutExercise->getReps();
                    $repsArray = explode('-', $repsRaw);
                    $avgReps = (count($repsArray) === 2) ? ((int)$repsArray[0] + (int)$repsArray[1]) / 2 : (int)$repsArray[0];

                    $workTime = $sets * $avgReps * 4;

                    $restTimePerSet = ($machine && $machine->getMechanicType() === 'compound') 
                        ? $goal->getRestCompoundSeconds() 
                        : $goal->getRestIsolationSeconds();
                    
                    $restTime = ($sets > 0 ? $sets - 1 : 0) * $restTimePerSet;
                    $transitionTime = $goal->getRestBetweenMachinesSeconds();

                    $totalSeconds += $workTime + $restTime + $transitionTime;
                }
            }
            $estimatedTimeMinutes = (int) round($totalSeconds / 60);
        }

        $chartData = [];
        $maxChartValue = 0;
        $today = new \DateTime();

        for ($i = 6; $i >= 0; $i--) {
            $date = (clone $today)->modify("-{$i} days");
            $dayName = strtoupper($date->format('D')); 
            
            $dayTonnage = 0;
            foreach ($sessions as $session) {
                if ($session->getStartedAt() && $session->getStartedAt()->format('Y-m-d') === $date->format('Y-m-d')) {
                    $dayTonnage += $session->getTotalTonnage();
                }
            }
            
            $chartData[] = [
                'day' => $dayName,
                'tonnage' => $dayTonnage
            ];
            
            if ($dayTonnage > $maxChartValue) {
                $maxChartValue = $dayTonnage;
            }
        }
        
        if ($maxChartValue === 0) {
            $maxChartValue = 1;
        }

        return $this->render('dashboard/index.html.twig', [
            'plan' => $plan,
            'totalTonnage' => $totalTonnage,
            'completedSessionsCount' => $completedSessionsCount,
            'maxLifts' => $maxLifts,
            'chartData' => $chartData,
            'maxChartValue' => $maxChartValue,
            'estimatedTimeMinutes' => $estimatedTimeMinutes,
        ]);
    }
}