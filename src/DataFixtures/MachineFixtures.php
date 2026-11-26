<?php

namespace App\DataFixtures;

use App\Entity\Machine;
use App\Entity\Muscle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MachineFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1. PRECYZYJNY SŁOWNIK ANATOMICZNY
        $musclesData = [
            'Klatka piersiowa - góra',
            'Klatka piersiowa - środek',
            'Klatka piersiowa - dół',
            'Barki - przód',
            'Barki - bok',
            'Barki - tył',
            'Najszerszy grzbietu',
            'Górna część pleców',
            'Prostownik grzbietu',
            'Kaptur',
            'Biceps',
            'Triceps - głowa długa',
            'Triceps - głowa boczna',
            'Triceps - głowa przyśrodkowa',
            'Przedramiona - zginacze (przód)',
            'Przedramiona - prostowniki (tył)',
            'Czworogłowy uda',
            'Tył uda (Kulszowo-goleniowe)',
            'Pośladki',
            'Przywodziciele',
            'Odwodziciele',
            'Łydki',
            'Prosty brzucha',
            'Skośne brzucha',
            // Grupy ogólne (zostawione do czasu sprawdzenia generatora planów)
            'Plecy',
            'Nogi',
            'Brzuch',
        ];

        $muscleRepo = $manager->getRepository(Muscle::class);
        $muscleEntities = [];

        foreach ($musclesData as $name) {
            $muscle = $muscleRepo->findOneBy(['name' => $name]);
            if (!$muscle) {
                $muscle = new Muscle();
                $muscle->setName($name);
                $manager->persist($muscle);
            }
            $muscleEntities[$name] = $muscle;
        }

        // --- MASZYNY I ĆWICZENIA (Baza Wiedzy) ---
        $machinesList = [
            [
                'name' => 'Fly',
                'description' => 'Usiądź na maszynie i oprzyj plecy płasko o oparcie. Chwyć uchwyty z ramionami rozłożonymi na boki, zachowując lekko ugięte łokcie. Płynnym ruchem przyciągnij uchwyty do siebie przed klatkę piersiową, mocno napinając mięśnie. Powoli i pod pełną kontrolą wróć do pozycji wyjściowej.',
                'tips' => 'Przez cały czas trwania ruchu utrzymuj klatkę piersiową wypchniętą do przodu, a łopatki ściągnięte i przylegające do oparcia. Unikaj całkowitego prostowania rąk w stawach łokciowych.',
                'category' => 'Klatka piersiowa',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Klatka piersiowa - środek',
                'secondary' => ['Barki - przód'],
                'image_prefix' => 'fly',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Lat Pulldown',
                'description' => 'Ściąganie drążka wyciągu górnego na maszynie w siadzie. Ćwiczenie budujące szerokość i grubość mięśni grzbietu, zapewniające stabilną i kontrolowaną trajektorię ruchu.',
                'tips' => 'Prowadź ruch łokciami w dół i nieznacznie do przodu, ściągając łopatki. Unikaj nadmiernego odchylania tułowia w tył.',
                'category' => 'Plecy',
                'mechanicType' => 'compound',
                'movementPattern' => 'vertical_pull',
                'primary' => 'Najszerszy grzbietu',
                'secondary' => ['Biceps', 'Górna część pleców'],
                'image_prefix' => 'lat_pulldown',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Rear Delt',
                'description' => 'Usiądź przodem do maszyny, opierając klatkę piersiową stabilnie o poduszkę. Chwyć uchwyty przed sobą, zachowując ramiona delikatnie ugięte w łokciach (pozycja startowa). Płynnym ruchem odwiedź ramiona na zewnątrz i w tył po szerokim łuku, aż dłonie znajdą się na wysokości barków (pozycja końcowa). Kontrolując ciężar, powoli wróć do pozycji wyjściowej.',
                'tips' => 'Utrzymuj stały, delikatny kąt zgięcia w łokciach. Nie używaj pędu tułowia, praca ma wychodzić wyłącznie z tylnego aktonu barków.',
                'category' => 'Barki',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Barki - tył',
                'secondary' => ['Górna część pleców', 'Kaptur'],
                'image_prefix' => 'rear_delt',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Leg Press',
                'description' => 'Usiądź na maszynie, opierając plecy i głowę o oparcie. Umieść stopy płasko na platformie na szerokość barków. Złap za boczne uchwyty, aby ustabilizować tułów. Płynnym ruchem wypchnij platformę, prostując nogi (nie blokuj kolan), a następnie powoli, kontrolując ciężar, ugnij nogi wracając do pozycji startowej z kolanami blisko tułowia.',
                'tips' => 'Zawsze utrzymuj całe stopy na platformie (nie odrywaj pięt) i pilnuj, aby dolny odcinek pleców stale przylegał do oparcia.',
                'category' => 'Nogi',
                'mechanicType' => 'compound',
                'movementPattern' => 'squat',
                'primary' => 'Czworogłowy uda',
                'secondary' => ['Pośladki', 'Łydki'],
                'image_prefix' => 'leg_press',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Uginanie nóg leżąc na maszynie',
                'description' => 'Połóż się przodem na maszynie i zaprzyj dolną część łydek pod wałkiem. Płynnym ruchem ugnij nogi w kolanach, przyciągając wałek w stronę pośladków, a następnie w kontrolowany sposób wróć do pozycji wyjściowej.',
                'tips' => 'Utrzymuj miednicę mocno dociśniętą do ławki przez cały zakres ruchu. Zapobiegnie to unoszeniu bioder i przenoszeniu napięcia na odcinek lędźwiowy, maksymalizując izolację dwugłowych uda.',
                'category' => 'Nogi',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Tył uda (Kulszowo-goleniowe)',
                'secondary' => ['Łydki', 'Pośladki'],
                'image_prefix' => 'prone_leg_curl',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Wyciskanie nad głowę na maszynie siedząc (zbieżne)',
                'description' => 'Usiądź stabilnie na maszynie, opierając plecy o oparcie. Chwyć uchwyty na wysokości barków. Wypchnij ciężar płynnie w górę, prostując ramiona, aż uchwyty zbiegną się nad głową. Następnie w kontrolowany sposób opuść ramiona do pozycji wyjściowej.',
                'tips' => 'Utrzymuj stabilną pozycję tułowia, unikając wyginania odcinka lędźwiowego. Łokcie prowadź lekko przed linią tułowia, aby zoptymalizować pracę mięśni naramiennych i chronić stawy.',
                'category' => 'Barki',
                'mechanicType' => 'compound',
                'movementPattern' => 'overhead_press',
                'primary' => 'Barki - przód',
                'secondary' => ['Triceps - głowa boczna', 'Triceps - głowa przyśrodkowa', 'Klatka piersiowa - góra', 'Kaptur'],
                'image_prefix' => 'converging_shoulder_press',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Maszyna na przywodziciele (Adductor Machine In)',
                'description' => 'Usiądź na maszynie, stabilnie opierając plecy o oparcie. Umieść wewnętrzną część ud na poduszkach oporowych, a stopy na przeznaczonych do tego podpórkach. Płynnym ruchem przyciągnij uda do siebie do środka, pokonując opór maszyny, po czym powoli wróć do szerokiego ustawienia wyjściowego.',
                'tips' => 'Ruch powrotny (ekscentryczny) wykonuj powoli i pod pełną kontrolą. Nie pozwól, aby ciężar gwałtownie opadł, utrzymując ciągłe napięcie mięśni.',
                'category' => 'Nogi',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Przywodziciele',
                'secondary' => [],
                'image_prefix' => 'adductor_machine_in',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Podciąganie z asystą (Chin Assist)',
                'description' => 'Uklęknij na platformie maszyny, chwytając górne drążki nachwytem. Utrzymując proste plecy, podciągnij się siłą mięśni grzbietu, aż broda znajdzie się na wysokości uchwytów, a następnie powoli opuść ciało do pełnego wyprostu ramion.',
                'tips' => 'Kontroluj ruch opuszczania i nie pozwól, aby platforma gwałtownie opadła. Skup się na pracy łopatek, ściągając je w dół i do siebie podczas podciągania.',
                'category' => 'Plecy',
                'mechanicType' => 'compound',
                'movementPattern' => 'vertical_pull',
                'primary' => 'Najszerszy grzbietu',
                'secondary' => ['Biceps', 'Górna część pleców', 'Przedramiona - zginacze (przód)', 'Prosty brzucha'],
                'image_prefix' => 'chin_assist',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Dipy z asystą (Dip Assist)',
                'description' => 'Uklęknij na ruchomej platformie maszyny, chwytając dolne poręcze neutralnym chwytem. Przyjmij pozycję wyjściową z ramionami w pełni wyprostowanymi, a następnie powoli i kontrolowanie opuszczaj ciało, uginając łokcie do kąta około 90 stopni, po czym dynamicznym ruchem wyciśnij się w górę, prostując ramiona.',
                'tips' => 'Prowadź łokcie blisko tułowia, aby mocniej zaangażować triceps i chronić stawy barkowe. Unikaj pełnego blokowania łokci w najwyższym punkcie, utrzymując stałe napięcie mięśniowe.',
                'category' => 'Ramiona',
                'mechanicType' => 'compound',
                'movementPattern' => 'other',
                'primary' => 'Triceps - głowa przyśrodkowa',
                'secondary' => ['Triceps - głowa boczna', 'Klatka piersiowa - dół', 'Barki - przód', 'Prosty brzucha'],
                'image_prefix' => 'dip_assist',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Go Series Abdominal',
                'description' => 'Usiądź na siedzisku maszyny i wsuń stopy pod dolne wałki oporowe. Złap dłońmi górne uchwyty. Wykonaj wydech i płynne spięcie mięśni brzucha, przyciągając klatkę piersiową do miednicy, pociągając za sobą ruchome ramię maszyny. Powoli i pod pełną kontrolą wróć do pozycji wyjściowej, wykonując wdech.',
                'tips' => 'Ruch powinien być inicjowany i kontrolowany wyłącznie z mięśni brzucha. Unikaj ciągnięcia ciężaru samymi ramionami. Utrzymuj stałe napięcie mięśniowe przez całą fazę ekscentryczną.',
                'category' => 'Brzuch',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Prosty brzucha',
                'secondary' => ['Skośne brzucha'],
                'image_prefix' => 'go_series_abdominal',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Wyciskanie na maszynie siedząc Chest Press',
                'description' => 'Usiądź stabilnie, dociskając plecy do oparcia maszyny. Chwyć uchwyty na szerokości barków na wysokości klatki piersiowej. Wypchnij ciężar płynnym ruchem przed siebie, aż do wyprostu ramion, po czym powolnym i kontrolowanym ruchem opuść ciężar z powrotem do pozycji wyjściowej.',
                'tips' => 'Utrzymuj klatkę piersiową wypchniętą do przodu i łopatki ściągnięte podczas całego ruchu. Unikaj blokowania łokci w fazie końcowej, aby zachować ciągłe napięcie mięśni.',
                'category' => 'Klatka piersiowa',
                'mechanicType' => 'compound',
                'movementPattern' => 'horizontal_push',
                'primary' => 'Klatka piersiowa - środek',
                'secondary' => ['Barki - przód', 'Triceps - głowa boczna'],
                'image_prefix' => 'chest_press_machine',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Prostowanie nóg na maszynie siedząc Leg Extension',
                'description' => 'Usiądź na maszynie, dociskając plecy do oparcia. Umieść dół goleni za wałkiem oporowym, a dłonie na bocznych uchwytach. Płynnym ruchem wyprostuj nogi w stawach kolanowych, unosząc wałek do góry, a następnie w kontrolowany sposób opuść ciężar powracając do pozycji wyjściowej.',
                'tips' => 'Utrzymuj stabilny tułów i nie odrywaj pośladków od siedziska. Skup się na pełnej kontroli ciężaru w fazie ekscentrycznej (opuszczania), unikając swobodnego opadania ramienia maszyny.',
                'category' => 'Nogi',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Czworogłowy uda',
                'secondary' => [],
                'image_prefix' => 'leg_extension_machine',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Uginanie ramion na modlitewniku',
                'description' => 'Usiądź na ławce, opierając klatkę piersiową i tył ramion (tricepsy) stabilnie na poduszce modlitewnika. Chwyć sztangę podchwytem. Płynnym ruchem ugnij przedramiona, unosząc ciężar w stronę barków, a następnie w kontrolowany sposób opuść sztangę aż do pełnego wyprostu ramion.',
                'tips' => 'Podczas całego ruchu nie odrywaj łokci ani ramion od poduszki. Ruch powinien izolować biceps – unikaj pomagania sobie balansem tułowia.',
                'category' => 'Ramiona',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Biceps',
                'secondary' => ['Przedramiona - zginacze (przód)'],
                'image_prefix' => 'arm_curl_preacher',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Wyciskanie sztangi na ławce poziomej',
                'description' => 'Połóż się płasko na ławce, opierając stopy stabilnie na podłożu lub podestach. Chwyć sztangę nachwytem nieco szerzej niż rozstaw barków. Zdejmij ciężar ze stojaków. Kontrolując ruch, powoli opuść sztangę do środkowej części klatki piersiowej, a następnie dynamicznie wyciśnij ją w górę aż do pełnego wyprostu ramion.',
                'tips' => 'Zadbaj o stabilną pozycję poprzez ściągnięcie łopatek do siebie i w dół (retrakcja i depresja). Nie odrywaj pośladków od ławki i unikaj sprężynującego odbijania sztangi od klatki piersiowej w dolnej fazie ruchu.',
                'category' => 'Klatka piersiowa',
                'mechanicType' => 'compound',
                'movementPattern' => 'horizontal_push',
                'primary' => 'Klatka piersiowa - środek',
                'secondary' => ['Barki - przód', 'Triceps - głowa boczna'],
                'image_prefix' => 'flat_bench_press',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Wyciskanie sztangi na ławce skośnej dodatniej',
                'description' => 'Połóż się na ławce skośnej (około 30-45 stopni), opierając stopy stabilnie na podłożu. Chwyć sztangę nachwytem nieco szerzej niż rozstaw barków. Zdejmij ciężar ze stojaków i trzymaj go nad górną częścią klatki piersiowej. Kontrolując ruch, powoli opuść sztangę w okolice obojczyków (górna część klatki), a następnie dynamicznie wyciśnij ją w górę, prostując ramiona.',
                'tips' => 'Zadbaj o mocne ściągnięcie łopatek do tyłu i w dół przez cały czas trwania ćwiczenia. Unikaj odbijania gryfu od klatki piersiowej i dbaj o to, by łokcie nie rozchodziły się nadmiernie w stronę głowy – powinny być prowadzone lekko do wewnątrz.',
                'category' => 'Klatka piersiowa',
                'mechanicType' => 'compound',
                'movementPattern' => 'horizontal_push',
                'primary' => 'Klatka piersiowa - góra',
                'secondary' => ['Barki - przód', 'Triceps - głowa boczna'],
                'image_prefix' => 'incline_bench_press',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Wyciskanie sztangi na ławce skośnej ujemnej',
                'description' => 'Zajmij pozycję na ławce skośnej ujemnej, blokując stopy i podudzia za wałkami oporowymi. Połóż się, chwyć sztangę nachwytem nieco szerzej niż rozstaw barków i zdejmij ją ze stojaków. Powoli, kontrolowanym ruchem opuść sztangę do dolnej części klatki piersiowej, a następnie dynamicznie wyciśnij ją w górę do pełnego wyprostu ramion.',
                'tips' => 'Pamiętaj o ciągłym ściągnięciu łopatek i nie odrywaj bioder ani pleców od ławki. Wyciskaj ciężar w naturalnej, lekko łukowatej płaszczyźnie, nie blokując gwałtownie łokci w najwyższym punkcie.',
                'category' => 'Klatka piersiowa',
                'mechanicType' => 'compound',
                'movementPattern' => 'horizontal_push',
                'primary' => 'Klatka piersiowa - dół',
                'secondary' => ['Barki - przód', 'Triceps - głowa boczna'],
                'image_prefix' => 'decline_bench_press',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Wykop na maszynie do pośladków',
                'description' => 'Zajmij pozycję na maszynie, opierając przedramiona na poduszkach i chwytając za uchwyty. Umieść stopę jednej nogi na ruchomej platformie/ramieniu wahadłowym, a nogę podporową postaw stabilnie na dolnej platformie. Oprzyj biodra o poduszkę przednią. Płynnym ruchem wypchnij stopę w tył i lekko w górę, prostując nogę, a następnie w kontrolowany sposób powróć do pozycji wyjściowej.',
                'tips' => 'Ruch powinien być inicjowany wyłącznie przez pośladek i dwugłowy uda – unikaj rotacji w biodrach oraz nadmiernego wyginania dolnego odcinka pleców (odcinka lędźwiowego). Skup się na pełnym napięciu mięśnia w fazie maksymalnego wyprostu.',
                'category' => 'Nogi',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Pośladki',
                'secondary' => ['Tył uda (Kulszowo-goleniowe)'],
                'image_prefix' => 'glute_machine',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Incline Lever Row',
                'description' => 'Oprzyj klatkę piersiową stabilnie o skośną poduszkę, a stopy zaprzyj o dolne platformy. Chwyć uchwyty i płynnym ruchem przyciągnij je w stronę tułowia, prowadząc łokcie blisko ciała i mocno ściągając łopatki. Wróć do pozycji wyjściowej, w pełni kontrolując opuszczanie ciężaru.',
                'tips' => 'Przez cały czas trwania ruchu utrzymuj klatkę piersiową przyklejoną do oparcia. Inicjuj pracę z mięśni grzbietu, zapobiegając szarpaniu ciężarem.',
                'category' => 'Plecy',
                'mechanicType' => 'compound',
                'movementPattern' => 'horizontal_pull',
                'primary' => 'Najszerszy grzbietu',
                'secondary' => ['Górna część pleców', 'Kaptur', 'Biceps', 'Barki - tył'],
                'image_prefix' => 'incline_lever_row',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Wypychanie nóg na suwnicy pod kątem 45 stopni',
                'description' => 'Usiądź na maszynie, opierając plecy i pośladki o oparcie. Stopy ułóż na platformie na szerokość barków. Odblokuj ciężar i powoli opuszczaj platformę, zginając kolana do kąta około 90 stopni. Następnie kontrolowanym ruchem wypchnij ciężar do pozycji wyjściowej, nie blokując całkowicie kolan.',
                'tips' => 'Przez cały czas trwania ruchu pilnuj, aby dolny odcinek pleców mocno przylegał do oparcia. Upewnij się również, że pięty nie odrywają się od platformy podczas opuszczania ciężaru.',
                'category' => 'Nogi',
                'mechanicType' => 'compound',
                'movementPattern' => 'squat',
                'primary' => 'Czworogłowy uda',
                'secondary' => ['Pośladki', 'Tył uda (Kulszowo-goleniowe)', 'Łydki'],
                'image_prefix' => '45_degree_leg_press',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Hack Przysiad na maszynie (Hack Squat)',
                'description' => 'Oprzyj plecy o oparcie maszyny, a barki umieść pod poduszkami oporowymi. Ustaw stopy na platformie na szerokość barków, lekko skierowane na zewnątrz. Odblokuj zabezpieczenia i kontrolowanym ruchem obniżaj pozycję, zginając kolana, aż uda znajdą się co najmniej równolegle do platformy. Następnie, odpychając się całą powierzchnią stóp, dynamicznie wróć do pozycji wyjściowej.',
                'tips' => 'Przez cały czas trwania ruchu utrzymuj całe plecy płasko oparte o oparcie maszyny, nie odrywając bioder ani lędźwi. Kontroluj, aby kolana nie schodziły się do środka ani nie odrywały się pięty podczas opuszczania ciężaru.',
                'category' => 'Nogi',
                'mechanicType' => 'compound',
                'movementPattern' => 'squat',
                'primary' => 'Czworogłowy uda',
                'secondary' => ['Pośladki', 'Tył uda (Kulszowo-goleniowe)', 'Łydki'],
                'image_prefix' => 'hack_squat',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Wiosłowanie na wyciągu dolnym w siadzie',
                'description' => 'Usiądź na siedzisku i oprzyj stopy o platformę z lekko ugiętymi kolanami. Chwyć rączkę wyciągu (np. uchwyt V) i wyprostuj plecy. Z pozycji z wyprostowanymi rękami przyciągnij uchwyt do brzucha, prowadząc łokcie blisko tułowia i ściągając łopatki. Wróć do pozycji wyjściowej powolnym, kontrolowanym ruchem.',
                'tips' => 'Trzymaj klatkę piersiową wypchniętą do przodu i nie garb się. Unikaj nadmiernego bujania tułowiem; ruch powinien wychodzić głównie z pracy mięśni grzbietu i rąk.',
                'category' => 'Plecy',
                'mechanicType' => 'compound',
                'movementPattern' => 'horizontal_pull',
                'primary' => 'Najszerszy grzbietu',
                'secondary' => ['Górna część pleców', 'Kaptur', 'Biceps', 'Przedramiona - zginacze (przód)'],
                'image_prefix' => 'low_row',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Prostowanie ramion na wyciągu (Triceps Pressdown)',
                'description' => 'Stań przodem do wyciągu górnego, chwytając rączkę (np. uchwyt V) nachwytem. Oprzyj się stabilnie o poduszkę maszyny. Zablokuj łokcie blisko tułowia – ugnij je tak, aby przedramiona znalazły się nieco powyżej linii równoległej do podłogi. Z tej pozycji, używając wyłącznie siły tricepsów, wyprostuj ramiona w dół, aż rączka dotknie okolic ud. Powoli i w pełni kontrolowanie wróć do pozycji wyjściowej.',
                'tips' => 'To ćwiczenie izolowane – ruch musi odbywać się wyłącznie w stawie łokciowym. Upewnij się, że łokcie nie uciekają na boki ani nie wędrują do przodu i do tyłu podczas wykonywania powtórzenia.',
                'category' => 'Ramiona',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Triceps - głowa boczna',
                'secondary' => ['Triceps - głowa przyśrodkowa', 'Przedramiona - prostowniki (tył)'],
                'image_prefix' => 'triceps_pressdown',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Wiosłowanie na maszynie w siadzie (Seated Row)',
                'description' => 'Usiądź na siedzisku, opierając stopy na platformach, a klatkę piersiową mocno dociśnij do poduszki oporowej. Chwyć rączki maszyny przy wyprostowanych ramionach. Inicjując ruch z mięśni grzbietu, przyciągnij uchwyty w stronę tułowia, prowadząc łokcie blisko ciała i mocno ściągając łopatki. Następnie powoli, kontrolując ciężar, wróć do pozycji wyjściowej.',
                'tips' => 'Przez całe ćwiczenie utrzymuj klatkę piersiową przyklejoną do poduszki oporowej – unikaj odchylania tułowia do tyłu podczas przyciągania ciężaru. Skup się na pracy łopatek, a nie tylko na zginaniu rąk.',
                'category' => 'Plecy',
                'mechanicType' => 'compound',
                'movementPattern' => 'horizontal_pull',
                'primary' => 'Najszerszy grzbietu',
                'secondary' => ['Górna część pleców', 'Kaptur', 'Biceps', 'Przedramiona - zginacze (przód)'],
                'image_prefix' => 'seated_row',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Wyciskanie na maszynie w siadzie (Vertical Bench Press)',
                'description' => 'Usiądź wygodnie na siedzisku maszyny, opierając plecy i głowę o oparcie. Stopy ustaw płasko na podłożu. Chwyć za rączki na wysokości klatki piersiowej. Kontrolowanym, dynamicznym ruchem wypchnij uchwyty do przodu, prostując ramiona (unikaj blokowania łokci). Następnie powolnym ruchem wróć do pozycji wyjściowej, aż poczujesz lekkie rozciąganie w klatce piersiowej.',
                'tips' => 'Przez cały czas trwania ruchu trzymaj łopatki ściągnięte, a plecy dociśnięte do oparcia. Unikaj wysuwania barków do przodu w końcowej fazie ruchu.',
                'category' => 'Klatka piersiowa',
                'mechanicType' => 'compound',
                'movementPattern' => 'horizontal_push',
                'primary' => 'Klatka piersiowa - środek',
                'secondary' => ['Barki - przód', 'Triceps - głowa boczna'],
                'image_prefix' => 'vertical_bench_press',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Ściąganie drążka wyciągu pionowego (Lat Pulldown)',
                'description' => 'Usiądź na siedzisku maszyny i zablokuj uda pod wałkami oporowymi. Chwyć rączki urządzenia szerokim chwytem. Kontrolowanym, dynamicznym ruchem przyciągnij ramiona maszyny w dół, kierując uchwyty w stronę górnej części klatki piersiowej i mocno ściągając łopatki. Następnie powolnym, płynnym ruchem pozwól ramionom powrócić do pozycji wyjściowej, w pełni rozciągając mięśnie grzbietu.',
                'tips' => 'Podczas ściągania ciężaru utrzymuj lekko odchyloną, ale stabilną sylwetkę i nie szarp tułowiem. Ruch powinien być zapoczątkowany przez opuszczenie łopatek, a nie tylko siłę ramion.',
                'category' => 'Plecy',
                'mechanicType' => 'compound',
                'movementPattern' => 'vertical_pull',
                'primary' => 'Najszerszy grzbietu',
                'secondary' => ['Górna część pleców', 'Biceps', 'Przedramiona - zginacze (przód)'],
                'image_prefix' => 'lat_pulldown_machine',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Wyciskanie na ławce poziomej na maszynie (Supine Bench Press)',
                'description' => 'Połóż się płasko na ławce maszyny, opierając stopy stabilnie na podłodze. Chwyć rączki urządzenia na szerokość klatki piersiowej. Kontrolowanym, dynamicznym ruchem wypchnij ramiona maszyny w górę, prostując ręce, ale unikając blokowania łokci. Następnie powoli opuść ciężar do pozycji wyjściowej, aż poczujesz lekkie rozciąganie w klatce piersiowej.',
                'tips' => 'Przez cały czas trwania ćwiczenia dbaj o to, aby łopatki były ściągnięte, a pośladki i plecy mocno przylegały do ławki. Nie odrywaj stóp od podłoża.',
                'category' => 'Klatka piersiowa',
                'mechanicType' => 'compound',
                'movementPattern' => 'horizontal_push',
                'primary' => 'Klatka piersiowa - środek',
                'secondary' => ['Barki - przód', 'Triceps - głowa boczna'],
                'image_prefix' => 'supine_bench_press_machine',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Skłony tułowia na ławce skośnej (Adjustable Ab Bench)',
                'description' => 'Usiądź na ławce skośnej, zaprzyj stopy pod dolnymi wałkami, a łydki pod górnymi wałkami oporowymi. Skrzyżuj ręce na klatce piersiowej lub trzymaj dłonie przy skroniach. Z pozycji leżącej wykonaj skłon, unosząc tułów w górę poprzez spięcie mięśni brzucha, aż klatka piersiowa zbliży się do kolan. Powoli i pod pełną kontrolą opuść tułów z powrotem do pozycji wyjściowej.',
                'tips' => 'Podczas wykonywania ruchu skup się na rolowaniu kręgosłupa (zaokrąglaniu pleców), a nie na unoszeniu całego, prostego tułowia z bioder. Unikaj ciągnięcia dłońmi za głowę/kark, aby zapobiec kontuzjom odcinka szyjnego.',
                'category' => 'Brzuch',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Prosty brzucha',
                'secondary' => ['Skośne brzucha'],
                'image_prefix' => 'adjustable_ab_bench',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Wyciskanie sztangi nad głowę na ławce pionowej (Shoulder Bench)',
                'description' => 'Usiądź na ławce, opierając plecy stabilnie o oparcie. Chwyć sztangę nachwytem, nieco szerzej niż szerokość barków, i zdejmij ją ze stojaków. Kontrolowanym ruchem opuść sztangę w pionowej linii, aż znajdzie się na wysokości obojczyków lub górnej części klatki piersiowej. Następnie dynamicznym, ale kontrolowanym ruchem wyciśnij sztangę w górę, prostując ramiona nad głową, pamiętając, aby nie blokować łokci.',
                'tips' => 'Przez cały czas trwania ruchu utrzymuj napięty brzuch i dbaj o to, aby odcinek lędźwiowy nie wyginał się nadmiernie w łuk. Stopy powinny mocno i płasko spoczywać na podłożu, zapewniając stabilną podstawę do wyciskania.',
                'category' => 'Barki',
                'mechanicType' => 'compound',
                'movementPattern' => 'overhead_press',
                'primary' => 'Barki - przód',
                'secondary' => ['Triceps - głowa boczna', 'Górna część pleców', 'Kaptur'],
                'image_prefix' => 'shoulder_bench',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Krzyżowe wznosy ramion bokiem na wyciągu dolnym',
                'description' => 'Stań na środku bramy, trzymając stopy na szerokość barków. Chwyć lewą rączkę wyciągu dolnego prawą dłonią, a prawą rączkę lewą dłonią, krzyżując linki przed miednicą. Utrzymując delikatne ugięcie w łokciach, unoś ramiona na boki, aż znajdą się na wysokości barków, prowadząc ruch w płaszczyźnie poziomej. Powróć do pozycji startowej z pełną kontrolą obciążenia.',
                'tips' => 'Inicjuj i prowadź ruch łokciami, unikając szarpania ciężaru. Zachowaj stabilny, nieruchomy tułów i nie używaj nóg do wybijania obciążenia w górę.',
                'category' => 'Barki',
                'mechanicType' => 'isolation',
                'movementPattern' => 'lateral_raises',
                'primary' => 'Barki - bok',
                'secondary' => ['Górna część pleców', 'Kaptur'],
                'image_prefix' => 'cable_crossover_lateral_raise',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Krzyżowanie linek wyciągu górnego',
                'description' => 'Stań centralnie w bramie wyciągu, trzymając rączki górnych wyciągów. Wykonaj wykrok w przód dla stabilizacji i lekko pochyl tułów. Ramiona rozłóż szeroko, zachowując delikatne ugięcie w łokciach (faza początkowa). Z tej pozycji płynnym ruchem ściągnij linki wyciągu do wewnątrz, łącząc dłonie przed klatką piersiową (faza końcowa). Maksymalnie napnij mięśnie, a następnie pod pełną kontrolą powoli wróć do szerokiego rozstawienia ramion.',
                'tips' => 'Utrzymuj stały, niezmienny kąt ugięcia w stawach łokciowych przez cały zakres ruchu, aby wyizolować pracę klatki piersiowej. Ruch powinien przypominać obejmowanie szerokiego drzewa.',
                'category' => 'Klatka piersiowa',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Klatka piersiowa - środek',
                'secondary' => ['Barki - przód'],
                'image_prefix' => 'cable_crossover',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Wyciskanie sztangi nad głowę stojąc',
                'description' => 'Stań w stabilnym, lekkim rozkroku, trzymając sztangę nachwytem nieco szerzej niż szerokość barków. Oprzyj sztangę na wysokości obojczyków, trzymając łokcie skierowane lekko w przód (faza początkowa). Dynamicznym, kontrolowanym ruchem wyciśnij sztangę pionowo nad głowę aż do pełnego wyprostu ramion, delikatnie przesuwając głowę w przód pod sztangę (faza końcowa). Powoli opuść ciężar z powrotem na obojczyki.',
                'tips' => 'Mocno napnij mięśnie pośladkowe oraz prosty brzucha, aby zapobiec niebezpiecznemu wyginaniu odcinka lędźwiowego kręgosłupa w trakcie wyciskania.',
                'category' => 'Barki',
                'mechanicType' => 'compound',
                'movementPattern' => 'overhead_press',
                'primary' => 'Barki - przód',
                'secondary' => ['Triceps - głowa boczna', 'Klatka piersiowa - góra', 'Kaptur'],
                'image_prefix' => 'barbell_overhead_press',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Martwy ciąg klasyczny',
                'description' => 'Stań w lekkim rozkroku, stopy na szerokość bioder, sztanga nad środkiem stóp. Wykonaj opad tułowia z jednoczesnym ugięciem kolan i chwyć gryf nachwytem nieco szerzej niż rozstaw kolan (faza początkowa). Utrzymując proste plecy i napięty brzuch, dynamicznie unieś ciężar, prostując biodra i kolana, aż do pełnego wyprostu sylwetki, prowadząc sztangę blisko ciała (faza końcowa). Kontrolowanym ruchem opuść sztangę na podłoże.',
                'tips' => 'Zacznij ruch od napięcia pośladków i brzucha, a sztangę prowadź jak najbliżej piszczeli i ud. Nie pozwalaj na zaokrąglenie pleców (tzw. „koci grzbiet”) pod obciążeniem.',
                'category' => 'Nogi',
                'mechanicType' => 'compound',
                'movementPattern' => 'hinge',
                'primary' => 'Tył uda (Kulszowo-goleniowe)',
                'secondary' => ['Pośladki', 'Czworogłowy uda', 'Prostownik grzbietu', 'Kaptur', 'Przedramiona - zginacze (przód)'],
                'image_prefix' => 'deadlift',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Wiosłowanie sztangą w opadzie tułowia',
                'description' => 'Stań w rozkroku na szerokość barków, ugnij lekko kolana i pochyl prosty tułów do przodu (prawie równolegle do podłogi). Chwyć sztangę nachwytem nieco szerzej niż szerokość barków, ramiona swobodnie opuszczone w dół (faza początkowa). Dynamicznie przyciągnij sztangę do dolnej części brzucha, prowadząc łokcie blisko ciała i mocno ściągając łopatki (faza końcowa). Powoli i z pełną kontrolą opuść sztangę do pozycji wyjściowej.',
                'tips' => 'Przez cały czas trwania ćwiczenia utrzymuj naturalną krzywiznę kręgosłupa i napięte mięśnie brzucha. Nie szarp ciężaru tułowiem.',
                'category' => 'Plecy',
                'mechanicType' => 'compound',
                'movementPattern' => 'horizontal_pull',
                'primary' => 'Najszerszy grzbietu',
                'secondary' => ['Biceps', 'Górna część pleców', 'Kaptur', 'Przedramiona - zginacze (przód)'],
                'image_prefix' => 'barbell_row',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Uginanie ramion z hantlami na ławce skośnej',
                'description' => 'Usiądź na ławce skośnej z hantlami w dłoniach. Oprzyj plecy płasko na oparciu, a ramiona opuść luźno w dół prostopadle do podłogi. Unieś hantle poprzez zgięcie stawów łokciowych, utrzymując ramiona nieruchomo. Następnie powoli opuść ciężar do pozycji wyjściowej.',
                'tips' => 'Trzymaj łokcie blisko tułowia i pilnuj, aby nie przesuwały się do przodu podczas unoszenia hantli. Skup się na pełnym rozciągnięciu mięśnia w dolnej fazie ruchu.',
                'category' => 'Ramiona',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Biceps',
                'secondary' => ['Przedramiona - zginacze (przód)'],
                'image_prefix' => 'incline_dumbbell_curl',
                'incrementStep' => 1.0
            ],
            [
                'name' => 'Uginanie młotkowe (Hammer Curl)',
                'description' => 'Stań prosto, trzymając hantle wzdłuż tułowia nachwytem neutralnym (kciuki skierowane do góry). Uginaj ramiona w łokciach, unosząc hantle w kierunku barków bez zmiany ustawienia nadgarstków. Następnie powoli opuść ciężar do pozycji początkowej.',
                'tips' => 'Utrzymuj łokcie blisko tułowia i nieruchome barki. Nie kołysz tułowiem ani nie wykorzystuj rozpędu. Zachowaj neutralny chwyt przez cały ruch i kontroluj opuszczanie hantli.',
                'category' => 'Ramiona',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Biceps',
                'secondary' => ['Przedramiona - zginacze (przód)'],
                'image_prefix' => 'hammer_curl',
                'incrementStep' => 1.0
            ],
            [
                'name' => 'Uginanie ramienia w podporze (Concentration Curl)',
                'description' => 'Usiądź na ławce z nogami rozstawionymi na boki. Oprzyj łokieć ćwiczonego ramienia o wewnętrzną stronę uda. Trzymając hantel podchwytem, powoli uginaj ramię, unosząc ciężar w kierunku barku. Następnie kontrolowanym ruchem opuść hantel do pełnego wyprostu ramienia.',
                'tips' => 'Utrzymuj łokieć nieruchomo i stabilnie oparty o udo przez cały ruch. Nie odrywaj ramienia od podpory, nie kołysz tułowiem i unikaj szarpania ciężarem. Utrzymuj nadgarstek w jednej linii z przedramieniem.',
                'category' => 'Ramiona',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Biceps',
                'secondary' => ['Przedramiona - zginacze (przód)'],
                'image_prefix' => 'concentration_curl',
                'incrementStep' => 1.0
            ],
            [
                'name' => 'Wyciskanie hantli zza głowy oburącz',
                'description' => 'Ćwiczenie izolowane angażujące głowę długą mięśnia trójgłowego ramienia. Z pozycji wyjściowej z hantlem trzymanym oburącz za wewnętrzny talerz i uniesionym nad głową, przedramiona są powoli opuszczane za głowę do pełnego rozciągnięcia tricepsa, po czym następuje kontrolowany wyprost w stawach łokciowych.',
                'tips' => 'Utrzymuj łokcie stabilnie, blisko głowy i unikaj ich nadmiernego rozchodzenia się na boki, aby zmaksymalizować pracę tricepsa.',
                'category' => 'Ramiona',
                'mechanicType' => 'isolation',
                'movementPattern' => 'isolation',
                'primary' => 'Triceps - głowa długa',
                'secondary' => ['Barki - przód', 'Przedramiona - prostowniki (tył)'],
                'image_prefix' => 'wyciskanie_hantli_zza_glowy_oburacz',
                'incrementStep' => 2.0
            ],

            [
                'name' => 'Uginanie nadgarstków ze sztangą podchwytem',
                'description' => 'Ćwiczenie izolowane na mięśnie zginacze przedramion. W pozycji siedzącej na ławce całe przedramiona spoczywają stabilnie na udach i kolanach, a dłonie trzymają sztangę podchwytem tuż za linią kolan. W fazie początkowej nadgarstki są maksymalnie wyprostowane w dół, natomiast w fazie końcowej następuje ich kontrolowane zgięcie ku górze wyłącznie pracą stawu nadgarstkowego.',
                'tips' => 'Przez cały czas trwania serii utrzymuj całe przedramiona i łokcie dociśnięte do ud, aby całkowicie wyeliminować pracę ramion i zmusić do pracy wyłącznie nadgarstki.',
                'category' => 'Przedramiona',
                'mechanicType' => 'isolation',
                'primary' => 'Przedramiona - zginacze (przód)',
                'secondary' => [],
                'image_prefix' => 'barbell_wrist_curl_underhand',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Zawijanie linki na wyciągu dolnym',
                'description' => 'Ćwiczenie izolowane na mięśnie przedramion wykonywane w pozycji stojącej przodem do wyciągu dolnego. W fazie początkowej ramiona są wyprostowane przed sobą na wysokości klatki piersiowej, a dłonie trzymają wałek nachwytem przy rozwiniętej lince. W fazie końcowej następuje naprzemienne, kontrolowane nawijanie linki na wałek wyłącznie ruchem nadgarstków, unosząc ciężar stosu w górę.',
                'tips' => 'Utrzymuj ramiona nieruchomo na stałej wysokości przez całą serię i unikaj kołysania tułowiem – cały ruch nawijania musi wynikać wyłącznie z pracy nadgarstków.',
                'category' => 'Przedramiona',
                'mechanicType' => 'isolation',
                'primary' => 'Przedramiona - prostowniki (tył)',
                'secondary' => ['Przedramiona - zginacze (przód)', 'Barki - przód'],
                'image_prefix' => 'low_cable_wrist_roller',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Wyciskanie hantli na ławce skośnej dodatniej',
                'description' => 'Wielostawowe ćwiczenie siłowe ukierunkowane na górną część klatki piersiowej, wykonywane na ławce ustawionej pod kątem dodatnim (30–45°). Ruch polega na kontrolowanym opuszczeniu hantli na wysokość górnej części klatki piersiowej z łokciami prowadzonymi pod kątem ok. 45° względem tułowia, a następnie dynamicznym wyciśnięciu ciężaru pionowo w górę do pełnego wyprostu ramion.',
                'tips' => 'Utrzymuj ściągnięte i opuszczone łopatki przez cały czas trwania serii, a przedramiona prowadź prostopadle do podłoża, aby odciążyć stawy barkowe.',
                'category' => 'Klatka piersiowa',
                'mechanicType' => 'compound',
                'primary' => 'Klatka piersiowa - góra',
                'secondary' => ['Barki - przód', 'Triceps - głowa boczna', 'Triceps - głowa przyśrodkowa'],
                'image_prefix' => 'wyciskanie_hantli_na_lawce_skosnej_dodatniej',
                'incrementStep' => 2.0
            ],
            [
                'name' => 'Wznosy ramienia bokiem na wyciągu dolnym (Cable Lateral Raise)',
                'description' => 'Izolowane ćwiczenie ukierunkowane na boczny akton mięśnia naramiennego, zapewniające stałe napięcie mięśniowe w całym zakresie ruchu. Ruch rozpoczyna się z pozycji stojącej bokiem do wyciągu dolnego z ręką opuszczoną przed biodrem i napiętą linką, a kończy płynnym odwiedzeniem lekko ugiętego ramienia w bok do wysokości barku.',
                'tips' => 'Prowadź ruch łokciem, a nie dłonią, i utrzymuj stabilny tułów bez kołysania, aby uniknąć przejmowania pracy przez mięśnie czworoboczne.',
                'category' => 'Barki',
                'mechanicType' => 'isolation',
                'primary' => 'Barki - bok',
                'secondary' => ['Kaptur', 'Barki - przód'],
                'image_prefix' => 'cable_lateral_raise',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Face Pull (Przyciąganie liny do twarzy)',
                'description' => 'Wielostawowe ćwiczenie na wyciągu ukierunkowane na tylny akton mięśni naramiennych, mięśnie górnej części pleców oraz rotatory zewnętrzne barku. Ruch rozpoczyna się od pozycji stojącej przodem do wyciągu z ramionami wyprostowanymi w przód i napiętą liną na wysokości twarzy, a kończy przyciągnięciem uchwytu w kierunku czoła z jednoczesnym rozciągnięciem końców liny na boki i rotacją zewnętrzną ramion.',
                'tips' => 'Prowadź łokcie wysoko na linii barków i w końcowej fazie ruchu kieruj kciuki w tył, aby zmaksymalizować rotację zewnętrzną i pracę tylnego aktonu barku.',
                'category' => 'Barki',
                'mechanicType' => 'compound',
                'primary' => 'Barki - tył',
                'secondary' => ['Górna część pleców', 'Kaptur', 'Barki - bok'],
                'image_prefix' => 'face_pull',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Rumuński martwy ciąg ze sztangą (RDL)',
                'description' => 'Wielostawowe ćwiczenie oparte na wzorcu zawiasu biodrowego (hip hinge), silnie angażujące tylną taśmę mięśniową. Ruch rozpoczyna się z pozycji wyprostowanej ze sztangą trzymaną nachwytem przed udami, a następnie polega na kontrolowanym wypychaniu bioder w tył przy zachowaniu prostych pleców i lekko ugiętych kolan aż do opuszczenia gryfu na wysokość połowy piszczeli, po czym następuje dynamiczny powrót do wyprostu przez spięcie pośladków.',
                'tips' => 'Prowadź sztangę jak najbliżej ud i piszczeli przez cały czas trwania ruchu oraz utrzymuj neutralny kręgosłup, kończąc opuszczanie w momencie, gdy biodra przestają cofać się w tył.',
                'category' => 'Nogi',
                'mechanicType' => 'compound',
                'primary' => 'Tył uda (Kulszowo-goleniowe)',
                'secondary' => ['Pośladki', 'Prostownik grzbietu', 'Przedramiona - zginacze (przód)'],
                'image_prefix' => 'rumunski_martwy_ciag_rdl',
                'incrementStep' => 2.5
            ],
            
            [
                'name' => 'Wspięcia na palcach na maszynie Smitha',
                'description' => 'Izolowane ćwiczenie angażujące mięśnie łydek poprzez dynamiczne zgięcie podeszwowe stawów skokowych przy wyprostowanych kolanach i ustabilizowanej sylwetce. Sztanga suwnicy Smitha spoczywa na górnej części mięśni czworobocznych, umożliwiając kontrolowany ruch wspięcia na samych palcach z pełnym napięciem w szczytowej fazie oraz powolnym opuszczaniem pięt do pozycji wyjściowej.',
                'tips' => 'Utrzymuj wyprostowane kolana przez cały czas trwania ruchu i zatrzymaj wspięcie na sekundę w najwyższym punkcie, aby zmaksymalizować napięcie mięśnia brzuchatego łydki.',
                'category' => 'Nogi',
                'mechanicType' => 'isolation',
                'primary' => 'Łydki',
                'secondary' => [],
                'image_prefix' => 'smith_machine_standing_calf_raise',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Wznosy hantli bokiem',
                'description' => 'Ćwiczenie izolowane, w którym ruch odbywa się głównie w stawie ramiennym. Początkowa faza to pozycja stojąca lub siedząca z hantlami przy udach. Faza końcowa to uniesienie wyprostowanych lub lekko ugiętych ramion bokiem do poziomu barków, gdzie następuje maksymalne napięcie mięśni naramiennych.',
                'tips' => 'Unikaj zarzucania tułowiem i używania zbyt dużego ciężaru; skup się na kontrolowanym ruchu i prowadzeniu łokciami, a nie dłońmi.',
                'category' => 'Barki',
                'mechanicType' => 'isolation',
                'primary' => 'Barki - bok',
                'secondary' => ['Barki - przód', 'Górna część pleców'],
                'image_prefix' => 'lateral_dumbbell_raise',
                'incrementStep' => 1.0
            ],
            [
                'name' => 'Naprzemienne wznosy hantli przód chwytem młotkowym',
                'description' => 'Ćwiczenie izolowane ukierunkowane na przedni akton mięśni naramiennych. Ruch rozpoczyna się z pozycji stojącej z ramionami opuszczonymi wzdłuż tułowia i hantlami trzymanymi chwytem neutralnym (młotkowym), po czym następuje naprzemienne zgięcie w stawie ramiennym i uniesienie jednego ramienia w przód do wysokości barków przy zachowaniu stabilnego tułowia.',
                'tips' => 'Utrzymuj lekko ugięte łokcie i napięty brzuch, aby uniknąć bujania tułowiem oraz przejmowania pracy przez odcinek lędźwiowy kręgosłupa.',
                'category' => 'Barki',
                'mechanicType' => 'isolation',
                'primary' => 'Barki - przód',
                'secondary' => ['Barki - bok', 'Klatka piersiowa - góra', 'Przedramiona - zginacze (przód)'],
                'image_prefix' => 'alternating_dumbbell_hammer_front_raise',
                'incrementStep' => 1.0
            ],
            [
                'name' => 'Uginanie przedramion ze sztangą',
                'description' => 'Podstawowe ćwiczenie izolowane rozwijające mięśnie dwugłowe ramienia. Ruch rozpoczyna się z pozycji stojącej z ramionami wyprostowanymi wzdłuż tułowia i sztangą trzymaną podchwytem na szerokość barków, po czym następuje koncentryczne zgięcie w stawach łokciowych i uniesienie gryfu do wysokości górnej części klatki piersiowej przy nieruchomych łokciach.',
                'tips' => 'Utrzymuj łokcie stabilnie przy tułowiu przez cały zakres ruchu i nie kołysz ciałem, aby zmaksymalizować napięcie mięśni dwugłowych ramienia.',
                'category' => 'Biceps',
                'mechanicType' => 'isolation',
                'primary' => 'Biceps',
                'secondary' => ['Przedramiona - zginacze (przód)', 'Barki - przód'],
                'image_prefix' => 'barbell_biceps_curl',
                'incrementStep' => 2.5
            ],
            [
                'name' => 'Wiosłowanie szerokim chwytem na wyciągu dolnym',
                'description' => 'Wielostawowe ćwiczenie na wyciągu dolnym angażujące mięśnie grzbietu i tylne aktony barków. Ruch rozpoczyna się w pozycji siedzącej z nogami opartymi o platformy, stabilnym tułowiem i ramionami wyprostowanymi w przód trzymającymi szeroki drążek, po czym następuje ściągnięcie drążka w kierunku dolnej klatki piersiowej z retrakcją łopatek.',
                'tips' => 'Utrzymuj stabilny tułów bez nadmiernego pochylania w przód i w tył oraz skup się na ściągnięciu łopatek w końcowej fazie ruchu.',
                'category' => 'Plecy',
                'mechanicType' => 'compound',
                'primary' => 'Górna część pleców',
                'secondary' => ['Najszerszy grzbietu', 'Barki - tył', 'Biceps'],
                'image_prefix' => 'wide_grip_seated_low_cable_row',
                'incrementStep' => 5.0
            ],
            [
                'name' => 'Odwodzenie nóg na maszynie siedząc',
                'description' => 'Ćwiczenie izolowane na maszynie ze stosem angażujące mięśnie odwodzące staw biodrowy. Ruch rozpoczyna się w pozycji siedzącej z plecami opartymi o oparcie i udami złączonymi przy wewnętrznych poduszkach, po czym następuje odwiedzenie obu nóg na zewnątrz z pokonaniem oporu stosu przy zachowaniu stabilnego tułowia.',
                'tips' => 'Utrzymuj plecy przylegające do oparcia i kontroluj fazę powrotu, nie pozwalając na gwałtowne złączenie ud.',
                'category' => 'Nogi',
                'mechanicType' => 'isolation',
                'primary' => 'Odwodziciele',
                'secondary' => ['Pośladki'],
                'image_prefix' => 'seated_hip_abduction_machine',
                'incrementStep' => 5.0
            ],
            // --- NOWE ---//
            
            // --- MASZYNY CARDIO ---//
            [
                'name' => 'Bieżnia Elektryczna',
                'description' => 'Trening biegowy na bieżni elektrycznej. Świetny sposób na poprawę wydolności tlenowej i spalanie kalorii. Utrzymuj wyprostowaną sylwetkę i patrz przed siebie.',
                'tips' => 'Zacznij od kilkuminutowego marszu, by rozgrzać stawy. Jeśli czujesz zmęczenie, zmniejsz prędkość lub zwiększ kąt nachylenia zamiast trzymać się poręczy.',
                'category' => 'Cardio',
                'mechanicType' => 'cardio',
                'movementPattern' => 'other',
                'primary' => 'Łydki',
                'secondary' => ['Czworogłowy uda', 'Tył uda (Kulszowo-goleniowe)', 'Pośladki'],
                'image_prefix' => 'treadmill'
            ],
            [
                'name' => 'Rowerek Stacjonarny',
                'description' => 'Trening wydolnościowy na rowerze stacjonarnym. Niskie obciążenie dla stawów kolanowych. Dostosuj wysokość siodełka tak, aby przy maksymalnym wyproście noga była lekko ugięta w kolanie.',
                'tips' => 'Staraj się pedałować okrężnie, "ciągnąc" pedały w górę (jeśli masz noski lub system SPD), a nie tylko wciskając je w dół.',
                'category' => 'Cardio',
                'mechanicType' => 'cardio',
                'movementPattern' => 'other',
                'primary' => 'Czworogłowy uda',
                'secondary' => ['Łydki', 'Pośladki'],
                'image_prefix' => 'stationary_bike'
            ],
            [
                'name' => 'Orbitrek (Elliptical)',
                'description' => 'Trening cross-trainer angażujący całe ciało w ruchu eliptycznym. Płynny ruch chroni stawy, jednocześnie budując kondycję i angażując ramiona oraz plecy do pracy.',
                'tips' => 'Utrzymuj stabilny tułów. Aktywnie używaj ramion do popychania i ciągnięcia uchwytów, aby zmaksymalizować spalanie kalorii.',
                'category' => 'Cardio',
                'mechanicType' => 'cardio',
                'movementPattern' => 'other',
                'primary' => 'Pośladki',
                'secondary' => ['Czworogłowy uda', 'Najszerszy grzbietu', 'Barki - przód'],
                'image_prefix' => 'elliptical'
            ],
        ];

        // --- PĘTLA GENERUJĄCA MASZYNY ---
        $machineRepo = $manager->getRepository(Machine::class);

        foreach ($machinesList as $data) {
            // Jeśli maszyna już istnieje w bazie — pomijamy, nie nadpisujemy
            if ($machineRepo->findOneBy(['name' => $data['name']])) {
                continue;
            }

            $machine = new Machine();

            $prefix = $data['image_prefix'] ?? 'default_machine';
            $imageStart = $prefix . '_start.jpg';
            $imageEnd = $prefix . '_end.jpg';

            $machine->setName($data['name'])
                ->setDescription($data['description'] ?? '')
                ->setTips($data['tips'] ?? '')
                ->setImageStart($imageStart)
                ->setImageEnd($imageEnd)
                ->setCategory($data['category'] ?? 'Maszyny')
                ->setMechanicType($data['mechanicType'])
                ->setMovementPattern($data['movementPattern'] ?? 'other');

            if (isset($data['incrementStep'])) {
                $machine->setIncrementStep($data['incrementStep']);
            } else {
                $machine->setIncrementStep(2.5);
            }

            if (isset($data['primary']) && isset($muscleEntities[$data['primary']])) {
                $machine->addPrimaryMuscle($muscleEntities[$data['primary']]);
            }

            if (isset($data['secondary'])) {
                foreach ($data['secondary'] as $secMuscle) {
                    if (isset($muscleEntities[$secMuscle])) {
                        $machine->addSecondaryMuscle($muscleEntities[$secMuscle]);
                    }
                }
            }

            $manager->persist($machine);
        }

        $manager->flush();
    }
}
