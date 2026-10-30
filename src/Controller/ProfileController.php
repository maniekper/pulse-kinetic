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
                if ($newGoal) {
                    $user->setCurrentGoal($newGoal);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profil i parametry treningowe zostały zaktualizowane.');
            
            return $this->redirectToRoute('app_profile');
        }

        $goals = $goalRepository->findAll();

        return $this->render('profile/index.html.twig', [
            'goals' => $goals
        ]);
    }
}