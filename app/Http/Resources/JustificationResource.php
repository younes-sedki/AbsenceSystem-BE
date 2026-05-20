<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JustificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'absence_id' => $this->absence_id,
            'type' => $this->type,
            'notes' => $this->notes,
            'fichier_url' => $this->fichier_url,
            'motif_rejet' => $this->motif_rejet,
            'statut' => $this->statut,
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'absence' => new AbsenceResource($this->whenLoaded('absence')),
        ];
    }
}
