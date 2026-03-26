<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if ($user === null || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais invalidas.'], 422);
        }

        if (!$user->active) {
            return response()->json(['message' => 'Usuario inativo.'], 403);
        }

        $issued = ApiToken::issueForUser($user, (string) ($data['device_name'] ?? 'default'));

        return response()->json([
            'token' => $issued['plainTextToken'],
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $tokenId = $request->attributes->get('api_token_id');
        if ($tokenId !== null) {
            ApiToken::query()->whereKey($tokenId)->delete();
        }

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }
}
