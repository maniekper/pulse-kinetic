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
            $muscleEntities[$name] = $muscle;
        }

        // --- 4. MASZYNY I ĆWICZENIA (Baza Wiedzy) ---
        $machinesList = [
            // Klasyczne wolne ciężary (z poprzedniej wersji)
            [
                'name' => 'Wyciskanie sztangi na ławce płaskiej',
                'description' => 'Podstawowe ćwiczenie wielostawowe budujące klatkę piersiową.',
                'tips' => 'Ściągnij łopatki, łokcie prowadź pod kątem 45 stopni.',
                'category' => 'Wolne ciężary',
                'mechanicType' => 'compound',
                'primary' => 'Klatka piersiowa',
                'secondary' => ['Triceps', 'Barki']
            ],
            [
                'name' => 'Uginanie ramion z hantlami na ławce skośnej',
                'description' => 'Ćwiczenie izolowane na biceps pozwalające na maksymalne rozciągnięcie.',
                'tips' => 'Nie bujaj tułowiem, ruch inicjuj wyłącznie zgięciem w łokciu.',
                'category' => 'Wolne ciężary',
                'mechanicType' => 'isolation',
                'primary' => 'Biceps',
                'secondary' => []
            ],

            // Maszyny na Klatkę Piersiową
            [
                'name' => 'Maszyna Smitha (Smith Machine)',
                'description' => 'Sztanga poruszająca się na wyznaczonych, pionowych lub lekko skośnych prowadnicach.',
                'category' => 'Maszyny',
                'mechanicType' => 'compound',
                'primary' => 'Klatka piersiowa',
                'secondary' => ['Triceps', 'Barki']
            ],
            [
                'name' => 'Brama z wyciągami (Cable Crossover)',
                'description' => 'Dwustronny wyciąg z regulacją wysokości rolek, wszechstronny do klatki i ramion.',
                'category' => 'Wyciągi',
                'mechanicType' => 'isolation',
                'primary' => 'Klatka piersiowa',
                'secondary' => ['Barki']
            ],
            [
                'name' => 'Pec Deck / Butterfly',
                'description' => 'Maszyna do rozpiętek w siadzie, izolująca mięśnie klatki piersiowej.',
                'category' => 'Maszyny',
                'mechanicType' => 'isolation',
                'primary' => 'Klatka piersiowa',
                'secondary' => []
            ],
            [
                'name' => 'Maszyna do wyciskania na klatkę siedząc (Chest Press)',
                'description' => 'Symuluje wyciskanie sztangi lub hantli z zachowaniem stałego toru ruchu.',
                'category' => 'Maszyny',
                'mechanicType' => 'compound',
                'primary' => 'Klatka piersiowa',
                'secondary' => ['Triceps', 'Barki']
            ],

            // Maszyny na Plecy
            [
                'name' => 'Wyciąg górny (Lat Pulldown)',
                'description' => 'Maszyna ze stosem do ściągania drążka z góry (trening najszerszego grzbietu).',
                'category' => 'Wyciągi',
                'mechanicType' => 'compound',
                'primary' => 'Plecy',
                'secondary' => ['Biceps']
            ],
            [
                'name' => 'Wyciąg dolny (Seated Cable Row)',
                'description' => 'Służy do przyciągania uchwytu do brzucha w siadzie (wiosłowanie).',
                'category' => 'Wyciągi',
                'mechanicType' => 'compound',
                'primary' => 'Plecy',
                'secondary' => ['Biceps']
            ],
            [
                'name' => 'Maszyna typu T-bar (Chest-supported Row)',
                'description' => 'Wiosłowanie w opadzie tułowia z podparciem klatki piersiowej.',
                'category' => 'Maszyny wolny ciężar',
                'mechanicType' => 'compound',
                'primary' => 'Plecy',
                'secondary' => ['Biceps']
            ],
            [
                'name' => 'Asystent podciągania i pompek (Assisted Pull-up/Dip Machine)',
                'description' => 'Maszyna z przeciwwagą ułatwiająca podciąganie na drążku oraz pompki na poręczach.',
                'category' => 'Maszyny',
                'mechanicType' => 'compound',
                'primary' => 'Plecy',
                'secondary' => ['Triceps', 'Klatka piersiowa']
            ],
            [
                'name' => 'Ławka rzymska (Roman Chair / Back Extension)',
                'description' => 'Stanowisko do prostowania tułowia angażujące prostowniki grzbietu.',
                'category' => 'Maszyny z masą ciała',
                'mechanicType' => 'isolation',
                'primary' => 'Plecy',
                'secondary' => ['Nogi']
            ],

            // Maszyny na Barki
            [
                'name' => 'Maszyna do wyciskania na barki (Shoulder Press)',
                'description' => 'Pozwala na bezpieczne wyciskanie ciężaru nad głowę w pozycji siedzącej.',
                'category' => 'Maszyny',
                'mechanicType' => 'compound',
                'primary' => 'Barki',
                'secondary' => ['Triceps']
            ],
            [
                'name' => 'Maszyna do wznosów bokiem (Lateral Raise Machine)',
                'description' => 'Izoluje środkowy akton mięśnia naramiennego.',
                'category' => 'Maszyny',
                'mechanicType' => 'isolation',
                'primary' => 'Barki',
                'secondary' => []
            ],

            // Maszyny na Ramiona
            [
                'name' => 'Modlitewnik na maszynie (Preacher Curl Machine)',
                'description' => 'Izolowane uginanie ramion z oparciem o pulpit.',
                'category' => 'Maszyny',
                'mechanicType' => 'isolation',
                'primary' => 'Biceps',
                'secondary' => []
            ],
            [
                'name' => 'Maszyna na triceps w siadzie (Triceps Press)',
                'description' => 'Symuluje pompki na poręczach (wciskanie uchwytów w dół).',
                'category' => 'Maszyny',
                'mechanicType' => 'compound',
                'primary' => 'Triceps',
                'secondary' => ['Barki']
            ],
            [
                'name' => 'Maszyna do prostowania ramion na triceps (Triceps Extension)',
                'description' => 'Pozwala na prostowanie przedramion z podparciem.',
                'category' => 'Maszyny',
                'mechanicType' => 'isolation',
                'primary' => 'Triceps',
                'secondary' => []
            ],
            [
                'name' => 'Wyciąg pojedynczy kolumnowy',
                'description' => 'Stacja z jedną linką i stosem, idealna do izolacji ramion.',
                'category' => 'Wyciągi',
                'mechanicType' => 'isolation',
                'primary' => 'Triceps',
                'secondary' => ['Biceps']
            ],

            // Maszyny na Dolne Partie Ciała
            [
                'name' => 'Suwnica na nogi (Leg Press)',
                'description' => 'Liniowe wypychanie platformy nogami pod kątem lub poziomo.',
                'category' => 'Maszyny',
                'mechanicType' => 'compound',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Hack-maszyna (Hack Squat)',
                'description' => 'Maszyna do przysiadów z oparciem pleców na suwnicy.',
                'category' => 'Maszyny',
                'mechanicType' => 'compound',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Maszyna do prostowania nóg w siadzie (Leg Extension)',
                'description' => 'Izoluje mięśnie czworogłowe ud.',
                'category' => 'Maszyny',
                'mechanicType' => 'isolation',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Maszyna do uginania nóg leżąc (Prone Leg Curl)',
                'description' => 'Izoluje mięśnie dwugłowe ud w leżeniu przodem.',
                'category' => 'Maszyny',
                'mechanicType' => 'isolation',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Maszyna do uginania nóg siedząc (Seated Leg Curl)',
                'description' => 'Alternatywa dla wersji leżącej, angażująca tył uda.',
                'category' => 'Maszyny',
                'mechanicType' => 'isolation',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Maszyna na przywodziciele (Adductor Machine)',
                'description' => 'Ściskanie ud do wewnątrz w siadzie.',
                'category' => 'Maszyny',
                'mechanicType' => 'isolation',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Maszyna na odwodziciele (Abductor Machine)',
                'description' => 'Rozchylanie ud na zewnątrz.',
                'category' => 'Maszyny',
                'mechanicType' => 'isolation',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Maszyna do wspięć na łydki stojąc (Standing Calf Raise)',
                'description' => 'Wspięcia na palce z ciężarem na barkach.',
                'category' => 'Maszyny',
                'mechanicType' => 'isolation',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Maszyna do wspięć na łydki siedząc (Seated Calf Raise)',
                'description' => 'Wspięcia na palce z ciężarem dociskającym kolana (mięsień płaszczkowaty).',
                'category' => 'Maszyny',
                'mechanicType' => 'isolation',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Maszyna do wypychania bioder (Hip Thrust Machine)',
                'description' => 'Dedykowana do mocnego treningu pośladków.',
                'category' => 'Maszyny',
                'mechanicType' => 'compound',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Belt Squat Machine',
                'description' => 'Przysiady z obciążeniem na pasie biodrowym (odciąża kręgosłup).',
                'category' => 'Maszyny',
                'mechanicType' => 'compound',
                'primary' => 'Nogi',
                'secondary' => []
            ],

            // Maszyny na Brzuch
            [
                'name' => 'Maszyna do spięć brzucha (Ab Crunch Machine)',
                'description' => 'Mechaniczne przyciąganie klatki piersiowej do kolan z oporem.',
                'category' => 'Maszyny',
                'mechanicType' => 'isolation',
                'primary' => 'Brzuch',
                'secondary' => []
            ],
            [
                'name' => 'Maszyna do rotacji tułowia (Rotary Torso)',
                'description' => 'Stanowisko do skrętów tułowia, celujące w mięśnie skośne brzucha.',
                'category' => 'Maszyny',
                'mechanicType' => 'isolation',
                'primary' => 'Brzuch',
                'secondary' => []
            ],

            // Cardio (Zaimplementowane jako izolacje, aby współgrały z resztą algorytmu treningu siłowego)
            [
                'name' => 'Bieżnia elektryczna (Treadmill)',
                'description' => 'Do marszu, truchtu i biegania z regulacją kąta nachylenia.',
                'category' => 'Cardio',
                'mechanicType' => 'isolation',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Orbitrek / Maszyna eliptyczna (Elliptical)',
                'description' => 'Symuluje bieg narciarski bez obciążania stawów.',
                'category' => 'Cardio',
                'mechanicType' => 'isolation',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Ergometr wioślarski (Rowing Machine)',
                'description' => 'Symuluje wiosłowanie na wodzie, mocno angażując plecy i nogi.',
                'category' => 'Cardio',
                'mechanicType' => 'compound',
                'primary' => 'Plecy',
                'secondary' => ['Nogi', 'Biceps']
            ],
            [
                'name' => 'Schody treningowe (StairMaster / Stair Climber)',
                'description' => 'Ruchome schody o zmiennym tempie rotacji.',
                'category' => 'Cardio',
                'mechanicType' => 'compound',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Rower stacjonarny pionowy/poziomy',
                'description' => 'Klasyczny rowerek do jazdy w pozycji wyprostowanej lub z oparciem.',
                'category' => 'Cardio',
                'mechanicType' => 'isolation',
                'primary' => 'Nogi',
                'secondary' => []
            ],
            [
                'name' => 'Rower powietrzny (Air Bike)',
                'description' => 'Rower z oporem wentylatorowym, napędzany jednocześnie pracą nóg i rąk.',
                'category' => 'Cardio',
                'mechanicType' => 'compound',
                'primary' => 'Nogi',
                'secondary' => ['Barki', 'Plecy']
            ]
        ];

        // --- PĘTLA GENERUJĄCA MASZYNY ---
        foreach ($machinesList as $data) {
            $machine = new Machine();
            $machine->setName($data['name'])
                ->setDescription($data['description'] ?? '')
                ->setTips($data['tips'] ?? '')
                ->setImageStart('default_start.jpg') // Możesz zaktualizować na docelowe grafiki później
                ->setImageEnd('default_end.jpg')
                ->setCategory($data['category'] ?? 'Maszyny')
                ->setMechanicType($data['mechanicType']);
            
            // Przypisanie głównego mięśnia
            if (isset($data['primary']) && isset($muscleEntities[$data['primary']])) {
                $machine->addPrimaryMuscle($muscleEntities[$data['primary']]);
            }
            
            // Przypisanie mięśni pomocniczych
            if (isset($data['secondary'])) {
                foreach ($data['secondary'] as $secMuscle) {
                    if (isset($muscleEntities[$secMuscle])) {
                        $machine->addSecondaryMuscle($muscleEntities[$secMuscle]);
                    }
                }
            }

            $manager->persist($machine);
        }

        // --- ZAPIS DO BAZY ---
        $manager->flush();
    }
}