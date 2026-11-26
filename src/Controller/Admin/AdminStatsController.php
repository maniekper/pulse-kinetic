<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\WorkoutSession;
use App\Entity\WorkoutSet;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/stats')]
#[IsGranted('ROLE_ADMIN')]
class AdminStatsController extends AbstractController
{
    #[Route('', name: 'app_admin_stats', methods: ['GET'])]
    public function index(UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $now = new \DateTimeImmutable();
        $todayStart = \DateTime::createFromImmutable($now->setTime(0, 0, 0));
        $weekStart = \DateTime::createFromImmutable($now->setTime(0, 0, 0)->modify('-6 days'));

        $users = $userRepository->findBy([], ['id' => 'ASC']);
        $totalUsers = count($users);

        $loggedInToday = (int)$userRepository->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->where('u.lastLoginAt >= :todayStart')
            ->setParameter('todayStart', $todayStart)
            ->getQuery()
            ->getSingleScalarResult();

        $loggedInLast7Days = (int)$userRepository->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->where('u.lastLoginAt >= :weekStart')
            ->setParameter('weekStart', $weekStart)
            ->getQuery()
            ->getSingleScalarResult();

        $sessionsToday = (int)$em->createQuery(
            'SELECT COUNT(s.id)
             FROM ' . WorkoutSession::class . ' s
             WHERE s.endedAt IS NOT NULL AND s.endedAt >= :start'
        )
            ->setParameter('start', $todayStart)
            ->getSingleScalarResult();

        $sessionsLast7Days = (int)$em->createQuery(
            'SELECT COUNT(s.id)
             FROM ' . WorkoutSession::class . ' s
             WHERE s.endedAt IS NOT NULL AND s.endedAt >= :start'
        )
            ->setParameter('start', $weekStart)
            ->getSingleScalarResult();

        $tonnageTodayKg = (float)$em->createQuery(
            'SELECT COALESCE(SUM(ws.weight * ws.reps), 0)
             FROM ' . WorkoutSet::class . ' ws
             WHERE ws.isCompleted = true AND ws.loggedAt >= :start'
        )
            ->setParameter('start', $todayStart)
            ->getSingleScalarResult();

        $tonnageLast7DaysKg = (float)$em->createQuery(
            'SELECT COALESCE(SUM(ws.weight * ws.reps), 0)
             FROM ' . WorkoutSet::class . ' ws
             WHERE ws.isCompleted = true AND ws.loggedAt >= :start'
        )
            ->setParameter('start', $weekStart)
            ->getSingleScalarResult();

        // --- PRZYGOTOWANIE DANYCH DO WYKRESU (OSTATNIE 7 DNI PER UŻYTKOWNIK) ---
        $dateKeys = [];
        $dateLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $now->modify("-{$i} days");
            $dateKeys[] = $d->format('Y-m-d');
            $dateLabels[] = $d->format('d.m');
        }

        // Paleta kontrastowych kolorów dla poszczególnych użytkowników
        $palette = [
            '#d1fc00', // neon limonka
            '#f000ff', // neon magenta
            '#00e5ff', // cyjan
            '#ff9100', // pomarańczowy
            '#00e676', // szmaragdowy
            '#ff3d71', // koralowy
            '#aa00ff', // fiolet
            '#ffd600', // złoty
            '#2979ff', // kobaltowy
            '#1de9b6', // turkusowy
        ];

        $userSeries = [];
        foreach ($users as $idx => $u) {
            /** @var User $u */ $uid = $u->getId();
            $label = $u->getName() ?: ('User #' . $uid);
            $color = $palette[$idx % count($palette)];

            $userSeries[$uid] = [
                'label' => $label,
                'color' => $color,
                'tonnage' => array_fill_keys($dateKeys, 0.0),
                'sessions' => array_fill_keys($dateKeys, 0),
                'logins' => array_fill_keys($dateKeys, 0),
            ];

            // Zaznaczamy dzień ostatniego logowania użytkownika (1 = zalogowany w tym dniu)
            if ($u->getLastLoginAt() && $u->getLastLoginAt() >= $weekStart) {
                $loginDay = $u->getLastLoginAt()->format('Y-m-d');
                if (isset($userSeries[$uid]['logins'][$loginDay])) {
                    $userSeries[$uid]['logins'][$loginDay] = 1;
                }
            }
        }

        // Sesje z ostatnich 7 dni
        $recentSessions = $em->createQuery(
            'SELECT s, u
             FROM ' . WorkoutSession::class . ' s
             JOIN s.user u
             WHERE s.endedAt IS NOT NULL AND s.endedAt >= :start'
        )
            ->setParameter('start', $weekStart)
            ->getResult();

        /** @var WorkoutSession $session */
        foreach ($recentSessions as $session) {
            $uid = $session->getUser()?->getId();
            $dayKey = $session->getEndedAt()?->format('Y-m-d');
            if ($uid && $dayKey && isset($userSeries[$uid]['sessions'][$dayKey])) {
                $userSeries[$uid]['sessions'][$dayKey]++;
            }
        }

        // Tonaż z ukończonych serii z ostatnich 7 dni
        $recentSets = $em->createQuery(
            'SELECT ws, s, u
             FROM ' . WorkoutSet::class . ' ws
             JOIN ws.workoutSession s
             JOIN s.user u
             WHERE ws.isCompleted = true AND ws.loggedAt >= :start'
        )
            ->setParameter('start', $weekStart)
            ->getResult();

        /** @var WorkoutSet $set */
        foreach ($recentSets as $set) {
            $uid = $set->getWorkoutSession()?->getUser()?->getId();
            $dayKey = $set->getLoggedAt()?->format('Y-m-d');
            if ($uid && $dayKey && isset($userSeries[$uid]['tonnage'][$dayKey])) {
                $userSeries[$uid]['tonnage'][$dayKey] += round((float) $set->getWeight() * (int)$set->getReps(), 1);
            }
        }

        // Formatujemy dane pod Chart.js
        $chartPayload = [
            'labels' => $dateLabels,
            'tonnage' => [],
            'sessions' => [],
            'logins' => [],
        ];

        foreach ($userSeries as $series) {
            $chartPayload['tonnage'][] = [
                'label' => $series['label'],
                'color' => $series['color'],
                'data' => array_values($series['tonnage']),
            ];
            $chartPayload['sessions'][] = [
                'label' => $series['label'],
                'color' => $series['color'],
                'data' => array_values($series['sessions']),
            ];
            $chartPayload['logins'][] = [
                'label' => $series['label'],
                'color' => $series['color'],
                'data' => array_values($series['logins']),
            ];
        }

        return $this->render('admin/stats/index.html.twig', [
            'totalUsers' => $totalUsers,
            'loggedInToday' => $loggedInToday,
            'loggedInLast7Days' => $loggedInLast7Days,
            'sessionsToday' => $sessionsToday,
            'sessionsLast7Days' => $sessionsLast7Days,
            'tonnageTodayKg' => $tonnageTodayKg,
            'tonnageLast7DaysKg' => $tonnageLast7DaysKg,
            'chartPayload' => $chartPayload,
        ]);
    }
}
