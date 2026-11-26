<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\GoalRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\WorkoutPlan; // Dodany niezbędny import

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, GoalRepository $goalRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        /** @var \App\Entity\User $user */

        if ($request->isMethod('POST')) {
            $user->setName($request->request->get('name'));
            $user->setGender($request->request->get('gender'));
            $user->setWeight((float) $request->request->get('weight'));
            $user->setHeight((float) $request->request->get('height'));
            $user->setAge((int) $request->request->get('age'));

            $goalId = $request->request->get('goal_id');
            if ($goalId) {
                $newGoal = $goalRepository->find($goalId);
                // BYŁO: if ($newGoal && $newGoal !== $user->getCurrentGoal()) {

                // ZMIEŃ NA:
                if ($newGoal) {
                    $user->setCurrentGoal($newGoal);

                    $goalName = $newGoal->getName();
                    $lowerGoalName = mb_strtolower($goalName, 'UTF-8');

                    // BEZPIECZNA AKTUALIZACJA PLANU W LOCIE
                    $plans = $entityManager->getRepository(WorkoutPlan::class)->findBy(['user' => $user]);

                    foreach ($plans as $plan) {
                        $plan->setName('Plan: ' . $goalName . ' (Zaktualizowano)');

                        foreach ($plan->getWorkoutExercises() as $exercise) {
                            $machine = $exercise->getMachine();
                            $isCompound = $machine ? ($machine->getMechanicType() === 'compound') : false;

                            // Pobieramy dane z wybranego właśnie nowego celu
                            $minReps = $isCompound ? $newGoal->getBaseCompoundRepsMin() : $newGoal->getBaseIsolationRepsMin();
                            $maxReps = $isCompound ? $newGoal->getBaseCompoundRepsMax() : $newGoal->getBaseIsolationRepsMax();

                            $exercise->setSets($isCompound ? 4 : 3);
                            $exercise->setReps($minReps . '-' . $maxReps);
                        }
                    }
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profil i parametry treningowe zostały natychmiast zaktualizowane.');

            return $this->redirectToRoute('app_profile');
        }

        $goals = $goalRepository->findAll();

        return $this->render('profile/index.html.twig', [
            'goals' => $goals,
            'bannedMachines' => $user->getBannedMachines()
        ]);
    }

    #[Route('/profile/unban/{id}', name: 'app_profile_unban', methods: ['POST'])]
    public function unbanMachine(\App\Entity\Machine $machine, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($user) {
            $user->removeBannedMachine($machine);
            $em->flush();
            $this->addFlash('success', 'Maszyna przywrócona. Będzie ponownie brana pod uwagę przez generator.');
        }
        return $this->redirectToRoute('app_profile');
    }
    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function deleteAccount(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 1. Weryfikacja tokena CSRF
        if (!$this->isCsrfTokenValid('delete_account_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('app_profile');
        }

        // 2. Weryfikacja hasła (zabezpieczenie przed przypadkowym usunięciem)
        $password = (string) $request->request->get('confirm_password', '');
        if (!$passwordHasher->isPasswordValid($user, $password)) {
            $this->addFlash('error', 'Nieprawidłowe hasło. Konto nie zostało usunięte.');
            return $this->redirectToRoute('app_profile');
        }

        // 3. Wylogowanie i wyczyszczenie sesji przed usunięciem
        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        // 4. Usunięcie użytkownika z bazy
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Twoje konto oraz wszystkie dane treningowe zostały trwale usunięte.');

        return $this->redirectToRoute('app_login');
    }
    #[Route('/profile/equipment-weights', name: 'app_profile_equipment_weights', methods: ['POST'])]
    public function updateEquipmentWeights(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $token = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('profile_equipment_weights', $token)) {
            $this->addFlash('error', 'Nieprawidłowy token CSRF.');
            return $this->redirectToRoute('app_profile');
        }

        $platesRaw = $request->request->all('plates');
        $dumbbellsRaw = $request->request->all('dumbbells');

        if (method_exists($user, 'setAvailablePlates')) {
            $user->setAvailablePlates(is_array($platesRaw) ? $platesRaw : []);
        }
        if (method_exists($user, 'setAvailableDumbbells')) {
            $user->setAvailableDumbbells(is_array($dumbbellsRaw) ? $dumbbellsRaw : []);
        }

        $em->flush();
        $this->addFlash('success', 'Zapisano dostępne ciężary krążków i hantli.');

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/change-password', name: 'app_profile_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('change_password_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('app_profile');
        }

        $currentPassword = (string) $request->request->get('current_password', '');
        $newPassword = (string) $request->request->get('new_password', '');
        $confirmPassword = (string) $request->request->get('confirm_new_password', '');

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Obecne hasło jest nieprawidłowe.');
            return $this->redirectToRoute('app_profile');
        }

        if (mb_strlen($newPassword) < 8) {
            $this->addFlash('error', 'Nowe hasło musi mieć co najmniej 8 znaków.');
            return $this->redirectToRoute('app_profile');
        }

        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'Nowe hasła nie są identyczne.');
            return $this->redirectToRoute('app_profile');
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $em->flush();

        $this->addFlash('success', 'Hasło zostało pomyślnie zmienione.');

        return $this->redirectToRoute('app_profile');
    }
}
