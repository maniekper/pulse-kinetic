<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Machine;
use App\Entity\WorkoutPlan;
use App\Entity\WorkoutSession;
use App\Entity\WorkoutSet;
use App\Entity\WorkoutExercise;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class HistoryFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        return; 
        
        // 1. Pobieramy użytkownika "Dzik"
        $user = $manager->getRepository(User::class)->findOneBy(['email' => 'test@pulsekinetic.com']);

        if (!$user) {
            echo "Brak użytkownika. Sprawdź AppFixtures.\n";
            return;
        }

        // 2. Pobieramy plan, a jeśli go nie ma - TWORZYMY GO AUTOMATYCZNIE
        $plan = $manager->getRepository(WorkoutPlan::class)->findOneBy(['user' => $user]);

        if (!$plan) {
            $plan = new WorkoutPlan();
            $plan->setName('Protokół Siłowy (Auto-Gen)');
            $plan->setUser($user);
            
            // Pobieramy 4 losowe maszyny/wolne ciężary z bazy
            $machines = $manager->getRepository(Machine::class)->findAll();
            
            for ($i = 0; $i < 4; $i++) {
                if (!isset($machines[$i])) continue;
                
                $exercise = new WorkoutExercise();
                $exercise->setMachine($machines[$i]);
                $exercise->setDayNumber(1);
                $exercise->setSets(4);
                $exercise->setReps('8-12');
                
                $plan->addWorkoutExercise($exercise);
                $manager->persist($exercise);
            }
            
            $manager->persist($plan);
            $manager->flush();
            echo "Wygenerowano automatyczny plan treningowy!\n";
        }

        // 3. Symulujemy 10 wykonanych treningów z ostatnich 30 dni
        $daysAgo = [2, 4, 5, 7, 9, 12, 14, 16, 21, 28];

        foreach ($daysAgo as $days) {
            $session = new WorkoutSession();
            $session->setUser($user);
        
            
            $createdAt = (new \DateTimeImmutable())->modify("-{$days} days");
                        $createdAt = (new \DateTime())->modify("-{$days} days");
            $endedAt = $createdAt->modify('+75 minutes');
            
            $session->setStartedAt($createdAt);
            $session->setEndedAt($endedAt);

            $totalTonnage = 0;

            // Generujemy wykonane serie dla każdego ćwiczenia
            foreach ($plan->getWorkoutExercises() as $workoutExercise) {
                $setsCount = rand(3, 4);
                
                for ($i = 1; $i <= $setsCount; $i++) {
                    $set = new WorkoutSet();
                    $set->setWorkoutSession($session);
                    // Relacja zależy od tego jak nazwałeś pole w WorkoutSet
                    // Bazując na poprzednim błędzie, ustawiamy bezpośrednio maszynę:
                    $set->setMachine($workoutExercise->getMachine());
                    $set->setSetNumber($i);
                    
                    $reps = rand(8, 12);
                    $weight = rand(60, 140);
                    
                    $set->setReps($reps);
                    $set->setWeight($weight);
                    $set->setIsCompleted(true);
                    $set->setLoggedAt($createdAt);
                    
                    $totalTonnage += ($reps * $weight);
                    
                    $manager->persist($set);
                }
            }

            $session->setTotalTonnage($totalTonnage);
            $manager->persist($session);
        }

        $manager->flush();
        echo "Dodano 10 ukończonych sesji treningowych z wyliczonym tonażem!\n";
    }

    public function getDependencies(): array
    {
        return [
            MachineFixtures::class,
            AppFixtures::class,
        ];
    }
}