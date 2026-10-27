<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Brak dostępu. Zaloguj się.']);
        }

        $currentGoal = method_exists($user, 'getCurrentGoal') ? $user->getCurrentGoal() : null;

        return $this->json([
            'status' => 'Zalogowano pomyślnie',
            'witaj' => $user->getUserIdentifier(),
            'twoj_cel' => $currentGoal?->getName() ?? 'Brak aktywnego celu',
            'wiadomosc' => 'To jest Twój bezpieczny panel treningowy.'
        ]);
    }
}