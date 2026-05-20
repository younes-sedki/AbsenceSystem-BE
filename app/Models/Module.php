<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = ['code', 'intitule', 'annee'];

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function sessionsAppel(): HasMany
    {
        return $this->hasMany(SessionAppel::class);
    }
}
