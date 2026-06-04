<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RejectJustificationRequest;
use App\Http\Resources\AbsenceResource;
use App\Http\Resources\JustificationResource;
use App\Models\Absence;
use App\Models\Justification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function absences(Request $request): JsonResponse
    {
        $absences = Absence::with(['etudiant.classe', 'sessionAppel.teacher', 'sessionAppel.classe', 'sessionAppel.module', 'justification'])
            ->when($request->classe, fn ($q) => $q->whereHas('sessionAppel.classe', fn ($c) => $c->where('code', $request->classe)))
            ->when($request->enseignant, fn ($q) => $q->whereHas('sessionAppel.teacher', fn ($t) => $t->where('id', $request->enseignant)->orWhere('email', $request->enseignant)))
            ->when($request->statut, fn ($q) => $q->where('statut', $request->statut))
            ->when($request->date, fn ($q) => $q->whereHas('sessionAppel', fn ($s) => $s->whereDate('date', $request->date)))
            ->latest()
            ->paginate(25);

        return response()->json([
            'success' => true,
            'data' => AbsenceResource::collection($absences->items()),
            'meta' => [
                'total' => $absences->total(),
                'per_page' => $absences->perPage(),
                'current_page' => $absences->currentPage(),
                'last_page' => $absences->lastPage(),
            ],
            'message' => 'Absences recuperees.',
        ]);
    }

    public function justifications(): JsonResponse
    {
        $justifications = Justification::where('statut', 'en_attente')->with(['absence.etudiant', 'absence.sessionAppel.classe', 'absence.sessionAppel.module'])->latest()->get();

        return response()->json(['success' => true, 'data' => JustificationResource::collection($justifications), 'message' => 'Justifications en attente recuperees.']);
    }

    public function accepter(Request $request, Justification $justification): JsonResponse
    {
        abort_unless($justification->statut === 'en_attente', 422, 'Cette justification a deja ete traitee.');

        $justification->update(['statut' => 'acceptee', 'reviewed_at' => now(), 'reviewed_by' => $request->user()->id, 'motif_rejet' => null]);
        $justification->absence()->update(['statut' => 'justifiee']);

        return response()->json(['success' => true, 'data' => new JustificationResource($justification->fresh('absence')), 'message' => 'Justification acceptee.']);
    }

    public function rejeter(RejectJustificationRequest $request, Justification $justification): JsonResponse
    {
        abort_unless($justification->statut === 'en_attente', 422, 'Cette justification a deja ete traitee.');

        $justification->update(['statut' => 'rejetee', 'motif_rejet' => $request->motif_rejet, 'reviewed_at' => now(), 'reviewed_by' => $request->user()->id]);
        $justification->absence()->update(['statut' => 'rejetee']);

        return response()->json(['success' => true, 'data' => new JustificationResource($justification->fresh('absence')), 'message' => 'Justification rejetee.']);
    }
}
