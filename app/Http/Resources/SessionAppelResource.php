<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionAppelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher' => new UserResource($this->whenLoaded('teacher')),
            'classe' => new ClasseResource($this->whenLoaded('classe')),
            'module' => new ModuleResource($this->whenLoaded('module')),
            'date' => $this->date?->toDateString(),
            'heure_debut' => $this->heure_debut,
            'statut' => $this->statut,
            'absences' => AbsenceResource::collection($this->whenLoaded('absences')),
        ];
    }
}
