<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\WorkoutSession;
use App\Entity\WorkoutSet;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/stats')]
#[IsGranted('ROLE_ADMIN')]
class AdminStatsController extends AbstractController
{
    #[Route('', name: 'app_admin_stats', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $days = $request->query->getInt('days', 7);
        if (!in_array($days, [7, 30], true)) {
            $days = 7;
        }

        $now = new \DateTimeImmutable();
        $todayStart = \DateTime::createFromImmutable($now->setTime(0, 0, 0));
        $periodStart = \DateTime::createFromImmutable($now->setTime(0, 0, 0)->modify('-' . ($days - 1) . ' days'));

        $users = $userRepository->findBy([], ['id' => 'ASC']);
        $totalUsers = count($users);

        // --- METRYKI DZIŚ ---
        $loggedInToday = (int)$userRepository->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->where('u.lastLoginAt >= :todayStart')
            ->setParameter('todayStart', $todayStart)
            ->getQuery()
            ->getSingleScalarResult();

        $sessionsToday = (int)$em->createQuery(
            'SELECT COUNT(s.id)
             FROM ' . WorkoutSession::class . ' s
             WHERE s.endedAt IS NOT NULL AND s.endedAt >= :start'
        )
            ->setParameter('start', $todayStart)
            ->getSingleScalarResult();

        $tonnageTodayKg = (float)$em->createQuery(
            'SELECT COALESCE(SUM(ws.weight * ws.reps), 0)
             FROM ' . WorkoutSet::class . ' ws
             WHERE ws.isCompleted = true AND ws.loggedAt >= :start'
        )
            ->setParameter('start', $todayStart)
            ->getSingleScalarResult();

        // --- METRYKI WYBRANEGO OKRESU (7 LUB 30 DNI) ---
        $loggedInPeriod = (int)$userRepository->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->where('u.lastLoginAt >= :periodStart')
            ->setParameter('periodStart', $periodStart)
            ->getQuery()
            ->getSingleScalarResult();

        $sessionsPeriod = (int)$em->createQuery(
            'SELECT COUNT(s.id)
             FROM ' . WorkoutSession::class . ' s
             WHERE s.endedAt IS NOT NULL AND s.endedAt >= :start'
        )
            ->setParameter('start', $periodStart)
            ->getSingleScalarResult();

        $abandonedSessionsPeriod = (int)$em->createQuery(
            'SELECT COUNT(s.id)
             FROM ' . WorkoutSession::class . ' s
             WHERE s.endedAt IS NULL AND s.startedAt >= :start'
        )
            ->setParameter('start', $periodStart)
            ->getSingleScalarResult();

        $tonnagePeriodKg = (float)$em->createQuery(
            'SELECT COALESCE(SUM(ws.weight * ws.reps), 0)
             FROM ' . WorkoutSet::class . ' ws
             WHERE ws.isCompleted = true AND ws.loggedAt >= :start'
        )
            ->setParameter('start', $periodStart)
            ->getSingleScalarResult();

        $activeTrainersPeriod = (int)$em->createQuery(
            'SELECT COUNT(DISTINCT IDENTITY(s.user))
             FROM ' . WorkoutSession::class . ' s
             WHERE s.endedAt IS NOT NULL AND s.endedAt >= :start'
        )
            ->setParameter('start', $periodStart)
            ->getSingleScalarResult();

        $avgTonnagePerSessionKg = $sessionsPeriod > 0
            ? round($tonnagePeriodKg / $sessionsPeriod, 0)
            : 0.0;

        $totalStartedInPeriod = $sessionsPeriod + $abandonedSessionsPeriod;
        $completionRatePercent = $totalStartedInPeriod > 0
            ? (int) round(($sessionsPeriod / $totalStartedInPeriod) * 100)
            : 100;

        // --- TOP 5 NAJCZĘŚCIEJ UŻYWANYCH MASZYN (W OKRESIE) ---
        $topUsedMachines = $em->createQuery(
            'SELECT m.id AS id,
                    m.name AS name,
                    m.category AS category,
                    COUNT(ws.id) AS setsCount,
                    COALESCE(SUM(ws.weight * ws.reps), 0) AS totalTonnage
             FROM ' . WorkoutSet::class . ' ws
             JOIN ws.machine m
             WHERE ws.isCompleted = true AND ws.loggedAt >= :start
             GROUP BY m.id, m.name, m.category
             ORDER BY setsCount DESC, totalTonnage DESC'
        )
            ->setParameter('start', $periodStart)
            ->setMaxResults(5)
            ->getResult();

