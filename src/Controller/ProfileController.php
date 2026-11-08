<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\GoalRepository;
use Doctrine\ORM\EntityManagerInterface;
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

                            if (str_contains($lowerGoalName, 'redukcj') || str_contains($lowerGoalName, 'rzeźb')) {
                                $exercise->setSets($isCompound ? 3 : 3);
                                $exercise->setReps($isCompound ? '10-12' : '12-15');
                            } elseif (str_contains($lowerGoalName, 'sił')) {
                                $exercise->setSets($isCompound ? 5 : 3);
                                $exercise->setReps($isCompound ? '3-6' : '6-8');
                            } else {
                                $exercise->setSets($isCompound ? 4 : 3);
                                $exercise->setReps($isCompound ? '8-10' : '10-12');
                            }
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
}