<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $users = User::query()
            ->when($request->filled('profile'), fn ($q) => $q->where('profile', $request->string('profile')))
            ->when($request->filled('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->when(
                $request->filled('q'),
                fn ($q) => $q->where(function ($qq) use ($request) {
                    $term = '%'.$request->string('q').'%';
                    $qq->where('name', 'like', $term)->orWhere('email', 'like', $term);
                })
            )
            ->select(['id', 'name', 'email', 'profile', 'active', 'created_at', 'updated_at'])
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($users);
    }

    public function show(User $usuario): JsonResponse
    {
        return response()->json([
            'id' => $usuario->id,
            'name' => $usuario->name,
            'email' => $usuario->email,
            'profile' => $usuario->profile,
            'active' => $usuario->active,
            'created_at' => $usuario->created_at,
            'updated_at' => $usuario->updated_at,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'profile' => ['required', Rule::in(['admin', 'vendedor', 'estoquista', 'operador'])],
            'active' => ['nullable', 'boolean'],
        ]);

        $user = User::query()->create([
            ...$data,
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile' => $user->profile,
            'active' => $user->active,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ], 201);
    }

    public function update(Request $request, User $usuario): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password' => ['sometimes', 'required', 'string', 'min:6', 'max:100'],
            'profile' => ['sometimes', 'required', Rule::in(['admin', 'vendedor', 'estoquista', 'operador'])],
            'active' => ['sometimes', 'required', 'boolean'],
        ]);

        if (
            isset($data['active'])
            && (bool) $data['active'] === false
            && (int) $request->user()?->id === (int) $usuario->id
        ) {
            return response()->json([
                'message' => 'Nao e permitido inativar o proprio usuario logado.',
            ], 422);
        }

        $usuario->update($data);

        return response()->json([
            'id' => $usuario->id,
            'name' => $usuario->name,
            'email' => $usuario->email,
            'profile' => $usuario->profile,
            'active' => $usuario->active,
            'created_at' => $usuario->created_at,
            'updated_at' => $usuario->updated_at,
        ]);
    }

    public function setStatus(Request $request, User $usuario): JsonResponse
    {
        $data = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        if ((bool) $data['active'] === false && (int) $request->user()?->id === (int) $usuario->id) {
            return response()->json([
                'message' => 'Nao e permitido inativar o proprio usuario logado.',
            ], 422);
        }

        $usuario->update(['active' => (bool) $data['active']]);

        return response()->json([
            'id' => $usuario->id,
            'active' => $usuario->active,
        ]);
    }
}
