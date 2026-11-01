<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\WorkoutSession;
use App\Entity\WorkoutSet;
use App\Repository\WorkoutPlanRepository;
use Doctrine\ORM\EntityManagerInterface;

class TrackerController extends AbstractController
{
    #[Route('/tracker/start/{plan_id}/{day}', name: 'app_tracker_start')]
    public function start(
        int $plan_id, 
        int $day, 
        WorkoutPlanRepository $planRepo, 
        EntityManagerInterface $entityManager
    ): Response {
        $plan = $planRepo->find($plan_id);
        
        if (!$plan || $plan->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Brak dostępu do tego planu.');
        }

        // 1. Tworzymy nową sesję treningową
        $session = new WorkoutSession();
        $session->setUser($this->getUser());
        $session->setStartedAt(new \DateTime());
        $entityManager->persist($session);

        // 2. Pobieramy ćwiczenia tylko dla konkretnego dnia (np. Trening 1)
        $exercisesForDay = [];
        foreach ($plan->getWorkoutExercises() as $ex) {
            if ($ex->getDayNumber() === $day) {
                $exercisesForDay[] = $ex;
            }
        }

        // 3. Generujemy puste serie (WorkoutSet) do wypełnienia na siłowni
        foreach ($exercisesForDay as $ex) {
            $machine = $ex->getMachine();
            $setsCount = $ex->getSets();

            for ($i = 1; $i <= $setsCount; $i++) {
                $set = new WorkoutSet();
                $set->setWorkoutSession($session);
                $set->setMachine($machine);
                $set->setSetNumber($i);
                $set->setWeight(0.0);
                $set->setReps(0);
                $set->setIsCompleted(false);
                $set->setLoggedAt(new \DateTime());
                
                $entityManager->persist($set);
            }
        }

        $entityManager->flush();

        // Przekierowanie do widoku aktywnego treningu
        return $this->redirectToRoute('app_tracker_session', ['id' => $session->getId()]);
    }

    #[Route('/tracker/session/{id}', name: 'app_tracker_session')]
    public function session(WorkoutSession $session): Response
    {
        if ($session->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Grupowanie serii według maszyn, aby łatwo wyświetlić je w widoku
        $groupedSets = [];
        foreach ($session->getWorkoutSets() as $set) {
            $machineName = $set->getMachine()->getName();
            $groupedSets[$machineName][] = $set;
        }

        return $this->render('tracker/session.html.twig', [
            'session' => $session,
            'groupedSets' => $groupedSets,
            'restTime' => 120
        ]);
    }

    #[Route('/tracker/set/complete/{id}', name: 'app_tracker_complete_set', methods: ['POST'])]
    public function completeSet(WorkoutSet $workoutSet, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($workoutSet->getWorkoutSession()->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Odmowa dostępu'], 403);
        }
        
        $data = json_decode($request->getContent(), true);
        
        // Aktualizujemy dane serii pobrane z frontendu
        $workoutSet->setWeight((float) $data['weight']);
        $workoutSet->setReps((int) $data['reps']);
        $workoutSet->setIsCompleted(true);
        $workoutSet->setLoggedAt(new \DateTime());
        
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }
}