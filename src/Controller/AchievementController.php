<?php

namespace App\Controller;

use App\Entity\MachineRecord;
use App\Entity\WorkoutPlan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Machine;
use App\Entity\WorkoutSet;
use Symfony\Component\HttpFoundation\JsonResponse;

class AchievementController extends AbstractController
{
    #[Route('/achievements', name: 'app_achievements')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 1. Analityka Globalna (Lifetime Stats)
        $stats = $em->createQuery(
            'SELECT COUNT(s.id) as totalSessions, SUM(s.totalTonnage) as lifetimeTonnage 
             FROM App\Entity\WorkoutSession s 
             WHERE s.user = :user AND s.endedAt IS NOT NULL'
        )
            ->setParameter('user', $user)
            ->getSingleResult();

        $totalSessions = (int) $stats['totalSessions'];
        $lifetimeTonnage = (float) $stats['lifetimeTonnage'];

        // 2. Rejestr Maksymów (Personal Bests)
        // Pobieramy rekordy posortowane od największego ciężaru
        $records = $em->getRepository(MachineRecord::class)->findBy(
            ['user' => $user],
            ['maxWeight' => 'DESC']
        );

        // 3. Aktywny plan (potrzebny, aby górny pasek/sidebar wiedział, co wyświetlać)
        $plan = $em->getRepository(WorkoutPlan::class)->findOneBy(
            ['user' => $user],
            ['id' => 'DESC']
        );

        return $this->render('achievements/index.html.twig', [
            'totalSessions' => $totalSessions,
            'lifetimeTonnage' => $lifetimeTonnage,
            'records' => $records,
            'plan' => $plan,
        ]);
    }
    #[Route('/achievements/strength-data/{id}', name: 'app_achievements_strength_data', methods: ['GET'])]
    public function strengthData(Machine $machine, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $sets = $em->createQuery(
            'SELECT ws
             FROM App\Entity\WorkoutSet ws
             JOIN ws.workoutSession s
             WHERE s.user = :user
               AND ws.machine = :machine
               AND ws.isCompleted = true
               AND ws.weight > 0
               AND ws.reps > 0
             ORDER BY ws.loggedAt ASC'
        )
            ->setParameter('user', $user)
            ->setParameter('machine', $machine)
            ->getResult();

        $grouped = [];

        /** @var WorkoutSet $set */
        foreach ($sets as $set) {
            $dateKey = $set->getLoggedAt()->format('Y-m-d');
            $weight = (float) $set->getWeight();
            $reps = (int) $set->getReps();

            // Wzór Epleya na szacowane 1RM (dla 1 powtórzenia to po prostu ciężar)
            $oneRm = $reps === 1 ? $weight : round($weight * (1 + $reps / 30), 1);

            if (!isset($grouped[$dateKey])) {
                $grouped[$dateKey] = [
                    'label' => $set->getLoggedAt()->format('d.m.Y'),
                    'oneRm' => $oneRm,
                    'maxWeight' => $weight,
                    'tonnage' => $weight * $reps,
                ];
            } else {
                $grouped[$dateKey]['oneRm'] = max($grouped[$dateKey]['oneRm'], $oneRm);
                $grouped[$dateKey]['maxWeight'] = max($grouped[$dateKey]['maxWeight'], $weight);
                $grouped[$dateKey]['tonnage'] += ($weight * $reps);
            }
        }

        $labels = [];
        $oneRmData = [];
        $maxWeightData = [];
        $tonnageData = [];

        foreach ($grouped as $day) {
            $labels[] = $day['label'];
            $oneRmData[] = round($day['oneRm'], 1);
            $maxWeightData[] = round($day['maxWeight'], 1);
            $tonnageData[] = round($day['tonnage'], 1);
        }

        return new JsonResponse([
            'machine' => $machine->getName(),
            'labels' => $labels,
            'oneRm' => $oneRmData,
            'maxWeight' => $maxWeightData,
            'tonnage' => $tonnageData,
        ]);
    }
}
