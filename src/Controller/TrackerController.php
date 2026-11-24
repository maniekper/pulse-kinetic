<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\WorkoutSession;
use App\Service\ProgressionAnalyzerService;
use App\Entity\WorkoutSet;
use App\Repository\WorkoutPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\CardioSession;
use App\Entity\Machine;
use App\Entity\User;

class TrackerController extends AbstractController
{
    #[Route('/tracker/start/{plan_id}', name: 'app_tracker_start')]
    public function start(
        int $plan_id,
        Request $request,
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

        // 1. Zbieramy tylko aktywne dni tego planu
        $activeDays = [];
        foreach ($plan->getWorkoutExercises() as $ex) {
            $exDay = $ex->getDayNumber();
            if (!in_array($exDay, $activeDays, true)) {
                $activeDays[] = $exDay;
            }
        }
        sort($activeDays);

        if (empty($activeDays)) {
            $this->addFlash('error', 'Ten plan nie posiada żadnych aktywnych ćwiczeń. Wygeneruj nowy.');
            return $this->redirectToRoute('app_generator_start');
        }

        // 2. Jeśli z dashboardu przyszedł konkretny dzień (?day=X), używamy go; w przeciwnym razie liczymy TYLKO dla tego planu
        $requestedDay = $request->query->getInt('day', 0);

        if ($requestedDay > 0 && in_array($requestedDay, $activeDays, true)) {
            $day = $requestedDay;
        } else {
            $completedSessionsCount = (int) $entityManager->createQuery(
                'SELECT COUNT(s.id) FROM App\Entity\WorkoutSession s WHERE s.workoutPlan = :plan AND s.endedAt IS NOT NULL'
            )
                ->setParameter('plan', $plan)
                ->getSingleScalarResult();

            $activeDaysCount = count($activeDays);
            $activeDayIndex = $completedSessionsCount % $activeDaysCount;
            $day = $activeDays[$activeDayIndex];
        }

        // 3. Tworzymy nową sesję treningową
        $session = new WorkoutSession();
        $session->setUser($this->getUser());
        $session->setWorkoutPlan($plan);
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
    public function session(WorkoutSession $session, EntityManagerInterface $em): Response
    {
        $cardioMachines = $em->getRepository(Machine::class)->findBy(['mechanicType' => 'cardio']);

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
            'cardioMachines' => $cardioMachines,
        ]);
    }

    #[Route('/tracker/set/complete/{id}', name: 'app_tracker_complete_set', methods: ['POST'])]
    public function completeSet(
        WorkoutSet $workoutSet,
        Request $request,
        EntityManagerInterface $entityManager,
        ProgressionAnalyzerService $analyzer,
        \App\Service\RecordTrackerService $recordTracker
    ): JsonResponse {
        if ($workoutSet->getWorkoutSession()->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Odmowa dostępu'], 403);
        }

        $data = json_decode($request->getContent(), true);

        $workoutSet->setWeight((float) $data['weight']);
        $workoutSet->setReps((int) $data['reps']);

        $workoutSet->setIsCompleted(true);
        $workoutSet->setLoggedAt(new \DateTime());

        $entityManager->flush();

        // --- AKTYWACJA REKORDÓW ---
        $recordTracker->checkAndSaveRecord($workoutSet);

        // ------------------------------------------------------------------
        // ANALIZA PROGRESJI I CZASU PRZERWY
        // ------------------------------------------------------------------
        $machine = $workoutSet->getMachine();
        $type = strtolower(trim((string)$machine->getMechanicType()));
        $isCompound = in_array($type, ['compound', 'wielostawowe', 'wielostawowa', 'złożone']);

        $targetReps = isset($data['target_reps']) ? (int) $data['target_reps'] : 10;

        $analysis = $analyzer->analyzeSet($workoutSet, $targetReps, $isCompound);

        return new JsonResponse([
            'success' => true,
            'analysis' => $analysis
        ]);
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

        // ------------------------------------------------------------------
        // WERYFIKACJA STAGNACJI I KOŃCA MEZOCYKLU (ZASADA STAŁOŚCI)
        // ------------------------------------------------------------------
        $plan = $session->getWorkoutPlan();

        if ($plan) {
            $uniqueDaysCount = count($em->getRepository(\App\Entity\WorkoutExercise::class)->createQueryBuilder('e')
                ->select('e.dayNumber')
                ->where('e.workoutPlan = :plan')
                ->setParameter('plan', $plan)
                ->groupBy('e.dayNumber')
                ->getQuery()
                ->getResult());

            $completedSessionsCount = (int) $em->createQuery(
                'SELECT COUNT(s.id) FROM App\Entity\WorkoutSession s WHERE s.workoutPlan = :plan AND s.endedAt IS NOT NULL'
            )
                ->setParameter('plan', $plan)
                ->getSingleScalarResult();

            $weeks = 4;
            if (preg_match('/\/ (\d+) tyg/', $plan->getName(), $matches)) {
                $weeks = (int) $matches[1];
            }

            $totalPlannedSessions = $uniqueDaysCount * $weeks;

            if ($completedSessionsCount >= $totalPlannedSessions) {
                $plan->setName($plan->getName() . ' [ZAKOŃCZONY]');
                $em->flush();

                $this->addFlash('success', 'GRATULACJE! Ukończyłeś ' . $totalPlannedSessions . ' treningów i zamknąłeś ten mezocykl! Organizm zaadaptował się do starych bodźców. Pora wygenerować nowy protokół.');
                return $this->redirectToRoute('app_generator_start');
            }
        }

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

    #[Route('/tracker/session/{id}/add-cardio', name: 'app_tracker_add_cardio', methods: ['POST'])]
    public function addCardio(WorkoutSession $session, Request $request, EntityManagerInterface $em): Response
    {
        if ($session->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Odmowa dostępu.');
        }

        $machineId = $request->request->get('machine_id');
        $machine = $em->getRepository(Machine::class)->find($machineId);

        if ($machine) {
            $cardio = new CardioSession();
            $cardio->setWorkoutSession($session);
            $cardio->setMachine($machine);
            $cardio->setDurationMinutes((int) $request->request->get('duration'));

            // Pobieranie Prędkości
            $speed = $request->request->get('speed');
            if ($speed !== null && $speed !== '') {
                $cardio->setSpeed((float) $speed);
            }

            // Pobieranie Pochylenia
            $incline = $request->request->get('incline');
            if ($incline !== null && $incline !== '') {
                $cardio->setIncline((float) $incline);
            }

            $em->persist($cardio);
            $em->flush();

            $this->addFlash('success', 'Zarejestrowano parametry Cardio na maszynie: ' . $machine->getName());
        }

        return $this->redirectToRoute('app_tracker_session', ['id' => $session->getId()]);
    }
    #[Route('/tracker/machine/{id}/increment-step', name: 'app_tracker_update_increment_step', methods: ['POST'])]
    public function updateIncrementStep(
        Machine $machine,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Brak autoryzacji'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $stepKg = isset($data['step_kg']) ? round((float) $data['step_kg'], 2) : 0.0;

        if ($stepKg <= 0 || $stepKg > 200) {
            return new JsonResponse(['error' => 'Nieprawidłowy krok obciążenia'], 400);
        }

        $user->setExerciseIncrementStep($machine->getId(), $stepKg);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'machine_id' => $machine->getId(),
            'step_kg' => $stepKg,
        ]);
    }
}
