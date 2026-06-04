<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Absence extends Model
{
    protected $fillable = ['session_id', 'etudiant_id', 'statut', 'date_limite'];

    protected $casts = ['date_limite' => 'datetime'];

    protected $appends = ['jours_restants'];

    public function sessionAppel(): BelongsTo
    {
        return $this->belongsTo(SessionAppel::class, 'session_id');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'etudiant_id');
    }

    public function justification(): HasOne
    {
        return $this->hasOne(Justification::class);
    }

    public function getJoursRestantsAttribute(): ?int
    {
        if (! $this->date_limite) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->date_limite->copy()->startOfDay(), false);
    }
}
