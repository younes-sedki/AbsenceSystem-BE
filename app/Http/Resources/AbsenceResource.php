<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'statut' => $this->statut,
            'date_limite' => $this->date_limite?->toDateTimeString(),
            'jours_restants' => $this->jours_restants,
            'etudiant' => new UserResource($this->whenLoaded('etudiant')),
            'session' => new SessionAppelResource($this->whenLoaded('sessionAppel')),
            'justification' => new JustificationResource($this->whenLoaded('justification')),
        ];
    }
}
