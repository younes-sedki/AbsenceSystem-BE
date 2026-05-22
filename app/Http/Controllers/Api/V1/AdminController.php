<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RejectJustificationRequest;
use App\Http\Resources\AbsenceResource;
use App\Http\Resources\JustificationResource;
use App\Http\Resources\ProblemReportResource;
use App\Http\Resources\ProfileChangeRequestResource;
use App\Http\Resources\SmsLogResource;
use App\Http\Resources\UserResource;
use App\Jobs\SendSmsJob;
use App\Models\Absence;
use App\Models\Justification;
use App\Models\ProblemReport;
use App\Models\ProfileChangeRequest;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\PatternDetectionService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function enseignants(): JsonResponse
    {
        $teachers = User::where('role', 'enseignant')
            ->with(['teacherAssignments.classe', 'teacherAssignments.module'])
            ->withCount('sessionsAppel')
            ->get();

        return response()->json(['success' => true, 'data' => UserResource::collection($teachers), 'message' => 'Enseignants recuperes.']);
    }

    public function absences(Request $request): JsonResponse
    {
        $absences = Absence::with(['etudiant.classe', 'sessionAppel.teacher', 'sessionAppel.classe', 'sessionAppel.module', 'justification'])
            ->when($request->classe, fn ($q) => $q->whereHas('sessionAppel.classe', fn ($c) => $c->where('code', $request->classe)))
            ->when($request->enseignant, fn ($q) => $q->whereHas('sessionAppel.teacher', fn ($t) => $t->where('id', $request->enseignant)->orWhere('email', $request->enseignant)))
            ->when($request->statut, fn ($q) => $q->where('statut', $request->statut))
            ->when($request->date, fn ($q) => $q->whereHas('sessionAppel', fn ($s) => $s->whereDate('date', $request->date)))
            ->latest()
            ->paginate(25);

        return response()->json(['success' => true, 'data' => AbsenceResource::collection($absences), 'message' => 'Absences recuperees.']);
    }

    public function justifications(): JsonResponse
    {
        $justifications = Justification::where('statut', 'en_attente')->with(['absence.etudiant', 'absence.sessionAppel.classe', 'absence.sessionAppel.module'])->latest()->get();

        return response()->json(['success' => true, 'data' => JustificationResource::collection($justifications), 'message' => 'Justifications en attente recuperees.']);
    }

    public function accepter(Request $request, Justification $justification): JsonResponse
    {
        $justification->update(['statut' => 'acceptee', 'reviewed_at' => now(), 'reviewed_by' => $request->user()->id, 'motif_rejet' => null]);
        $justification->absence()->update(['statut' => 'justifiee']);
        SendSmsJob::dispatch('justification_acceptee', $justification->absence_id);

        return response()->json(['success' => true, 'data' => new JustificationResource($justification->fresh('absence')), 'message' => 'Justification acceptee.']);
    }

    public function rejeter(RejectJustificationRequest $request, Justification $justification): JsonResponse
    {
        $justification->update(['statut' => 'rejetee', 'motif_rejet' => $request->motif_rejet, 'reviewed_at' => now(), 'reviewed_by' => $request->user()->id]);
        $justification->absence()->update(['statut' => 'rejetee']);
        SendSmsJob::dispatch('justification_rejetee', $justification->absence_id);

        return response()->json(['success' => true, 'data' => new JustificationResource($justification->fresh('absence')), 'message' => 'Justification rejetee.']);
    }

    public function smsLogs(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => SmsLogResource::collection(SmsLog::latest('sent_at')->paginate(25)), 'message' => 'Logs SMS recuperes.']);
    }

    public function envoyerSmsParent(Request $request, Absence $absence, SmsService $smsService): JsonResponse
    {
        $absence->load(['etudiant', 'sessionAppel.module', 'sessionAppel.classe']);

        if ($absence->statut !== 'non_justifiee') {
            return response()->json([
                'success' => false,
                'message' => 'Cette absence a deja une justification.',
            ], 422);
        }

        $parentPhone = $absence->etudiant?->parent_telephone ?? $absence->etudiant?->telephone;
        if (! $parentPhone) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun numero de parent disponible.',
            ], 422);
        }

        $log = $smsService->sendParentReminder($absence);

        return response()->json([
            'success' => $log->statut === 'envoye',
            'data' => new SmsLogResource($log),
            'message' => $log->statut === 'envoye' ? 'SMS envoye.' : 'Echec de l\'envoi du SMS.',
        ]);
    }

    public function profileRequests(): JsonResponse
    {
        $requests = ProfileChangeRequest::where('statut', 'en_attente')->with('user.classe')->latest()->get();

        return response()->json(['success' => true, 'data' => ProfileChangeRequestResource::collection($requests), 'message' => 'Demandes recuperees.']);
    }

    public function problems(Request $request): JsonResponse
    {
        $reports = ProblemReport::with(['user.classe', 'resolver'])
            ->when($request->statut, fn ($q) => $q->where('statut', $request->statut))
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => ProblemReportResource::collection($reports), 'message' => 'Problemes recuperes.']);
    }

    public function resoudreProblem(Request $request, ProblemReport $problemReport): JsonResponse
    {
        if ($problemReport->statut !== 'resolue') {
            $problemReport->update([
                'statut' => 'resolue',
                'resolved_at' => now(),
                'resolved_by' => $request->user()->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new ProblemReportResource($problemReport->fresh(['user', 'resolver'])),
            'message' => 'Probleme resolu.',
        ]);
    }

    public function approuverProfile(Request $request, ProfileChangeRequest $profileRequest): JsonResponse
    {
        $profileRequest->user()->update([$profileRequest->champ => $profileRequest->nouvelle_valeur]);
        $profileRequest->update(['statut' => 'approuvee', 'reviewed_by' => $request->user()->id]);

        return response()->json(['success' => true, 'data' => new ProfileChangeRequestResource($profileRequest->fresh('user')), 'message' => 'Demande approuvee.']);
    }

    public function rejeterProfile(Request $request, ProfileChangeRequest $profileRequest): JsonResponse
    {
        $profileRequest->update(['statut' => 'rejetee', 'reviewed_by' => $request->user()->id]);

        return response()->json(['success' => true, 'data' => new ProfileChangeRequestResource($profileRequest->fresh('user')), 'message' => 'Demande rejetee.']);
    }

    public function patterns(PatternDetectionService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->detectAll(),
        ]);
    }
}
