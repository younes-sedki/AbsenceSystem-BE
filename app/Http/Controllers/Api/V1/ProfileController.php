<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AvatarRequest;
use App\Http\Requests\Api\V1\ProfileChangeRequest as ProfileChangeFormRequest;
use App\Http\Resources\ProfileChangeRequestResource;
use App\Http\Resources\UserResource;
use App\Models\ProfileChangeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => new UserResource($request->user()->load(['classe', 'teacherAssignments.classe', 'teacherAssignments.module'])), 'message' => 'Profil recupere.']);
    }

    public function requestChange(ProfileChangeFormRequest $request): JsonResponse
    {
        $existing = ProfileChangeRequest::where('user_id', $request->user()->id)
            ->where('champ', $request->champ)
            ->where('statut', 'en_attente')
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Demande deja en attente.'], 409);
        }

        $change = ProfileChangeRequest::create([
            'user_id' => $request->user()->id,
            'champ' => $request->champ,
            'ancienne_valeur' => $request->user()->{$request->champ},
            'nouvelle_valeur' => $request->nouvelle_valeur,
            'statut' => 'en_attente',
        ]);

        return response()->json(['success' => true, 'data' => new ProfileChangeRequestResource($change), 'message' => 'Demande de changement creee.'], 201);
    }

    public function requests(Request $request): JsonResponse
    {
        $requests = ProfileChangeRequest::where('user_id', $request->user()->id)
            ->where('statut', 'en_attente')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProfileChangeRequestResource::collection($requests),
            'message' => 'Demandes en attente recuperees.',
        ]);
    }

    public function avatar(AvatarRequest $request): JsonResponse
    {
        if ($request->user()->avatar) {
            Storage::disk('public')->delete($request->user()->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $request->user()->update(['avatar' => $path]);

        return response()->json(['success' => true, 'data' => new UserResource($request->user()->fresh()), 'message' => 'Avatar mis a jour.']);
    }
}
