<?php

namespace App\Policies;

use App\Models\SessionAppel;
use App\Models\TeacherAssignment;
use App\Models\User;

class SessionAppelPolicy
{
    public function view(User $user, SessionAppel $sessionAppel): bool
    {
        return $user->role === 'admin' || $sessionAppel->teacher_id === $user->id;
    }

    public function create(User $user, int $classeId = null, int $moduleId = null): bool
    {
        if ($user->role !== 'enseignant' || ! $classeId || ! $moduleId) {
            return false;
        }

        return TeacherAssignment::where('user_id', $user->id)
            ->where('classe_id', $classeId)
            ->where('module_id', $moduleId)
            ->exists();
    }

    public function update(User $user, SessionAppel $sessionAppel): bool
    {
        return $sessionAppel->teacher_id === $user->id;
    }
}
