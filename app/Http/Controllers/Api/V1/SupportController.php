<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProblemReportRequest;
use App\Http\Resources\ProblemReportResource;
use App\Models\ProblemReport;
use Illuminate\Http\JsonResponse;

class SupportController extends Controller
{
    public function store(ProblemReportRequest $request): JsonResponse
    {
        $report = ProblemReport::create([
            'user_id' => $request->user()->id,
            'sujet' => $request->sujet,
            'description' => $request->description,
            'statut' => 'ouverte',
        ]);

        return response()->json([
            'success' => true,
            'data' => new ProblemReportResource($report->load('user')),
            'message' => 'Probleme envoye a l\'administration.',
        ], 201);
    }
}
