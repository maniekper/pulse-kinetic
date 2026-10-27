<?php

namespace App\Controller;

use App\Service\RecordTrackerService;
use App\Entity\WorkoutSession;
use App\Repository\RoutineRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\WorkoutCalculatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WorkoutSessionController extends AbstractController
{
    #[Route('/session/start/{routineId}', name: 'app_session_start')]
    public function startSession(
        int $routineId,
        RoutineRepository $routineRepo,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        $routine = $routineRepo->find($routineId);

        // Zabezpieczenie przed uruchomieniem cudzego planu
        if (!$user || !$routine || $routine->getUser() !== $user) {
            return $this->redirectToRoute('app_dashboard');
        }

        $session = new WorkoutSession();
        $session->setUser($user);
        $session->setRoutine($routine);
        $session->setStartedAt(new \DateTime());

        $em->persist($session);
        $em->flush();

        // Przekierowanie do widoku aktywnej sesji
        return $this->redirectToRoute('app_session_active', ['id' => $session->getId()]);
    }

    #[Route('/session/{id}/active', name: 'app_session_active')]
    public function activeSession(
        int $id,
        EntityManagerInterface $em,
        WorkoutCalculatorService $calculator
    ): Response {
        $session = $em->getRepository(WorkoutSession::class)->find($id);

        if (!$session || $session->getUser() !== $this->getUser() || $session->getEndedAt() !== null) {
            return $this->redirectToRoute('app_dashboard');
        }

        $routine = $session->getRoutine();
        $user = $this->getUser();
        $targets = [];
        $lastSets = []; // Tabela na ostanie wyniki

        foreach ($routine->getRoutineMachines() as $rm) {
            $machine = $rm->getMachine();
            $machineId = $machine->getId();
            
            // Wytyczne
            $targets[$machineId] = $calculator->calculateTargets($user, $machine);
            
            // Szukamy ostatniej zapisanej serii dla tego użytkownika i maszyny
            $lastSet = $em->createQueryBuilder()
                ->select('s')
                ->from(\App\Entity\WorkoutSet::class, 's')
                ->join('s.workoutSession', 'ws')
                ->where('ws.user = :user')
                ->andWhere('s.machine = :machine')
                ->setParameter('user', $user)
                ->setParameter('machine', $machine)
                ->orderBy('s.loggedAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
                
            $lastSets[$machineId] = $lastSet;
        }

        return $this->render('session/active.html.twig', [
            'session' => $session,
            'routine' => $routine,
            'targets' => $targets,
            'lastSets' => $lastSets
        ]);
    }

    #[Route('/session/{id}/log-set/{machineId}', name: 'app_session_log_set', methods: ['POST'])]
    public function logSet(
        int $id,
        int $machineId,
        Request $request,
        EntityManagerInterface $em,
        RecordTrackerService $recordTracker
    ): Response {
        $session = $em->getRepository(WorkoutSession::class)->find($id);
        $machine = $em->getRepository(\App\Entity\Machine::class)->find($machineId);

        if (!$session || !$machine || $session->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $weight = $request->request->get('weight');
        $reps = $request->request->get('reps');

        if ($weight !== null && $reps !== null) {
            // Obliczamy, która to seria na tej maszynie w obecnej sesji
            $setNumber = $em->getRepository(\App\Entity\WorkoutSet::class)->count([
                'workoutSession' => $session,
                'machine' => $machine
            ]) + 1;

            $set = new \App\Entity\WorkoutSet();
            $set->setWorkoutSession($session);
            $set->setMachine($machine);
            $set->setSetNumber($setNumber);
            $set->setWeight((float)$weight);
            $set->setReps((int)$reps);
            $set->setIsCompleted(true);
            $set->setLoggedAt(new \DateTime());

            $em->persist($set);
            $em->flush();

            // Uruchamiamy weryfikację rekordu
            $newRecord = $recordTracker->checkAndSaveRecord($set);

            // Zapisujemy komunikat, który wyświetlimy na ekranie
            if ($newRecord) {
                $this->addFlash('success', '🏆 NOWY REKORD! ' . $machine->getName() . ': ' . $weight . 'kg x ' . $reps);
            } else {
                $this->addFlash('success', 'Zapisano serię: ' . $weight . 'kg x ' . $reps);
            }
        }

        return $this->redirectToRoute('app_session_active', ['id' => $session->getId()]);
    }

    #[Route('/session/{id}/end', name: 'app_session_end')]
    public function endSession(
        int $id,
        EntityManagerInterface $em
    ): Response {
        $session = $em->getRepository(WorkoutSession::class)->find($id);

        // Zabezpieczenie przed zamknięciem cudzej sesji lub już zakończonej
        if (!$session || $session->getUser() !== $this->getUser() || $session->getEndedAt() !== null) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Zapisanie czasu zakończenia
        $session->setEndedAt(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'Trening zakończony! Dobra robota.');

        return $this->redirectToRoute('app_dashboard');
    }
}