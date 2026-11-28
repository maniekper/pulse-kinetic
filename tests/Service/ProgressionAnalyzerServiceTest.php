<?php

namespace App\Tests\Service;

use App\Entity\Goal;
use App\Entity\Machine;
use App\Entity\User;
use App\Entity\WorkoutSession;
use App\Entity\WorkoutSet;
use App\Service\ProgressionAnalyzerService;
use App\Service\WorkoutCalculatorService;
use PHPUnit\Framework\TestCase;

class ProgressionAnalyzerServiceTest extends TestCase
{
    private function createSet(
        float $weight,
        int $reps,
        string $machineName,
        string $mechanicType = 'compound',
        ?string $goalName = 'Hipertrofia',
        array $userSteps = []
    ): WorkoutSet {
        $goal = null;
        if ($goalName !== null) {
            $goal = (new Goal())
                ->setName($goalName)
                ->setBaseCompoundRepsMin(6)
                ->setBaseCompoundRepsMax(10)
                ->setBaseIsolationRepsMin(10)
                ->setBaseIsolationRepsMax(15)
                ->setRestCompoundSeconds(180)
                ->setRestIsolationSeconds(90);
        }

        $user = new class($goal, $userSteps) extends User {
            public function __construct(
                ?Goal $goal,
                private readonly array $steps
            ) {
                parent::__construct();
                $this->setCurrentGoal($goal);
            }

            public function getId(): int
            {
                return 1;
            }

            public function getExerciseIncrementSteps(): array
            {
                return $this->steps;
            }
        };

        $machine = (new class extends Machine {
            public function getId(): int
            {
                return 10;
            }
        })
            ->setName($machineName)
            ->setMechanicType($mechanicType)
            ->setIncrementStep(2.5);

        $session = (new WorkoutSession())
            ->setUser($user)
            ->setStartedAt(new \DateTime());

        return (new WorkoutSet())
            ->setWorkoutSession($session)
            ->setMachine($machine)
            ->setWeight($weight)
            ->setReps($reps)
            ->setIsCompleted(true)
            ->setLoggedAt(new \DateTime());
    }

    public function testPlusOneRepOnBarbellAddsOnlySingleMinimalStep(): void
    {
        $service = new ProgressionAnalyzerService(new WorkoutCalculatorService());

        // Cel: 8 powtórzeń, wykonane: 9 (+1), ciężar: 80 kg na sztandze
        // Nawet jeśli w userSteps zapisano przypadkowo 10 kg z rozgrzewki, na sztandze nie powinno skoczyć o 10 kg
        $set = $this->createSet(
            weight: 80.0,
            reps: 9,
            machineName: 'Wyciskanie sztangi płasko',
            mechanicType: 'compound',
            goalName: 'Hipertrofia',
            userSteps: ['10' => 10.0]
        );

        $result = $service->analyzeSet($set, targetReps: 8, isCompound: true);

        $this->assertSame(82.5, $result['next_weight']);
        $this->assertSame(180, $result['rest_seconds']);
    }

    public function testPlusTwoRepsStillAddsSingleMinimalStep(): void
    {
        $service = new ProgressionAnalyzerService(new WorkoutCalculatorService());
        $set = $this->createSet(80.0, 10, 'Wyciskanie sztangi płasko', 'compound');

        $result = $service->analyzeSet($set, targetReps: 8, isCompound: true);

        $this->assertSame(82.5, $result['next_weight']);
    }

    public function testPlusThreeOrMoreRepsCalculatesLargerJump(): void
    {
        $service = new ProgressionAnalyzerService(new WorkoutCalculatorService());
        $set = $this->createSet(80.0, 11, 'Wyciskanie sztangi płasko', 'compound');

        $result = $service->analyzeSet($set, targetReps: 8, isCompound: true);

        $this->assertGreaterThan(82.5, $result['next_weight']);
    }

    public function testTargetRepsReachedKeepsSameWeight(): void
    {
        $service = new ProgressionAnalyzerService(new WorkoutCalculatorService());
        $set = $this->createSet(80.0, 8, 'Wyciskanie sztangi płasko', 'compound');

        $result = $service->analyzeSet($set, targetReps: 8, isCompound: true);

        $this->assertSame(80.0, $result['next_weight']);
        $this->assertSame(180, $result['rest_seconds']);
    }
}
