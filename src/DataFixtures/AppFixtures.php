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

        // --- 2. KONTO UŻYTKOWNIKA ---
        $user = new User();
        $user->setEmail('test@pulsekinetic.com')
            ->setName('Dzik')
            ->setAge(43)
            ->setGender('Mężczyzna')
            ->setWeight(104.0)
            ->setHeight(180.0)
            ->setCurrentGoal($goalStrength)
            ->setRoles(['ROLE_USER']);
        
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
            $muscleEntities[$name] = $muscle; // Zapisujemy do tablicy, by łatwo przypiąć do maszyn
        }

        // --- 4. MASZYNY (Baza Wiedzy) ---
        $machine1 = new Machine();
        $machine1->setName('Wyciskanie sztangi na ławce płaskiej')
            ->setDescription('Podstawowe ćwiczenie wielostawowe budujące klatkę piersiową.')
            ->setTips('Ściągnij łopatki, łokcie prowadź pod kątem 45 stopni względem tułowia.')
            ->setImageStart('bench_press_start.jpg')
            ->setImageEnd('bench_press_end.jpg')
            ->setCategory('Wolne ciężary')
            ->setMechanicType('compound')
            ->addPrimaryMuscle($muscleEntities['Klatka piersiowa'])
            ->addSecondaryMuscle($muscleEntities['Triceps'])
            ->addSecondaryMuscle($muscleEntities['Barki']);
        $manager->persist($machine1);

        $machine2 = new Machine();
        $machine2->setName('Uginanie ramion z hantlami na ławce skośnej')
            ->setDescription('Ćwiczenie izolowane na biceps pozwalające na maksymalne rozciągnięcie mięśnia.')
            ->setTips('Nie bujaj tułowiem, ruch inicjuj wyłącznie zgięciem w łokciu.')
            ->setImageStart('incline_curl_start.jpg')
            ->setImageEnd('incline_curl_end.jpg')
            ->setCategory('Wolne ciężary')
            ->setMechanicType('isolation')
            ->addPrimaryMuscle($muscleEntities['Biceps']);
        $manager->persist($machine2);

        // --- ZAPIS DO BAZY ---
        $manager->flush();
    }
}