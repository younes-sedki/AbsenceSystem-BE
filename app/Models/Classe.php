<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classe extends Model
{
    protected $fillable = ['code', 'annee'];

    public function students(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'etudiant');
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function sessionsAppel(): HasMany
    {
        return $this->hasMany(SessionAppel::class);
    }
}
