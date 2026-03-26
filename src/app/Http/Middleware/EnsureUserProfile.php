<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserProfile
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next, string ...$profiles): Response
    {
        $user = $request->user();
        $expectsJson = $request->expectsJson() || $request->is('api/*');

        if ($user === null) {
            if ($expectsJson) {
                return response()->json(['message' => 'Nao autenticado.'], 401);
            }

            return redirect()->route('login');
        }

        if (!in_array($user->profile, $profiles, true)) {
            if ($expectsJson) {
                return response()->json(['message' => 'Perfil sem permissao para esta acao.'], 403);
            }

            abort(403, 'Perfil sem permissao para esta acao.');
        }

        return $next($request);
    }
}
