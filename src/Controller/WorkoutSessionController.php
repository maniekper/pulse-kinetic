<?php

namespace App\Controller;

use App\Entity\WorkoutSet;
use App\Service\RecordTrackerService;
use App\Entity\Machine;
use App\Entity\WorkoutSession;
use App\Repository\RoutineRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class WorkoutSessionController extends AbstractController
{
    #[Route('/session/start', name: 'app_session_start')]
    public function startSession(
        UserRepository $userRepo,
        RoutineRepository $routineRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $userRepo->findOneBy(['email' => 'test@pulsekinetic.com']);
        $routine = $routineRepo->findOneBy(['name' => 'Trening Góra - Siła']);

        if (!$user || !$routine) {
            return $this->json(['error' => 'Brak użytkownika lub przypisanego planu.']);
        }

        $session = new WorkoutSession();
        $session->setUser($user);
        $session->setRoutine($routine);
        $session->setStartedAt(new \DateTime()); // Rejestrujemy dokładny czas startu

        $em->persist($session);
        $em->flush();

        return $this->json([
            'status' => 'Trening rozpoczety',
            'plan' => $routine->getName(),
            'czas_startu' => $session->getStartedAt()->format('Y-m-d H:i:s'),
            'id_sesji' => $session->getId()
        ]);
    }

    #[Route('/session/{id}/log-test-set', name: 'app_session_log_test_set')]
    public function logTestSet(
        int $id,
        EntityManagerInterface $em,
        RecordTrackerService $recordTracker
    ): JsonResponse {
        $session = $em->getRepository(WorkoutSession::class)->find($id);
        $machine = $em->getRepository(Machine::class)->findOneBy(['name' => 'Wyciskanie sztangi na ławce płaskiej']);

        if (!$session || !$machine) {
            return $this->json(['error' => 'Brak sesji lub maszyny w bazie.']);
        }

        $set = new WorkoutSet();
        $set->setWorkoutSession($session);
        $set->setMachine($machine);
        $set->setSetNumber(1);
        $set->setWeight(85.0); // Zwiększamy ciężar do 85 kg!
        $set->setReps(4);
        $set->setIsCompleted(true);
        $set->setLoggedAt(new \DateTime());

        $em->persist($set);
        $em->flush();

        // Uruchamiamy w tle sprawdzanie życiówki
        $newRecord = $recordTracker->checkAndSaveRecord($set);

        return $this->json([
            'status' => 'Seria zapisana',
            'sesja_id' => $session->getId(),
            'maszyna' => $machine->getName(),
            'ciezar' => $set->getWeight() . ' kg',
            'powtorzenia' => $set->getReps(),
            'nowy_rekord' => $newRecord ? 'TAK! Nowy PR: ' . $newRecord->getMaxWeight() . 'kg x ' . $newRecord->getMaxRepsAtMaxWeight() : 'Nie'
        ]);
    }

    #[Route('/session/{id}/end', name: 'app_session_end')]
    public function endSession(
        int $id,
        EntityManagerInterface $em
    ): JsonResponse {
        $session = $em->getRepository(WorkoutSession::class)->find($id);

        if (!$session) {
            return $this->json(['error' => 'Brak sesji treningowej o podanym ID.']);
        }

        // 1. Zapisujemy czas zakończenia
        $session->setEndedAt(new \DateTime());

        // 2. Obliczamy całkowity tonaż (ciężar * powtórzenia) dla wszystkich ukończonych serii
        $tonnage = 0;
        foreach ($session->getWorkoutSets() as $set) {
            if ($set->isCompleted()) {
                $tonnage += ($set->getWeight() * $set->getReps());
            }
        }
        
        $session->setTotalTonnage($tonnage);
        $em->flush();

        // 3. Zwracamy podsumowanie
        $duration = $session->getStartedAt()->diff($session->getEndedAt());

        return $this->json([
            'status' => 'Trening zakonczony',
            'czas_trwania' => $duration->format('%H:%I:%S'),
            'calkowity_tonaz' => $session->getTotalTonnage() . ' kg'
        ]);
    }
}