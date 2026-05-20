<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SoumettreSessionRequest;
use App\Http\Requests\Api\V1\StoreSessionRequest;
use App\Http\Resources\SessionAppelResource;
use App\Http\Resources\TeacherAssignmentResource;
use App\Http\Resources\UserResource;
use App\Jobs\SendSmsJob;
use App\Models\Absence;
use App\Models\Classe;
use App\Models\SessionAppel;
use App\Models\TeacherAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnseignantController extends Controller
{
    public function assignments(Request $request): JsonResponse
    {
        $assignments = $request->user()->teacherAssignments()->with(['classe', 'module'])->get();

        return response()->json(['success' => true, 'data' => TeacherAssignmentResource::collection($assignments), 'message' => 'Affectations recuperees.']);
    }

    public function etudiants(Request $request, string $classeCode): JsonResponse
    {
        $classe = Classe::where('code', $classeCode)->firstOrFail();
        abort_unless($this->teacherHasClass($request->user()->id, $classe->id), 403);

        $students = $classe->students()->with('classe')->orderBy('nom')->orderBy('prenom')->get();

        return response()->json(['success' => true, 'data' => UserResource::collection($students), 'message' => 'Etudiants recuperes.']);
    }

    public function storeSession(StoreSessionRequest $request): JsonResponse
    {
        abort_unless($this->teacherHasAssignment($request->user()->id, $request->classe_id, $request->module_id), 403);

        $session = SessionAppel::create([
            'teacher_id' => $request->user()->id,
            'classe_id' => $request->classe_id,
            'module_id' => $request->module_id,
            'date' => $request->date,
            'heure_debut' => $request->heure_debut,
            'statut' => 'ouverte',
        ]);

        return response()->json(['success' => true, 'data' => new SessionAppelResource($session->load(['classe', 'module'])), 'message' => 'Session creee.'], 201);
    }

    public function soumettre(SoumettreSessionRequest $request, SessionAppel $session): JsonResponse
    {
        abort_unless($session->teacher_id === $request->user()->id, 403);
        abort_if($session->statut === 'soumise', 422, 'Cette session est deja soumise.');

        foreach ($request->absences as $row) {
            if (! $row['present']) {
                $absence = Absence::firstOrCreate(
                    ['session_id' => $session->id, 'etudiant_id' => $row['etudiant_id']],
                    ['statut' => 'non_justifiee', 'date_limite' => $session->date->copy()->addDays(7)->endOfDay()]
                );
                SendSmsJob::dispatch('absence_detectee', $absence->id);
            }
        }

        $session->update(['statut' => 'soumise']);

        return response()->json(['success' => true, 'data' => new SessionAppelResource($session->fresh(['classe', 'module', 'absences.etudiant'])), 'message' => 'Session soumise.']);
    }

    public function sessions(Request $request): JsonResponse
    {
        $sessions = $request->user()->sessionsAppel()->with(['classe', 'module', 'absences.etudiant'])->latest('date')->get();

        return response()->json(['success' => true, 'data' => SessionAppelResource::collection($sessions), 'message' => 'Sessions recuperees.']);
    }

    public function export(Request $request, SessionAppel $session): JsonResponse
    {
        abort_unless($session->teacher_id === $request->user()->id, 403);

        $session->load(['classe', 'module', 'teacher', 'absences.etudiant']);
        $data = [
            'session' => [
                'date' => $session->date->toDateString(),
                'heure_debut' => $session->heure_debut,
                'classe' => $session->classe->code,
                'module' => $session->module->code,
                'enseignant' => "{$session->teacher->prenom} {$session->teacher->nom}",
            ],
            'absences' => $session->absences->map(fn ($absence) => [
                'nom' => $absence->etudiant->nom,
                'prenom' => $absence->etudiant->prenom,
                'email' => $absence->etudiant->email,
                'statut' => $absence->statut,
                'date_limite' => $absence->date_limite->toDateString(),
                'jours_restants' => $absence->jours_restants,
            ]),
        ];

        return response()->json(['success' => true, 'data' => $data, 'message' => 'Export prepare.']);
    }

    private function teacherHasClass(int $teacherId, int $classeId): bool
    {
        return TeacherAssignment::where('user_id', $teacherId)->where('classe_id', $classeId)->exists();
    }

    private function teacherHasAssignment(int $teacherId, int $classeId, int $moduleId): bool
    {
        return TeacherAssignment::where('user_id', $teacherId)->where('classe_id', $classeId)->where('module_id', $moduleId)->exists();
    }
}
