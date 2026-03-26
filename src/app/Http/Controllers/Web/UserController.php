<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $profile = (string) $request->query('profile', '');
        $status = (string) $request->query('status', '');

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($profile, ['admin', 'vendedor', 'estoquista', 'operador'], true), fn ($query) => $query->where('profile', $profile))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('active', $status === 'active'))
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        $staleSince = Carbon::now()->subDays(30);

        $summary = [
            'total' => (int) User::query()->count(),
            'active' => (int) User::query()->where('active', true)->count(),
            'inactive' => (int) User::query()->where('active', false)->count(),
            'stale' => (int) User::query()->where('updated_at', '<', $staleSince)->count(),
        ];

        return view('users.index', [
            'users' => $users,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'profile' => $profile,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => null]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::query()->create($request->validated());

        return redirect()->route('users.index')->with('success', 'Usuario criado com sucesso!');
    }

    public function edit(User $user): View
    {
        return view('users.form', compact('user'));
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['password'])) {
            $data = Arr::except($data, ['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Usuario atualizado com sucesso!');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (Auth::id() === $user->id) {
            return back()->withErrors(['user' => 'Voce nao pode remover seu proprio usuario.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario removido com sucesso!');
    }
}
