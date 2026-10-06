<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    /**
     * Handle an incoming request.
     * Vérifie que l'utilisateur est connecté (email + mot de passe).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        return $next($request);
    }
}


