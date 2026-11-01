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

        // 1. Aktywny plan użytkownika
        $plan = $em->getRepository(WorkoutPlan::class)->findOneBy(['user' => $user], ['id' => 'DESC']);

        // 2. Pobranie historii sesji (do zliczenia tonażu i numeru treningu)
        $sessions = $em->getRepository(WorkoutSession::class)->findBy(['user' => $user]);
        $totalTonnage = 0;
        $completedSessionsCount = count($sessions);
        
        foreach ($sessions as $session) {
            $totalTonnage += $session->getTotalTonnage();
        }

        // 3. Wyciągnięcie TOP 3 Rekordów (Max Lifts)
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

        // 4. Szacowanie czasu treningu (Algorytm TUT) dla BIEŻĄCEJ sesji
        $estimatedTimeMinutes = 0;
        if ($plan && $user instanceof \App\Entity\User && $user->getCurrentGoal()) {
            $goal = $user->getCurrentGoal();
            $totalSeconds = 600; // 10 minut bazy na rozgrzewkę
            
            // ZNALEZIENIE MAKSYMALNEJ LICZBY DNI W PLANIE
            $maxDays = 0;
            foreach ($plan->getWorkoutExercises() as $ex) {
                if ($ex->getDayNumber() > $maxDays) {
                    $maxDays = $ex->getDayNumber();
                }
            }
            
            // ZAPĘTLENIE (MODULO) - zapobiega szukaniu "Dnia 15" w 3-dniowym planie
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

        // 5. Dane do wykresu (Ostatnie 7 dni)
        $chartData = [];
        $maxChartValue = 0;
        $today = new \DateTime();

        for ($i = 6; $i >= 0; $i--) {
            $date = (clone $today)->modify("-{$i} days");
            $dayName = strtoupper($date->format('D')); // MON, TUE, WED...
            
            $dayTonnage = 0;
            foreach ($sessions as $session) {
                // Sprawdzamy czy sesja odbyła się danego dnia
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
        
        // Zabezpieczenie przed dzieleniem przez zero przy pustym wykresie
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