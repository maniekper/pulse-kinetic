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
        // 1. MIĘŚNIE - Słownik w 100% zsynchronizowany z interaktywnym SVG
        $musclesData = [
            'Klatka piersiowa', 
            'Barki', 
            'Biceps', 
            'Triceps', 
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
            'Łydki',
            // Główne grupy kategoryzacyjne
            'Plecy', 'Nogi', 'Brzuch'
        ];
        
        $muscleEntities = [];
        
        foreach ($musclesData as $name) {
            $muscle = new Muscle();
            $muscle->setName($name);
            $manager->persist($muscle);
            $muscleEntities[$name] = $muscle;
        }

        // --- 4. MASZYNY I ĆWICZENIA (Baza Wiedzy) ---
        $machinesList = [
            [
                'name' => 'Fly',
                'description' => 'Usiądź na maszynie i oprzyj plecy płasko o oparcie. Chwyć uchwyty z ramionami rozłożonymi na boki, zachowując lekko ugięte łokcie. Płynnym ruchem przyciągnij uchwyty do siebie przed klatkę piersiową, mocno napinając mięśnie. Powoli i pod pełną kontrolą wróć do pozycji wyjściowej.',
                'tips' => 'Przez cały czas trwania ruchu utrzymuj klatkę piersiową wypchniętą do przodu, a łopatki ściągnięte i przylegające do oparcia. Unikaj całkowitego prostowania rąk w stawach łokciowych.',
                'category' => 'Klatka piersiowa',
                'mechanicType' => 'isolation',
                'primary' => 'Klatka piersiowa', 
                'secondary' => ['Barki'], 
                'image_prefix' => 'fly'
            ],
            [
                'name' => 'Lat Pulldown',
                'description' => 'Ściąganie drążka wyciągu górnego na maszynie w siadzie. Ćwiczenie budujące szerokość i grubość mięśni grzbietu, zapewniające stabilną i kontrolowaną trajektorię ruchu.',
                'tips' => 'Prowadź ruch łokciami w dół i nieznacznie do przodu, ściągając łopatki. Unikaj nadmiernego odchylania tułowia w tył.',
                'category' => 'Plecy',
                'mechanicType' => 'compound',
                'primary' => 'Najszerszy grzbietu',
                'secondary' => ['Biceps', 'Czworoboczny grzbietu'], 
                'image_prefix' => 'lat_pulldown'
            ],
            [
                'name' => 'Rear Delt',
                'description' => 'Usiądź przodem do maszyny, opierając klatkę piersiową stabilnie o poduszkę. Chwyć uchwyty przed sobą, zachowując ramiona delikatnie ugięte w łokciach (pozycja startowa). Płynnym ruchem odwiedź ramiona na zewnątrz i w tył po szerokim łuku, aż dłonie znajdą się na wysokości barków (pozycja końcowa). Kontrolując ciężar, powoli wróć do pozycji wyjściowej.',
                'tips' => 'Utrzymuj stały, delikatny kąt zgięcia w łokciach. Nie używaj pędu tułowia, praca ma wychodzić wyłącznie z tylnego aktonu barków.',
                'category' => 'Barki',
                'mechanicType' => 'isolation',
                'primary' => 'Barki', 
                'secondary' => ['Czworoboczny grzbietu'],
                'image_prefix' => 'rear_delt'
            ],
            [
                'name' => 'Leg Press',
                'description' => 'Usiądź na maszynie, opierając plecy i głowę o oparcie. Umieść stopy płasko na platformie na szerokość barków. Złap za boczne uchwyty, aby ustabilizować tułów. Płynnym ruchem wypchnij platformę, prostując nogi (nie blokuj kolan), a następnie powoli, kontrolując ciężar, ugnij nogi wracając do pozycji startowej z kolanami blisko tułowia.',
                'tips' => 'Zawsze utrzymuj całe stopy na platformie (nie odrywaj pięt) i pilnuj, aby dolny odcinek pleców stale przylegał do oparcia.',
                'category' => 'Nogi',
                'mechanicType' => 'compound',
                'primary' => 'Czworogłowy uda',
                'secondary' => ['Pośladki', 'Łydki'],
                'image_prefix' => 'leg_press'
            ],
            [
                'name' => 'Uginanie nóg leżąc na maszynie',
                'description' => 'Połóż się przodem na maszynie i zaprzyj dolną część łydek pod wałkiem. Płynnym ruchem ugnij nogi w kolanach, przyciągając wałek w stronę pośladków, a następnie w kontrolowany sposób wróć do pozycji wyjściowej.',
                'tips' => 'Utrzymuj miednicę mocno dociśniętą do ławki przez cały zakres ruchu. Zapobiegnie to unoszeniu bioder i przenoszeniu napięcia na odcinek lędźwiowy, maksymalizując izolację dwugłowych uda.',
                'category' => 'Nogi',
                'mechanicType' => 'isolation',
                'primary' => 'Dwugłowy uda',
                'secondary' => ['Łydki', 'Pośladki'],
                'image_prefix' => 'prone_leg_curl'
            ],
            [
                'name' => 'Wyciskanie nad głowę na maszynie siedząc (zbieżne)',
                'description' => 'Usiądź stabilnie na maszynie, opierając plecy o oparcie. Chwyć uchwyty na wysokości barków. Wypchnij ciężar płynnie w górę, prostując ramiona, aż uchwyty zbiegną się nad głową. Następnie w kontrolowany sposób opuść ramiona do pozycji wyjściowej.',
                'tips' => 'Utrzymuj stabilną pozycję tułowia, unikając wyginania odcinka lędźwiowego. Łokcie prowadź lekko przed linią tułowia, aby zoptymalizować pracę mięśni naramiennych i chronić stawy.',
                'category' => 'Barki',
                'mechanicType' => 'compound',
                'primary' => 'Barki',
                'secondary' => ['Triceps', 'Klatka piersiowa'],
                'image_prefix' => 'converging_shoulder_press'
            ],
            [
    'name' => 'Maszyna na przywodziciele (Adductor Machine In)',
    'description' => 'Usiądź na maszynie, stabilnie opierając plecy o oparcie. Umieść wewnętrzną część ud na poduszkach oporowych, a stopy na przeznaczonych do tego podpórkach. Płynnym ruchem przyciągnij uda do siebie do środka, pokonując opór maszyny, po czym powoli wróć do szerokiego ustawienia wyjściowego.',
    'tips' => 'Ruch powrotny (ekscentryczny) wykonuj powoli i pod pełną kontrolą. Nie pozwól, aby ciężar gwałtownie opadł, utrzymując ciągłe napięcie mięśni.',
    'category' => 'Nogi',
    'mechanicType' => 'isolation',
    'primary' => 'Przywodziciele',
    'secondary' => [],
    'image_prefix' => 'adductor_machine_in'
],[
    'name' => 'Podciąganie z asystą (Chin Assist)',
    'description' => 'Uklęknij na platformie maszyny, chwytając górne drążki nachwytem. Utrzymując proste plecy, podciągnij się siłą mięśni grzbietu, aż broda znajdzie się na wysokości uchwytów, a następnie powoli opuść ciało do pełnego wyprostu ramion.',
    'tips' => 'Kontroluj ruch opuszczania i nie pozwól, aby platforma gwałtownie opadła. Skup się na pracy łopatek, ściągając je w dół i do siebie podczas podciągania.',
    'category' => 'Plecy',
    'mechanicType' => 'compound',
    'primary' => 'Najszerszy grzbietu',
    'secondary' => ['Biceps', 'Czworoboczny grzbietu', 'Przedramiona', 'Prosty brzucha'],
    'image_prefix' => 'chin_assist'
],[
    'name' => 'Dipy z asystą (Dip Assist)',
    'description' => 'Uklęknij na ruchomej platformie maszyny, chwytając dolne poręcze neutralnym chwytem. Przyjmij pozycję wyjściową z ramionami w pełni wyprostowanymi, a następnie powoli i kontrolowanie opuszczaj ciało, uginając łokcie do kąta około 90 stopni, po czym dynamicznym ruchem wyciśnij się w górę, prostując ramiona.',
    'tips' => 'Prowadź łokcie blisko tułowia, aby mocniej zaangażować triceps i chronić stawy barkowe. Unikaj pełnego blokowania łokci w najwyższym punkcie, utrzymując stałe napięcie mięśniowe.',
    'category' => 'Klatka piersiowa',
    'mechanicType' => 'compound',
    'primary' => 'Triceps',
    'secondary' => ['Klatka piersiowa', 'Barki', 'Prosty brzucha'],
    'image_prefix' => 'dip_assist'
],[
    'name' => 'Go Series Abdominal',
    'description' => 'Usiądź na siedzisku maszyny i wsuń stopy pod dolne wałki oporowe. Złap dłońmi górne uchwyty. Wykonaj wydech i płynne spięcie mięśni brzucha, przyciągając klatkę piersiową do miednicy, pociągając za sobą ruchome ramię maszyny. Powoli i pod pełną kontrolą wróć do pozycji wyjściowej, wykonując wdech.',
    'tips' => 'Ruch powinien być inicjowany i kontrolowany wyłącznie z mięśni brzucha. Unikaj ciągnięcia ciężaru samymi ramionami. Utrzymuj stałe napięcie mięśniowe przez całą fazę ekscentryczną.',
    'category' => 'Machine',
    'mechanicType' => 'isolation',
    'primary' => 'Prosty brzucha',
    'secondary' => ['Skośne brzucha'],
    'image_prefix' => 'go_series_abdominal'
],[
    'name' => 'Wyciskanie na maszynie siedząc Chest Press',
    'description' => 'Usiądź stabilnie, dociskając plecy do oparcia maszyny. Chwyć uchwyty na szerokości barków na wysokości klatki piersiowej. Wypchnij ciężar płynnym ruchem przed siebie, aż do wyprostu ramion, po czym powolnym i kontrolowanym ruchem opuść ciężar z powrotem do pozycji wyjściowej.',
    'tips' => 'Utrzymuj klatkę piersiową wypchniętą do przodu i łopatki ściągnięte podczas całego ruchu. Unikaj blokowania łokci w fazie końcowej, aby zachować ciągłe napięcie mięśni.',
    'category' => 'Maszyny',
    'mechanicType' => 'compound',
    'primary' => 'Klatka piersiowa',
    'secondary' => ['Barki', 'Triceps'],
    'image_prefix' => 'chest_press_machine'
],
[
    'name' => 'Prostowanie nóg na maszynie siedząc Leg Extension',
    'description' => 'Usiądź na maszynie, dociskając plecy do oparcia. Umieść dół goleni za wałkiem oporowym, a dłonie na bocznych uchwytach. Płynnym ruchem wyprostuj nogi w stawach kolanowych, unosząc wałek do góry, a następnie w kontrolowany sposób opuść ciężar powracając do pozycji wyjściowej.',
    'tips' => 'Utrzymuj stabilny tułów i nie odrywaj pośladków od siedziska. Skup się na pełnej kontroli ciężaru w fazie ekscentrycznej (opuszczania), unikając swobodnego opadania ramienia maszyny.',
    'category' => 'Maszyny',
    'mechanicType' => 'isolation',
    'primary' => 'Czworogłowy uda',
    'secondary' => [],
    'image_prefix' => 'leg_extension_machine'
],[
    'name' => 'Uginanie ramion na modlitewniku',
    'description' => 'Usiądź na ławce, opierając klatkę piersiową i tył ramion (tricepsy) stabilnie na poduszce modlitewnika. Chwyć sztangę podchwytem. Płynnym ruchem ugnij przedramiona, unosząc ciężar w stronę barków, a następnie w kontrolowany sposób opuść sztangę aż do pełnego wyprostu ramion.',
    'tips' => 'Podczas całego ruchu nie odrywaj łokci ani ramion od poduszki. Ruch powinien izolować biceps – unikaj pomagania sobie balansem tułowia.',
    'category' => 'Wolne ciężary',
    'mechanicType' => 'isolation',
    'primary' => 'Biceps',
    'secondary' => ['Przedramiona'],
    'image_prefix' => 'arm_curl_preacher'
],
[
    'name' => 'Wyciskanie sztangi na ławce poziomej',
    'description' => 'Połóż się płasko na ławce, opierając stopy stabilnie na podłożu lub podestach. Chwyć sztangę nachwytem nieco szerzej niż rozstaw barków. Zdejmij ciężar ze stojaków. Kontrolując ruch, powoli opuść sztangę do środkowej części klatki piersiowej, a następnie dynamicznie wyciśnij ją w górę aż do pełnego wyprostu ramion.',
    'tips' => 'Zadbaj o stabilną pozycję poprzez ściągnięcie łopatek do siebie i w dół (retrakcja i depresja). Nie odrywaj pośladków od ławki i unikaj sprężynującego odbijania sztangi od klatki piersiowej w dolnej fazie ruchu.',
    'category' => 'Wolne ciężary',
    'mechanicType' => 'compound',
    'primary' => 'Klatka piersiowa',
    'secondary' => ['Barki', 'Triceps'],
    'image_prefix' => 'flat_bench_press'
],[
    'name' => 'Wyciskanie sztangi na ławce skośnej dodatniej',
    'description' => 'Połóż się na ławce skośnej (około 30-45 stopni), opierając stopy stabilnie na podłożu. Chwyć sztangę nachwytem nieco szerzej niż rozstaw barków. Zdejmij ciężar ze stojaków i trzymaj go nad górną częścią klatki piersiowej. Kontrolując ruch, powoli opuść sztangę w okolice obojczyków (górna część klatki), a następnie dynamicznie wyciśnij ją w górę, prostując ramiona.',
    'tips' => 'Zadbaj o mocne ściągnięcie łopatek do tyłu i w dół przez cały czas trwania ćwiczenia. Unikaj odbijania gryfu od klatki piersiowej i dbaj o to, by łokcie nie rozchodziły się nadmiernie w stronę głowy – powinny być prowadzone lekko do wewnątrz.',
    'category' => 'Wolne ciężary',
    'mechanicType' => 'compound',
    'primary' => 'Klatka piersiowa',
    'secondary' => ['Barki', 'Triceps'],
    'image_prefix' => 'incline_bench_press'
],[
    'name' => 'Wyciskanie sztangi na ławce skośnej ujemnej',
    'description' => 'Zajmij pozycję na ławce skośnej ujemnej, blokując stopy i podudzia za wałkami oporowymi. Połóż się, chwyć sztangę nachwytem nieco szerzej niż rozstaw barków i zdejmij ją ze stojaków. Powoli, kontrolowanym ruchem opuść sztangę do dolnej części klatki piersiowej, a następnie dynamicznie wyciśnij ją w górę do pełnego wyprostu ramion.',
    'tips' => 'Pamiętaj o ciągłym ściągnięciu łopatek i nie odrywaj bioder ani pleców od ławki. Wyciskaj ciężar w naturalnej, lekko łukowatej płaszczyźnie, nie blokując gwałtownie łokci w najwyższym punkcie.',
    'category' => 'Wolne ciężary',
    'mechanicType' => 'compound',
    'primary' => 'Klatka piersiowa',
    'secondary' => ['Barki', 'Triceps'],
    'image_prefix' => 'decline_bench_press'
],[
    'name' => 'Wykop na maszynie do pośladków',
    'description' => 'Zajmij pozycję na maszynie, opierając przedramiona na poduszkach i chwytając za uchwyty. Umieść stopę jednej nogi na ruchomej platformie/ramieniu wahadłowym, a nogę podporową postaw stabilnie na dolnej platformie. Oprzyj biodra o poduszkę przednią. Płynnym ruchem wypchnij stopę w tył i lekko w górę, prostując nogę, a następnie w kontrolowany sposób powróć do pozycji wyjściowej.',
    'tips' => 'Ruch powinien być inicjowany wyłącznie przez pośladek i dwugłowy uda – unikaj rotacji w biodrach oraz nadmiernego wyginania dolnego odcinka pleców (odcinka lędźwiowego). Skup się na pełnym napięciu mięśnia w fazie maksymalnego wyprostu.',
    'category' => 'Maszyny',
    'mechanicType' => 'isolation',
    'primary' => 'Pośladki',
    'secondary' => [],
    'image_prefix' => 'glute_machine'
],[
    'name' => 'Incline Lever Row',
    'description' => 'Oprzyj klatkę piersiową stabilnie o skośną poduszkę, a stopy zaprzyj o dolne platformy. Chwyć uchwyty i płynnym ruchem przyciągnij je w stronę tułowia, prowadząc łokcie blisko ciała i mocno ściągając łopatki. Wróć do pozycji wyjściowej, w pełni kontrolując opuszczanie ciężaru.',
    'tips' => 'Przez cały czas trwania ruchu utrzymuj klatkę piersiową przyklejoną do oparcia. Inicjuj pracę z mięśni grzbietu, zapobiegając szarpaniu ciężarem.',
    'category' => 'Plecy',
    'mechanicType' => 'compound',
    'primary' => 'Najszerszy grzbietu',
    'secondary' => ['Czworoboczny grzbietu', 'Biceps', 'Barki'],
    'image_prefix' => 'incline_lever_row'
],[
    'name' => 'Wypychanie nóg na suwnicy pod kątem 45 stopni',
    'description' => 'Usiądź na maszynie, opierając plecy i pośladki o oparcie. Stopy ułóż na platformie na szerokość barków. Odblokuj ciężar i powoli opuszczaj platformę, zginając kolana do kąta około 90 stopni. Następnie kontrolowanym ruchem wypchnij ciężar do pozycji wyjściowej, nie blokując całkowicie kolan.',
    'tips' => 'Przez cały czas trwania ruchu pilnuj, aby dolny odcinek pleców mocno przylegał do oparcia. Upewnij się również, że pięty nie odrywają się od platformy podczas opuszczania ciężaru.',
    'category' => 'Nogi',
    'mechanicType' => 'compound',
    'primary' => 'Czworogłowy uda',
    'secondary' => ['Pośladki', 'Dwugłowy uda', 'Łydki'],
    'image_prefix' => '45_degree_leg_press'
],[
    'name' => 'Hack Przysiad na maszynie (Hack Squat)',
    'description' => 'Oprzyj plecy o oparcie maszyny, a barki umieść pod poduszkami oporowymi. Ustaw stopy na platformie na szerokość barków, lekko skierowane na zewnątrz. Odblokuj zabezpieczenia i kontrolowanym ruchem obniżaj pozycję, zginając kolana, aż uda znajdą się co najmniej równolegle do platformy. Następnie, odpychając się całą powierzchnią stóp, dynamicznie wróć do pozycji wyjściowej.',
    'tips' => 'Przez cały czas trwania ruchu utrzymuj całe plecy płasko oparte o oparcie maszyny, nie odrywając bioder ani lędźwi. Kontroluj, aby kolana nie schodziły się do środka ani nie odrywały się pięty podczas opuszczania ciężaru.',
    'category' => 'Nogi',
    'mechanicType' => 'compound',
    'primary' => 'Czworogłowy uda',
    'secondary' => ['Pośladki', 'Dwugłowy uda', 'Łydki'],
    'image_prefix' => 'hack_squat'
],[
    'name' => 'Wiosłowanie na wyciągu dolnym w siadzie',
    'description' => 'Usiądź na siedzisku i oprzyj stopy o platformę z lekko ugiętymi kolanami. Chwyć rączkę wyciągu (np. uchwyt V) i wyprostuj plecy. Z pozycji z wyprostowanymi rękami przyciągnij uchwyt do brzucha, prowadząc łokcie blisko tułowia i ściągając łopatki. Wróć do pozycji wyjściowej powolnym, kontrolowanym ruchem.',
    'tips' => 'Trzymaj klatkę piersiową wypchniętą do przodu i nie garb się. Unikaj nadmiernego bujania tułowiem; ruch powinien wychodzić głównie z pracy mięśni grzbietu i rąk.',
    'category' => 'Plecy',
    'mechanicType' => 'compound',
    'primary' => 'Najszerszy grzbietu',
    'secondary' => ['Czworoboczny grzbietu', 'Biceps', 'Przedramiona'],
    'image_prefix' => 'low_row'
],[
    'name' => 'Wyciskanie na ławce poziomej na maszynie (Supine Bench Press)',
    'description' => 'Połóż się płasko na ławce maszyny, opierając stopy stabilnie na podłodze. Chwyć rączki urządzenia na szerokość klatki piersiowej. Kontrolowanym, dynamicznym ruchem wypchnij ramiona maszyny w górę, prostując ręce, ale unikając blokowania łokci. Następnie powoli opuść ciężar do pozycji wyjściowej, aż poczujesz lekkie rozciąganie w klatce piersiowej.',
    'tips' => 'Przez cały czas trwania ćwiczenia dbaj o to, aby łopatki były ściągnięte, a pośladki i plecy mocno przylegały do ławki. Nie odrywaj stóp od podłoża.',
    'category' => 'Klatka piersiowa',
    'mechanicType' => 'compound',
    'primary' => 'Klatka piersiowa',
    'secondary' => ['Barki', 'Triceps'],
    'image_prefix' => 'supine_bench_press'
],[
    'name' => 'Prostowanie ramion na wyciągu (Triceps Pressdown)',
    'description' => 'Stań przodem do wyciągu górnego, chwytając rączkę (np. uchwyt V) nachwytem. Oprzyj się stabilnie o poduszkę maszyny. Zablokuj łokcie blisko tułowia – ugnij je tak, aby przedramiona znalazły się nieco powyżej linii równoległej do podłogi. Z tej pozycji, używając wyłącznie siły tricepsów, wyprostuj ramiona w dół, aż rączka dotknie okolic ud. Powoli i w pełni kontrolowanie wróć do pozycji wyjściowej.',
    'tips' => 'To ćwiczenie izolowane – ruch musi odbywać się wyłącznie w stawie łokciowym. Upewnij się, że łokcie nie uciekają na boki ani nie wędrują do przodu i do tyłu podczas wykonywania powtórzenia.',
    'category' => 'Ramiona',
    'mechanicType' => 'isolation',
    'primary' => 'Triceps',
    'secondary' => ['Przedramiona'],
    'image_prefix' => 'triceps_pressdown'
],
        ];

        // --- PĘTLA GENERUJĄCA MASZYNY ---
        foreach ($machinesList as $data) {
            $machine = new Machine();
            
            // Automatyczne generowanie nazw plików
            $prefix = $data['image_prefix'] ?? 'default_machine';
            $imageStart = $prefix . '_start.jpg';
            $imageEnd = $prefix . '_end.jpg';

            $machine->setName($data['name'])
                ->setDescription($data['description'] ?? '')
                ->setTips($data['tips'] ?? '')
                ->setImageStart($imageStart)
                ->setImageEnd($imageEnd)
                ->setCategory($data['category'] ?? 'Maszyny')
                ->setMechanicType($data['mechanicType']);
            
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