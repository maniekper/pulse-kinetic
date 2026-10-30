<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\WorkoutPlanRepository;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(WorkoutPlanRepository $workoutPlanRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Pobieramy najnowszy plan treningowy użytkownika (sortujemy malejąco po ID)
        $latestPlan = $workoutPlanRepository->findOneBy(
            ['user' => $user], 
            ['id' => 'DESC']
        );

        return $this->render('dashboard/index.html.twig', [
            'plan' => $latestPlan
        ]);
    }
}