        // --- TOP 5 NAJCZĘŚCIEJ BANOWANYCH MASZYN (GLOBALNIE) ---
        $bannedCountMap = [];
        foreach ($users as $u) {
            /** @var User $u */
            foreach ($u->getBannedMachines() as $bannedMachine) {
                $mid = $bannedMachine->getId();
                if (!isset($bannedCountMap[$mid])) {
                    $bannedCountMap[$mid] = [
                        'id' => $mid,
                        'name' => $bannedMachine->getName(),
                        'category' => $bannedMachine->getCategory() ?: 'Brak kategorii',
                        'bansCount' => 0,
                    ];
                }
                $bannedCountMap[$mid]['bansCount']++;
            }
        }
        usort($bannedCountMap, static fn(array $a, array $b): int => $b['bansCount'] <=> $a['bansCount']);
        $topBannedMachines = array_slice($bannedCountMap, 0, 5);

        // --- ANALIZA PŁYTEK / STOSU I SKOKÓW OBCIĄŻENIA NA MASZYNACH ---
        $allCompletedSets = $em->createQuery(
            'SELECT ws.weight AS weight,
                    ws.reps AS reps,
                    ws.loggedAt AS loggedAt,
                    m.id AS machineId,
                    m.name AS machineName,
                    m.category AS category,
                    m.mechanicType AS mechanicType,
                    u.id AS userId
             FROM ' . WorkoutSet::class . ' ws
             JOIN ws.machine m
             JOIN ws.workoutSession s
             JOIN s.user u
             WHERE ws.isCompleted = true
               AND ws.weight > 0
             ORDER BY m.name ASC, u.id ASC, ws.loggedAt ASC'
        )->getResult();

        $userStepsByMachine = [];
        foreach ($users as $u) {
            if (method_exists($u, 'getExerciseIncrementSteps')) {
                $steps = $u->getExerciseIncrementSteps();
                if (is_array($steps)) {
                    foreach ($steps as $mid => $stepVal) {
                        $midInt = (int)$mid;
                        $stepFloat = round((float)$stepVal, 2);
                        if ($stepFloat > 0) {
                            $userStepsByMachine[$midInt][] = $stepFloat;
                        }
                    }
                }
            }
        }

        $plateAnalysisMap = [];
        $lastWeightByMachineUser = [];

        foreach ($allCompletedSets as $row) {
            $mid = (int)$row['machineId'];
            $uid = (int)$row['userId'];
            $w = round((float)$row['weight'], 2);

            if (!isset($plateAnalysisMap[$mid])) {
                $plateAnalysisMap[$mid] = [
                    'id' => $mid,
                    'name' => $row['machineName'],
                    'category' => $row['category'] ?: 'Brak kategorii',
                    'mechanicType' => $row['mechanicType'] ?: '—',
                    'weightsCount' => [],
                    'observedDeltas' => [],
                    'setsCount' => 0,
                    'configuredSteps' => array_values(array_unique($userStepsByMachine[$mid] ?? [])),
                ];
            }

            $plateAnalysisMap[$mid]['setsCount']++;
            $weightKey = (string)$w;
            $plateAnalysisMap[$mid]['weightsCount'][$weightKey] = ($plateAnalysisMap[$mid]['weightsCount'][$weightKey] ?? 0) + 1;

            $pairKey = $mid . '_' . $uid;
            if (isset($lastWeightByMachineUser[$pairKey])) {
                $delta = round(abs($w - $lastWeightByMachineUser[$pairKey]), 2);
                if ($delta > 0 && $delta <= 50) {
                    $deltaKey = (string)$delta;
                    $plateAnalysisMap[$mid]['observedDeltas'][$deltaKey] = ($plateAnalysisMap[$mid]['observedDeltas'][$deltaKey] ?? 0) + 1;
                }
            }
            $lastWeightByMachineUser[$pairKey] = $w;
        }

        $machinePlatesAnalysis = [];
        foreach ($plateAnalysisMap as $item) {
            $uniqueWeights = array_map('floatval', array_keys($item['weightsCount']));
            sort($uniqueWeights, SORT_NUMERIC);

            $mostCommonDelta = null;
            if (!empty($item['observedDeltas'])) {
                arsort($item['observedDeltas']);
                $mostCommonDelta = (float) array_key_first($item['observedDeltas']);
            }

            $minGap = null;
            for ($i = 1, $cnt = count($uniqueWeights); $i < $cnt; $i++) {
                $gap = round($uniqueWeights[$i] - $uniqueWeights[$i - 1], 2);
                if ($gap > 0 && ($minGap === null || $gap < $minGap)) {
                    $minGap = $gap;
                }
            }

            sort($item['configuredSteps'], SORT_NUMERIC);

            $machinePlatesAnalysis[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'category' => $item['category'],
                'mechanicType' => $item['mechanicType'],
                'uniqueWeights' => $uniqueWeights,
                'minGap' => $minGap,
                'mostCommonDelta' => $mostCommonDelta,
                'configuredSteps' => $item['configuredSteps'],
                'setsCount' => $item['setsCount'],
            ];
        }

