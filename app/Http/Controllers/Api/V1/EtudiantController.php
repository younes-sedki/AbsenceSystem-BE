<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\JustifierAbsenceRequest;
use App\Http\Resources\AbsenceResource;
use App\Http\Resources\SmsLogResource;
use App\Models\Absence;
use App\Models\Justification;
use App\Models\SmsLog;
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

        $path = $request->hasFile('fichier')
            ? $request->file('fichier')->store("justifications/{$absence->id}", 'public')
            : null;

        Justification::updateOrCreate(
            ['absence_id' => $absence->id],
            [
                'type' => $request->type,
                'notes' => $request->notes,
                'fichier_path' => $path,
                'motif_rejet' => null,
                'statut' => 'en_attente',
                'submitted_at' => now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]
        );

        $absence->update(['statut' => 'en_attente']);

        return response()->json([
            'success' => true,
            'data' => new AbsenceResource($absence->fresh(['sessionAppel.classe', 'sessionAppel.module', 'justification'])),
            'message' => 'Justification envoyee.',
        ], 201);
    }

    public function notifications(Request $request): JsonResponse
    {
        $logs = SmsLog::query()
            ->where('telephone', $request->user()->telephone)
            ->latest('sent_at')
            ->get();

        return response()->json(['success' => true, 'data' => SmsLogResource::collection($logs), 'message' => 'Notifications recuperees.']);
    }
}
