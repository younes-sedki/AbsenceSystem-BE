<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::with(['classe', 'teacherAssignments.classe', 'teacherAssignments.module'])
            ->where('email', $request->email)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'errors' => ['email' => ['Identifiants invalides.']], 'message' => 'Connexion refusee.'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => ['token' => $user->createToken('api-token')->plainTextToken, 'user' => new UserResource($user)],
            'message' => 'Connexion reussie.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'data' => null, 'message' => 'Deconnexion reussie.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['classe', 'teacherAssignments.classe', 'teacherAssignments.module']);

        return response()->json(['success' => true, 'data' => new UserResource($user), 'message' => 'Utilisateur authentifie.']);
    }
}
