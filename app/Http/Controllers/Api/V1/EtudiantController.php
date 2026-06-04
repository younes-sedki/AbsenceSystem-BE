<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\JustifierAbsenceRequest;
use App\Http\Resources\AbsenceResource;
use App\Models\Absence;
use App\Models\Justification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EtudiantController extends Controller
{
    public function absences(Request $request): JsonResponse
    {
        $absences = $request->user()->absences()
            ->with(['sessionAppel.classe', 'sessionAppel.module', 'justification'])
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => AbsenceResource::collection($absences), 'message' => 'Absences recuperees.']);
    }

    public function justifier(JustifierAbsenceRequest $request, Absence $absence): JsonResponse
    {
        abort_unless($absence->etudiant_id === $request->user()->id, 403);
        abort_if($absence->statut === 'justifiee', 422, 'Cette absence est deja justifiee.');
        abort_if($absence->statut === 'en_attente', 422, 'Un justificatif est deja en cours de validation.');

        if ($absence->date_limite && now()->isAfter($absence->date_limite)) {
            abort(422, 'La date limite de justification est depassee.');
        }

        $existing = Justification::where('absence_id', $absence->id)->first();
        $path = $request->hasFile('fichier')
            ? $request->file('fichier')->store("justifications/{$absence->id}", 'public')
            : null;

        $justificationData = [
            'type' => $request->type,
            'notes' => $request->notes,
            'motif_rejet' => null,
            'statut' => 'en_attente',
            'submitted_at' => now(),
            'reviewed_at' => null,
            'reviewed_by' => null,
        ];

        if ($path !== null) {
            $justificationData['fichier_path'] = $path;
        } elseif (! $existing?->fichier_path) {
            $justificationData['fichier_path'] = null;
        }

        Justification::updateOrCreate(
            ['absence_id' => $absence->id],
            $justificationData
        );

        $absence->update(['statut' => 'en_attente']);

        return response()->json([
            'success' => true,
            'data' => new AbsenceResource($absence->fresh(['sessionAppel.classe', 'sessionAppel.module', 'justification'])),
            'message' => 'Justification envoyee.',
        ], 201);
    }
}
