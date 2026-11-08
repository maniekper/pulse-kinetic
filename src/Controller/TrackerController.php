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
    #[Route('/tracker/start/{plan_id}', name: 'app_tracker_start')]
    public function start(
        int $plan_id, 
        WorkoutPlanRepository $planRepo, 
        EntityManagerInterface $entityManager
    ): Response {
        $plan = $planRepo->find($plan_id);
        
        if (!$plan || $plan->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Brak dostępu do tego planu.');
        }

        // --- AUTOMATYCZNE CZYSZCZENIE WISZĄCEJ SESJI ---
        $activeSession = $entityManager->getRepository(WorkoutSession::class)->findOneBy([
            'user' => $this->getUser(),
            'endedAt' => null
        ]);

        if ($activeSession) {
            $entityManager->remove($activeSession);
            $entityManager->flush(); 
        }

        // --- AUTOMATYCZNE WYLICZANIE DNIA (Omijanie pustych dni) ---
        $completedSessionsCount = (int) $entityManager->createQuery(
            'SELECT COUNT(s.id) FROM App\\Entity\\WorkoutSession s WHERE s.user = :user AND s.endedAt IS NOT NULL'
        )
            ->setParameter('user', $this->getUser())
            ->getSingleScalarResult();

        // 1. Zbieramy tylko aktywne dni
        $activeDays = [];
        foreach ($plan->getWorkoutExercises() as $ex) {
            $exDay = $ex->getDayNumber();
            if (!in_array($exDay, $activeDays)) {
                $activeDays[] = $exDay;
            }
        }
        sort($activeDays);

        if (empty($activeDays)) {
            $this->addFlash('error', 'Ten plan nie posiada żadnych aktywnych ćwiczeń. Wygeneruj nowy.');
            return $this->redirectToRoute('app_generator_start');
        }

        // 2. Wyliczamy następny AKTYWNY dzień
        $activeDaysCount = count($activeDays);
        $activeDayIndex = $completedSessionsCount % $activeDaysCount;
        $day = $activeDays[$activeDayIndex];

        // 3. Tworzymy nową sesję treningową
        $session = new WorkoutSession();
        $session->setUser($this->getUser());
        $session->setStartedAt(new \DateTime());
        $entityManager->persist($session);

        // 4. Pobieramy ćwiczenia tylko dla wyliczonego, aktywnego dnia
        $exercisesForDay = [];
        foreach ($plan->getWorkoutExercises() as $ex) {
            if ($ex->getDayNumber() === $day) {
                $exercisesForDay[] = $ex;
            }
        }

        // 5. Generujemy puste serie
        foreach ($exercisesForDay as $ex) {
            $machine = $ex->getMachine();
            $setsCount = $ex->getSets();

            $lastSet = $entityManager->createQuery(
                'SELECT ws.weight, ws.reps 
                 FROM App\Entity\WorkoutSet ws 
                 JOIN ws.workoutSession s 
                 WHERE s.user = :user AND ws.machine = :machine AND ws.isCompleted = true 
                 ORDER BY ws.loggedAt DESC'
            )
            ->setParameter('user', $this->getUser())
            ->setParameter('machine', $machine)
            ->setMaxResults(1)
            ->getOneOrNullResult();

            if ($lastSet && (int) $lastSet['reps'] > 0) {
                $startingWeight = (float) $lastSet['weight'];
                $startingReps = (int) $lastSet['reps'];
            } else {
                $startingWeight = 0.0;
                $repsString = $ex->getReps();
                $repsParts = explode('-', (string) $repsString);
                $startingReps = (int) end($repsParts);
            }

            for ($i = 1; $i <= $setsCount; $i++) {
                $set = new WorkoutSet();
                $set->setWorkoutSession($session);
                $set->setMachine($machine);
                $set->setSetNumber($i);
                $set->setWeight($startingWeight);
                $set->setReps($startingReps);
                $set->setIsCompleted(false);
                $set->setLoggedAt(new \DateTime());
                
                $entityManager->persist($set);
            }
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_tracker_session', ['id' => $session->getId()]);
    }

   #[Route('/tracker/session/{id}', name: 'app_tracker_session')]
    public function session(WorkoutSession $session): Response
    {
        if ($session->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $groupedSets = [];
        $restTimes = [];
        
        $user = $this->getUser();
        $goal = method_exists($user, 'getCurrentGoal')
            ? call_user_func([$user, 'getCurrentGoal'])
            : null;
        
        $restCompound = ($goal && $goal->getRestCompoundSeconds()) ? $goal->getRestCompoundSeconds() : 180;
        $restIsolation = ($goal && $goal->getRestIsolationSeconds()) ? $goal->getRestIsolationSeconds() : 90;

        foreach ($session->getWorkoutSets() as $set) {
            $machine = $set->getMachine();
            $machineName = $machine->getName();
            $groupedSets[$machineName][] = $set;
            
            $type = strtolower(trim((string)$machine->getMechanicType()));
            
            if (!isset($restTimes[$machineName])) {
                if (in_array($type, ['compound', 'wielostawowe', 'wielostawowa', 'złożone'])) {
                    $restTimes[$machineName] = $restCompound;
                } else {
                    $restTimes[$machineName] = $restIsolation;
                }
            }
        }

        return $this->render('tracker/session.html.twig', [
            'session' => $session,
            'groupedSets' => $groupedSets,
            'restTimes' => $restTimes,
        ]);
    }

    #[Route('/tracker/set/complete/{id}', name: 'app_tracker_complete_set', methods: ['POST'])]
    public function completeSet(WorkoutSet $workoutSet, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($workoutSet->getWorkoutSession()->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Odmowa dostępu'], 403);
        }
        
        $data = json_decode($request->getContent(), true);
        
        $workoutSet->setWeight((float) $data['weight']);
        $workoutSet->setReps((int) $data['reps']);
        $workoutSet->setIsCompleted(true);
        $workoutSet->setLoggedAt(new \DateTime());
        
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/tracker/finish/{id}', name: 'app_tracker_finish', methods: ['POST'])]
    public function finish(WorkoutSession $session, EntityManagerInterface $em): Response
    {
        $session->setEndedAt(new \DateTime());

        $totalTonnage = 0;
        foreach ($session->getWorkoutSets() as $set) {
            if ($set->isCompleted()) {
                $totalTonnage += ($set->getWeight() * $set->getReps());
            }
        }
        $session->setTotalTonnage($totalTonnage);

        $em->flush();

        $this->addFlash('success', 'Trening ukończony! Zarejestrowano tonaż: ' . number_format($totalTonnage, 0, ',', ' ') . ' kg.');
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/tracker/swap/{exercise_id}/{new_machine_id}', name: 'app_tracker_swap_machine', methods: ['POST'])]
    public function swapMachine(
        int $exercise_id,
        int $new_machine_id,
        EntityManagerInterface $em,
        Request $request 
    ): Response {
        $exercise = $em->getRepository(\App\Entity\WorkoutExercise::class)->find($exercise_id);
        $newMachine = $em->getRepository(\App\Entity\Machine::class)->find($new_machine_id);
        
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$exercise || !$newMachine || $exercise->getWorkoutPlan()->getUser() !== $user) {
            throw $this->createAccessDeniedException('Odmowa dostępu.');
        }

        $oldMachine = $exercise->getMachine();
        if ($oldMachine) {
            $user->addBannedMachine($oldMachine);
        }
        
        $exercise->setMachine($newMachine);
        $em->flush();

        $this->addFlash('success', 'Sprzęt podmieniony. Stara maszyna trafiła do wykluczeń.');

        $referer = $request->headers->get('referer');
        
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_generator_success', ['id' => $exercise->getWorkoutPlan()->getId()]);
    }
}