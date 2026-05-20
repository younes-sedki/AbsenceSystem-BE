<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'destinataire_nom' => $this->destinataire_nom,
            'telephone' => $this->telephone,
            'message' => $this->message,
            'type' => $this->type,
            'statut' => $this->statut,
            'sent_at' => $this->sent_at?->toDateTimeString(),
        ];
    }
}
