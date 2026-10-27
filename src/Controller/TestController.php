<?php

namespace App\Controller;

use App\Repository\MachineRepository;
use App\Repository\UserRepository;
use App\Service\WorkoutCalculatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    #[Route('/test-calculator', name: 'app_test_calculator')]
    public function index(
        UserRepository $userRepo,
        MachineRepository $machineRepo,
        WorkoutCalculatorService $calculator
    ): JsonResponse {
        // Pobieramy dane testowe, które wgraliśmy przez Fixtures
        $user = $userRepo->findOneBy(['email' => 'test@pulsekinetic.com']);
        $compoundMachine = $machineRepo->findOneBy(['mechanicType' => 'compound']);
        $isolationMachine = $machineRepo->findOneBy(['mechanicType' => 'isolation']);

        if (!$user || !$compoundMachine || !$isolationMachine) {
            return $this->json(['error' => 'Brak danych testowych!']);
        }

        // Przepuszczamy dane przez nasz nowy serwis
        $compoundTargets = $calculator->calculateTargets($user, $compoundMachine);
        $isolationTargets = $calculator->calculateTargets($user, $isolationMachine);

        return $this->json([
            'Uzytkownik' => $user->getName(),
            'Aktualny Cel' => $user->getCurrentGoal()->getName(),
            'Test_Cwiczenie_Wielostawowe' => [
                'maszyna' => $compoundMachine->getName(),
                'wyliczone_parametry' => $compoundTargets
            ],
            'Test_Cwiczenie_Izolowane' => [
                'maszyna' => $isolationMachine->getName(),
                'wyliczone_parametry' => $isolationTargets
            ]
        ]);
    }
}