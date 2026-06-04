<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'role' => $this->role,
            'telephone' => $this->telephone,
            'avatar' => $this->avatar,
            'etablissement' => $this->etablissement,
            'cne' => $this->cne,
            'classe_id' => $this->classe_id,
            'classe' => new ClasseResource($this->whenLoaded('classe')),
            'filiere' => $this->filiere,
            'assignments' => TeacherAssignmentResource::collection($this->whenLoaded('teacherAssignments')),
        ];
    }
}
