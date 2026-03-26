<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionUser = $request->user();
        if ($sessionUser !== null) {
            if (!$sessionUser->active) {
                return response()->json(['message' => 'Usuario sem acesso.'], 403);
            }

            return $next($request);
        }

        $header = (string) $request->header('Authorization', '');
        $token = '';

        if (str_starts_with($header, 'Bearer ')) {
            $token = trim(substr($header, 7));
        }

        if ($token === '') {
            return response()->json(['message' => 'Token nao informado.'], 401);
        }

        $tokenModel = ApiToken::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if ($tokenModel === null) {
            return response()->json(['message' => 'Token invalido.'], 401);
        }

        if ($tokenModel->expires_at !== null && $tokenModel->expires_at->isPast()) {
            return response()->json(['message' => 'Token expirado.'], 401);
        }

        $user = $tokenModel->user;
        if ($user === null || !$user->active) {
            return response()->json(['message' => 'Usuario sem acesso.'], 403);
        }

        $tokenModel->update(['last_used_at' => now()]);

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('api_token_id', $tokenModel->id);

        return $next($request);
    }
}
