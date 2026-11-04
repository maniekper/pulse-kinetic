<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\GoalRepository;
use Doctrine\ORM\EntityManagerInterface;

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

        // Obsługa zapisu z formularza
        if ($request->isMethod('POST')) {
            $user->setName($request->request->get('name'));
            $user->setGender($request->request->get('gender'));
            $user->setWeight((float) $request->request->get('weight'));
            $user->setHeight((float) $request->request->get('height'));
            $user->setAge((int) $request->request->get('age'));

            $goalId = $request->request->get('goal_id');
            if ($goalId) {
                $newGoal = $goalRepository->find($goalId);
                if ($newGoal && $newGoal !== $user->getCurrentGoal()) {
                    $user->setCurrentGoal($newGoal);
                    
                    // Usuwamy stary plan, żeby system wymusił wygenerowanie nowego na Dashboardzie
                    $oldPlan = $entityManager->getRepository(\App\Entity\WorkoutPlan::class)->findOneBy(['user' => $user], ['id' => 'DESC']);
                    if ($oldPlan) {
                        $entityManager->remove($oldPlan);
                        
                        // Czyścimy też w tle aktywną (niezakończoną) sesję
                        $activeSession = $entityManager->getRepository(\App\Entity\WorkoutSession::class)->findOneBy([
                            'user' => $user,
                            'endedAt' => null
                        ]);
                        if ($activeSession) {
                            $entityManager->remove($activeSession);
                        }
                    }
                    $this->addFlash('warning', 'Parametry zmienione. Przejdź na Dashboard, by wygenerować nowy protokół dla tego celu.');
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profil i parametry treningowe zostały zaktualizowane.');
            
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