        usort(
            $machinePlatesAnalysis,
            static fn(array $a, array $b): int => $b['setsCount'] <=> $a['setsCount']
        );

        // --- DANE DO WYKRESU DZIENNEGO PER UŻYTKOWNIK ---
        $dateKeys = [];
        $dateLabels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = $now->modify("-{$i} days");
            $dateKeys[] = $d->format('Y-m-d');
            $dateLabels[] = $d->format('d.m');
        }

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
                'id' => $uid,
                'label' => $label,
                'color' => $color,
                'totalTonnage' => 0.0,
                'totalSessions' => 0,
                'tonnage' => array_fill_keys($dateKeys, 0.0),
                'sessions' => array_fill_keys($dateKeys, 0),
                'logins' => array_fill_keys($dateKeys, 0),
            ];

            if ($u->getLastLoginAt() && $u->getLastLoginAt() >= $periodStart) {
                $loginDay = $u->getLastLoginAt()->format('Y-m-d');
                if (isset($userSeries[$uid]['logins'][$loginDay])) {
                    $userSeries[$uid]['logins'][$loginDay] = 1;
                }
            }
        }

        $recentSessions = $em->createQuery(
            'SELECT s, u
             FROM ' . WorkoutSession::class . ' s
             JOIN s.user u
             WHERE s.endedAt IS NOT NULL AND s.endedAt >= :start'
        )
            ->setParameter('start', $periodStart)
            ->getResult();

        /** @var WorkoutSession $session */
        foreach ($recentSessions as $session) {
            $uid = $session->getUser()?->getId();
            $dayKey = $session->getEndedAt()?->format('Y-m-d');
            if ($uid && $dayKey && isset($userSeries[$uid]['sessions'][$dayKey])) {
                $userSeries[$uid]['sessions'][$dayKey]++;
                $userSeries[$uid]['totalSessions']++;
            }
        }

        $recentSets = $em->createQuery(
            'SELECT ws, s, u
             FROM ' . WorkoutSet::class . ' ws
             JOIN ws.workoutSession s
             JOIN s.user u
             WHERE ws.isCompleted = true AND ws.loggedAt >= :start'
        )
            ->setParameter('start', $periodStart)
            ->getResult();

        /** @var WorkoutSet $set */
        foreach ($recentSets as $set) {
            $uid = $set->getWorkoutSession()?->getUser()?->getId();
            $dayKey = $set->getLoggedAt()?->format('Y-m-d');
            if ($uid && $dayKey && isset($userSeries[$uid]['tonnage'][$dayKey])) {
                $vol = round((float) $set->getWeight() * (int)$set->getReps(), 1);
                $userSeries[$uid]['tonnage'][$dayKey] += $vol;
                $userSeries[$uid]['totalTonnage'] += $vol;
            }
        }

        $sortedSeries = array_values($userSeries);
        usort(
            $sortedSeries,
            static fn(array $a, array $b): int => [$b['totalTonnage'], $b['totalSessions']] <=> [$a['totalTonnage'], $a['totalSessions']]
        );

        $chartPayload = [
            'labels' => $dateLabels,
            'days' => $days,
            'tonnage' => [],
            'sessions' => [],
            'logins' => [],
        ];

        foreach ($sortedSeries as $rank => $series) {
            $isTop5 = $rank < 5;

            $chartPayload['tonnage'][] = [
                'label' => $series['label'],
                'color' => $series['color'],
                'isTop5' => $isTop5,
                'data' => array_values($series['tonnage']),
            ];
            $chartPayload['sessions'][] = [
                'label' => $series['label'],
                'color' => $series['color'],
                'isTop5' => $isTop5,
                'data' => array_values($series['sessions']),
            ];
            $chartPayload['logins'][] = [
                'label' => $series['label'],
                'color' => $series['color'],
                'isTop5' => $isTop5,
                'data' => array_values($series['logins']),
            ];
        }

        return $this->render('admin/stats/index.html.twig', [
            'days' => $days,
            'totalUsers' => $totalUsers,
            'loggedInToday' => $loggedInToday,
            'sessionsToday' => $sessionsToday,
            'tonnageTodayKg' => $tonnageTodayKg,
            'loggedInPeriod' => $loggedInPeriod,
            'sessionsPeriod' => $sessionsPeriod,
            'abandonedSessionsPeriod' => $abandonedSessionsPeriod,
            'tonnagePeriodKg' => $tonnagePeriodKg,
            'activeTrainersPeriod' => $activeTrainersPeriod,
            'avgTonnagePerSessionKg' => $avgTonnagePerSessionKg,
            'completionRatePercent' => $completionRatePercent,
            'topUsedMachines' => $topUsedMachines,
            'topBannedMachines' => $topBannedMachines,
            'machinePlatesAnalysis' => $machinePlatesAnalysis,
            'chartPayload' => $chartPayload,
        ]);
    }
}
