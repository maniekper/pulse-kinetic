<?php

namespace App\Controller;

use App\Entity\Machine;
use App\Entity\User;
use App\Entity\WorkoutPlan;
use App\Repository\MachineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExerciseCatalogController extends AbstractController
{
    #[Route('/exercises', name: 'app_exercises_index', methods: ['GET'])]
    public function index(MachineRepository $machineRepository, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $machines = $machineRepository->findBy([], ['category' => 'ASC', 'name' => 'ASC']);

        $bannedIds = [];
        foreach ($user->getBannedMachines() as $bannedMachine) {
            $bannedIds[] = $bannedMachine->getId();
        }

        $categories = [];
        foreach ($machines as $machine) {
            $cat = $machine->getCategory() ?: 'Pozostałe';
            $categories[$cat] = $cat;
        }
        ksort($categories);

        $plan = $em->getRepository(WorkoutPlan::class)->findOneBy(
            ['user' => $user],
            ['id' => 'DESC']
        );

        return $this->render('exercises/index.html.twig', [
            'machines' => $machines,
            'bannedIds' => $bannedIds,
            'categories' => array_values($categories),
            'plan' => $plan,
        ]);
    }

    #[Route('/exercises/{id}/toggle-ban', name: 'app_exercises_toggle_ban', methods: ['POST'])]
    public function toggleBan(Machine $machine, Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $token = (string) $request->headers->get('X-CSRF-TOKEN', '');
        if (!$this->isCsrfTokenValid('toggle_ban_machine_' . $machine->getId(), $token)) {
            return new JsonResponse(['error' => 'Nieprawidłowy token CSRF.'], 403);
        }

        $isBanned = $user->getBannedMachines()->contains($machine);

        if ($isBanned) {
            $user->removeBannedMachine($machine);
            $nowBanned = false;
        } else {
            $user->addBannedMachine($machine);
            $nowBanned = true;
        }

        $em->flush();

        return new JsonResponse([
            'id' => $machine->getId(),
            'banned' => $nowBanned,
            'bannedCount' => $user->getBannedMachines()->count(),
        ]);
    }
}
