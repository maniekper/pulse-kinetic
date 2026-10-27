<?php

namespace App\Controller;

use App\Entity\Routine;
use App\Entity\RoutineMachine;
use App\Repository\MachineRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class RoutineController extends AbstractController
{
    #[Route('/routine/create-test', name: 'app_routine_create_test')]
    public function createTestRoutine(
        UserRepository $userRepo,
        MachineRepository $machineRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        // 1. Pobieramy testowego użytkownika i dostępne maszyny
        $user = $userRepo->findOneBy(['email' => 'test@pulsekinetic.com']);
        $machine1 = $machineRepo->findOneBy(['name' => 'Wyciskanie sztangi na ławce płaskiej']);
        $machine2 = $machineRepo->findOneBy(['name' => 'Uginanie ramion z hantlami na ławce skośnej']);

        if (!$user || !$machine1 || !$machine2) {
            return $this->json(['error' => 'Brak danych testowych w bazie.']);
        }

        // 2. Tworzymy nowy Szablon Treningowy
        $routine = new Routine();
        $routine->setName('Trening Góra - Siła');
        $routine->setUser($user);
        $em->persist($routine); // Dodajemy do kolejki zapisu

        // 3. Przypisujemy pierwsze ćwiczenie (Wyciskanie) jako pierwsze w kolejności
        $routineMachine1 = new RoutineMachine();
        $routineMachine1->setRoutine($routine);
        $routineMachine1->setMachine($machine1);
        $routineMachine1->setSortOrder(1);
        $em->persist($routineMachine1);

        // 4. Przypisujemy drugie ćwiczenie (Biceps) jako drugie w kolejności
        $routineMachine2 = new RoutineMachine();
        $routineMachine2->setRoutine($routine);
        $routineMachine2->setMachine($machine2);
        $routineMachine2->setSortOrder(2);
        $em->persist($routineMachine2);

        // 5. Fizyczny zapis całego planu do bazy MySQL (wykonuje się jedna duża transakcja)
        $em->flush();

        return $this->json([
            'status' => 'Sukces',
            'wiadomosc' => 'Plan treningowy został utworzony.',
            'szablon' => $routine->getName(),
            'cwiczenia' => [
                '1' => $machine1->getName(),
                '2' => $machine2->getName()
            ]
        ]);
    }
}