<?php

namespace App\DataFixtures;

use App\Entity\Goal;
use App\Entity\Machine;
use App\Entity\Muscle;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // --- 1. CELE TRENINGOWE ---
        $goalHypertrophy = new Goal();
        $goalHypertrophy->setName('Hipertrofia (Masa mięśniowa)')
            ->setBaseCompoundRepsMin(8)->setBaseCompoundRepsMax(12)
            ->setBaseIsolationRepsMin(12)->setBaseIsolationRepsMax(15)
            ->setRestCompoundSeconds(120)->setRestIsolationSeconds(90)
            ->setRestBetweenMachinesSeconds(180);
        $manager->persist($goalHypertrophy);

        $goalStrength = new Goal();
        $goalStrength->setName('Budowa Siły')
            ->setBaseCompoundRepsMin(3)->setBaseCompoundRepsMax(5)
            ->setBaseIsolationRepsMin(8)->setBaseIsolationRepsMax(10)
            ->setRestCompoundSeconds(240)->setRestIsolationSeconds(120)
            ->setRestBetweenMachinesSeconds(240);
        $manager->persist($goalStrength);

        $goalReduction = new Goal();
        $goalReduction->setName('Redukcja / Rzeźba (Spalanie tłuszczu)')
            ->setBaseCompoundRepsMin(10)->setBaseCompoundRepsMax(15)
            ->setBaseIsolationRepsMin(15)->setBaseIsolationRepsMax(20)
            ->setRestCompoundSeconds(60)->setRestIsolationSeconds(45)
            ->setRestBetweenMachinesSeconds(90);
        $manager->persist($goalReduction);

        // --- 2. KONTO UŻYTKOWNIKA ---
        $user = new User();
        $user->setEmail('test@pulsekinetic.com')
            ->setName('Dzik')
            ->setAge(43)
            ->setGender('Mężczyzna')
            ->setWeight(95.0)
            ->setHeight(180.0)
            ->setCurrentGoal($goalStrength)
            ->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        
        $password = $this->hasher->hashPassword($user, 'trening123');
        $user->setPassword($password);
        $manager->persist($user);

        // --- 3. MIĘŚNIE ---
        $musclesData = ['Klatka piersiowa', 'Plecy', 'Nogi', 'Barki', 'Biceps', 'Triceps', 'Brzuch'];
        $muscleEntities = [];
        
        foreach ($musclesData as $name) {
            $muscle = new Muscle();
            $muscle->setName($name);
            $manager->persist($muscle);
            $muscleEntities[$name] = $muscle;
        }

        // --- ZAPIS DO BAZY ---
        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            MachineFixtures::class, // Wymagamy załadowania maszyn przed stworzeniem usera i historii
        ];
    }
}