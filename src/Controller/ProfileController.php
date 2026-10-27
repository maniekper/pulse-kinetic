<?php

namespace App\Controller;

use App\Repository\GoalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile/update-test', name: 'app_profile_update_test')]
    public function updateTestProfile(
        GoalRepository $goalRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        // Pobieramy aktualnie zalogowanego użytkownika
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Brak dostępu. Zaloguj się.']);
        }

       // Pobieramy wszystkie cele i wybieramy ostatni z listy
        $goals = $goalRepo->findAll();
        $newGoal = end($goals);

        if (!$newGoal) {
            return $this->json(['error' => 'Brak jakichkolwiek celów w bazie.']);
        } 

        // Aktualizujemy dane
        $user->setCurrentWeight(85.5); // Ustawiamy nową wagę
        $user->setCurrentGoal($newGoal); // Przypinamy nowy cel

        // Zapisujemy zmiany w bazie
        $em->flush();

        return $this->json([
            'status' => 'Profil zostal zaktualizowany',
            'uzytkownik' => $user->getUserIdentifier(),
            'nowa_waga' => $user->getCurrentWeight() . ' kg',
            'nowy_cel' => $user->getCurrentGoal()->getName()
        ]);
    }
}