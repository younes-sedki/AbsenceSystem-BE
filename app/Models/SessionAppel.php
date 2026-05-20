<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionAppel extends Model
{
    protected $table = 'sessions_appel';

    protected $fillable = ['teacher_id', 'classe_id', 'module_id', 'date', 'heure_debut', 'statut'];

    protected $casts = ['date' => 'date'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class, 'session_id');
    }
}
