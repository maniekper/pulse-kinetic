<?php

namespace App\Service;

use App\Entity\Machine;
use App\Entity\User;

class WorkoutCalculatorService
{
    /**
     * Zwraca docelowy zakres powtórzeń i czas przerwy dla konkretnej maszyny i użytkownika.
     */
    public function calculateTargets(User $user, Machine $machine): array
    {
        $goal = $user->getCurrentGoal();
        
        // Zabezpieczenie: domyślne wartości, jeśli użytkownik nie ma jeszcze wybranego celu
        if (!$goal) {
            return [
                'repsMin' => 8,
                'repsMax' => 12,
                'restSeconds' => 90,
            ];
        }

        // Sprawdzamy typ mechaniki wybranej maszyny
        $isCompound = $machine->getMechanicType() === 'compound';

        // Zwracamy odpowiednie parametry na podstawie Celu
        return [
            'repsMin' => $isCompound ? $goal->getBaseCompoundRepsMin() : $goal->getBaseIsolationRepsMin(),
            'repsMax' => $isCompound ? $goal->getBaseCompoundRepsMax() : $goal->getBaseIsolationRepsMax(),
            'restSeconds' => $isCompound ? $goal->getRestCompoundSeconds() : $goal->getRestIsolationSeconds(),
        ];
    }
}
