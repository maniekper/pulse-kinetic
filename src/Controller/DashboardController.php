<?php

namespace App\Controller;

use App\Repository\RoutineRepository;
use App\Repository\MachineRecordRepository; // Dodany import
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(RoutineRepository $routineRepo, MachineRecordRepository $recordRepo): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $currentGoal = method_exists($user, 'getCurrentGoal') ? $user->getCurrentGoal() : null;
        
        $routines = $routineRepo->findBy(['user' => $user]);
        // Pobieramy rekordy użytkownika
        $records = $recordRepo->findBy(['user' => $user], ['achievedAt' => 'DESC']);

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'currentGoal' => $currentGoal,
            'routines' => $routines,
            'records' => $records, // Przekazujemy do Twiga
        ]);
    }
}