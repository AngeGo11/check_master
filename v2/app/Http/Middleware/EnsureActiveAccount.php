<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($authenticated = $request->user()) {
            $user = $authenticated->fresh();
            if ($user?->is_active && Auth::viaRemember() && ! $request->session()->has('auth_version')) {
                $request->session()->regenerate();
                $request->session()->put('auth_version', $user->auth_version);
            }
            if (! $user?->is_active || (int) $request->session()->get('auth_version') !== $user->auth_version) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Votre accès a changé. Veuillez vous reconnecter ou contacter l’administration.',
                ]);
            }
            Auth::guard('web')->setUser($user);
        }

        return $next($request);
    }
}
