<?php

namespace App\Service;

use App\Entity\MachineRecord;
use App\Entity\WorkoutSet;
use Doctrine\ORM\EntityManagerInterface;

class RecordTrackerService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function checkAndSaveRecord(WorkoutSet $set): ?MachineRecord
    {
        $user = $set->getWorkoutSession()->getUser();
        $machine = $set->getMachine();
        
        $recordRepo = $this->em->getRepository(MachineRecord::class);
        $record = $recordRepo->findOneBy([
            'user' => $user,
            'machine' => $machine
        ]);

        $isNewRecord = false;

        // Jeśli nie ma jeszcze rekordu, tworzymy pierwszy
        if (!$record) {
            $record = new MachineRecord();
            $record->setUser($user);
            $record->setMachine($machine);
            $record->setMaxWeight($set->getWeight());
            $record->setMaxRepsAtMaxWeight($set->getReps());
            $record->setAchievedAt(new \DateTime());
            $isNewRecord = true;
        } else {
            // Jeśli rekord istnieje, sprawdzamy czy nowy jest lepszy
            $currentMax = $record->getMaxWeight();
            $currentReps = $record->getMaxRepsAtMaxWeight();

            // Pobicie rekordu: większy ciężar LUB ten sam ciężar, ale więcej powtórzeń
            if ($set->getWeight() > $currentMax || ($set->getWeight() == $currentMax && $set->getReps() > $currentReps)) {
                $record->setMaxWeight($set->getWeight());
                $record->setMaxRepsAtMaxWeight($set->getReps());
                $record->setAchievedAt(new \DateTime());
                $isNewRecord = true;
            }
        }

        if ($isNewRecord) {
            $this->em->persist($record);
            $this->em->flush();
            return $record;
        }

        return null;
    }
}