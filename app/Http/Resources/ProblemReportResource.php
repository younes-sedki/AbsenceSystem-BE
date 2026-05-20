<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProblemReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sujet' => $this->sujet,
            'description' => $this->description,
            'statut' => $this->statut,
            'created_at' => $this->created_at?->toDateTimeString(),
            'resolved_at' => $this->resolved_at?->toDateTimeString(),
            'user' => new UserResource($this->whenLoaded('user')),
            'resolver' => new UserResource($this->whenLoaded('resolver')),
        ];
    }
}
