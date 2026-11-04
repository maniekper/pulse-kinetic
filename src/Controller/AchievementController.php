<?php

namespace App\Controller;

use App\Entity\MachineRecord;
use App\Entity\WorkoutPlan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
}