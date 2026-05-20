<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Justification extends Model
{
    protected $fillable = [
        'absence_id',
        'type',
        'notes',
        'fichier_path',
        'motif_rejet',
        'statut',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected $appends = ['fichier_url'];

    public function absence(): BelongsTo
    {
        return $this->belongsTo(Absence::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getFichierUrlAttribute(): ?string
    {
        return $this->fichier_path ? Storage::disk('public')->url($this->fichier_path) : null;
    }
}
