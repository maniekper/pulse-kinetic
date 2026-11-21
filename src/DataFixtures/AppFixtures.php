<?php

namespace App\DataFixtures;

use App\Entity\Goal;
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
        $goalRepo = $manager->getRepository(Goal::class);
        $userRepo = $manager->getRepository(User::class);
        $muscleRepo = $manager->getRepository(Muscle::class);

        // --- 1. CELE TRENINGOWE ---
        $goalsData = [
            'hypertrophy' => [
                'name' => 'Hipertrofia (Masa mięśniowa)',
                'compoundMin' => 8,
                'compoundMax' => 12,
                'isolationMin' => 12,
                'isolationMax' => 15,
                'restCompound' => 120,
                'restIsolation' => 90,
                'restMachines' => 180,
            ],
            'strength' => [
                'name' => 'Budowa Siły',
                'compoundMin' => 3,
                'compoundMax' => 5,
                'isolationMin' => 8,
                'isolationMax' => 10,
                'restCompound' => 240,
                'restIsolation' => 120,
                'restMachines' => 240,
            ],
            'reduction' => [
                'name' => 'Redukcja / Rzeźba (Spalanie tłuszczu)',
                'compoundMin' => 10,
                'compoundMax' => 15,
                'isolationMin' => 15,
                'isolationMax' => 20,
                'restCompound' => 60,
                'restIsolation' => 45,
                'restMachines' => 90,
            ],
        ];

        $goals = [];
        foreach ($goalsData as $key => $data) {
            $goal = $goalRepo->findOneBy(['name' => $data['name']]);
            if (!$goal) {
                $goal = new Goal();
                $goal->setName($data['name'])
                    ->setBaseCompoundRepsMin($data['compoundMin'])->setBaseCompoundRepsMax($data['compoundMax'])
                    ->setBaseIsolationRepsMin($data['isolationMin'])->setBaseIsolationRepsMax($data['isolationMax'])
                    ->setRestCompoundSeconds($data['restCompound'])->setRestIsolationSeconds($data['restIsolation'])
                    ->setRestBetweenMachinesSeconds($data['restMachines']);
                $manager->persist($goal);
            }
            $goals[$key] = $goal;
        }

        // --- 2. KONTA UŻYTKOWNIKÓW ---
        $usersData = [
            [
                'email' => 'test@pulsekinetic.com',
                'name' => 'Dzik',
                'age' => 43,
                'gender' => 'Mężczyzna',
                'weight' => 95.0,
                'height' => 180.0,
                'goal' => $goals['strength'],
                'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
                'password' => 'trening123',
            ],
            [
                'email' => 'joa@pulsekinetic.com',
                'name' => 'Joa',
                'age' => 35,
                'gender' => 'Kobieta',
                'weight' => 60.0,
                'height' => 165.0,
                'goal' => $goals['reduction'],
                'roles' => ['ROLE_USER'],
                'password' => 'trening123',
            ],
            [
                'email' => 'bober@pulsekinetic.com',
                'name' => 'Bober',
                'age' => 30,
                'gender' => 'Mężczyzna',
                'weight' => 80.0,
                'height' => 178.0,
                'goal' => $goals['hypertrophy'],
                'roles' => ['ROLE_USER'],
                'password' => 'bober123',
            ],
            [
                'email' => 'wojtek@pulsekinetic.com',
                'name' => 'Wojtek',
                'age' => 18,
                'gender' => 'Mężczyzna',
                'weight' => 80.0,
                'height' => 178.0,
                'goal' => $goals['hypertrophy'],
                'roles' => ['ROLE_USER'],
                'password' => 'wojtek123',
            ],
            [
                'email' => 'usun@pulsekinetic.com',
                'name' => 'Usuń',
                'age' => 30,
                'gender' => 'Mężczyzna',
                'weight' => 80.0,
                'height' => 178.0,
                'goal' => $goals['hypertrophy'],
                'roles' => ['ROLE_USER'],
                'password' => 'trening123',
            ],
        ];

        foreach ($usersData as $uData) {
            if ($userRepo->findOneBy(['email' => $uData['email']])) {
                continue;
            }

            $user = new User();
            $user->setEmail($uData['email'])
                ->setName($uData['name'])
                ->setAge($uData['age'])
                ->setGender($uData['gender'])
                ->setWeight($uData['weight'])
                ->setHeight($uData['height'])
                ->setCurrentGoal($uData['goal'])
                ->setRoles($uData['roles']);

            $user->setPassword($this->hasher->hashPassword($user, $uData['password']));
            $manager->persist($user);
        }

        // --- 3. MIĘŚNIE ---
        $musclesData = [
            'Klatka piersiowa',
            'Plecy',
            'Nogi',
            'Barki',
            'Biceps',
            'Triceps',
            'Brzuch',
            'Przedramiona',
            'Kaptur',
            'Najszerszy grzbietu',
            'Czworoboczny grzbietu',
            'Prosty brzucha',
            'Skośne brzucha',
            'Pośladki',
            'Przywodziciele',
            'Odwodziciele',
            'Czworogłowy uda',
            'Dwugłowy uda',
            'Łydki'
        ];

        foreach ($musclesData as $name) {
            if (!$muscleRepo->findOneBy(['name' => $name])) {
                $muscle = new Muscle();
                $muscle->setName($name);
                $manager->persist($muscle);
            }
        }

        // --- ZAPIS DO BAZY ---
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MachineFixtures::class,
        ];
    }
}
