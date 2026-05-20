<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->user_id,
            'classe' => new ClasseResource($this->whenLoaded('classe')),
            'module' => new ModuleResource($this->whenLoaded('module')),
        ];
    }
}
