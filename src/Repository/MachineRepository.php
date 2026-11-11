<?php

namespace App\Repository;

use App\Entity\Machine;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MachineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Machine::class);
    }

    /**
     * Zwraca listę maszyn posortowaną priorytetowo pod profil użytkownika.
     */
    public function findRecommendedForUser(User $user): array
    {
        $qb = $this->createQueryBuilder('m');
        $gender = strtolower($user->getGender() ?? ''); 

        if (in_array($gender, ['kobieta', 'female'])) {
            // Waga 1 dla dolnych partii, reszta otrzymuje wagę 2
            $qb->addSelect("(CASE WHEN m.category IN ('Nogi', 'Pośladki', 'Brzuch') THEN 1 ELSE 2 END) AS HIDDEN priority")
               ->orderBy('priority', 'ASC');
        } elseif (in_array($gender, ['mężczyzna', 'male'])) {
            // Waga 1 dla górnych partii, reszta otrzymuje wagę 2
            $qb->addSelect("(CASE WHEN m.category IN ('Klatka piersiowa', 'Plecy', 'Barki', 'Ramiona', 'Wolne ciężary') THEN 1 ELSE 2 END) AS HIDDEN priority")
               ->orderBy('priority', 'ASC');
        } else {
            // Fallback dla braku precyzyjnej płci
            $qb->addSelect("1 AS HIDDEN priority"); 
        }

        // Dodatkowe sortowanie alfabetyczne dla maszyn o tym samym priorytecie
        $qb->addOrderBy('m.name', 'ASC');

        return $qb->getQuery()->getResult();
    }
}