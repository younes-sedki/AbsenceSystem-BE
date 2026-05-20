<?php

namespace App\Policies;

use App\Models\Absence;
use App\Models\User;

class AbsencePolicy
{
    public function view(User $user, Absence $absence): bool
    {
        if ($user->role === 'admin' || $absence->etudiant_id === $user->id) {
            return true;
        }

        return $user->role === 'enseignant'
            && $absence->sessionAppel
            && $absence->sessionAppel->teacher_id === $user->id;
    }

    public function update(User $user, Absence $absence): bool
    {
        return $this->view($user, $absence);
    }
}
