<?php

namespace App\Http\Controllers;

use App\Actions\Access\ManageAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('profile');
    }

    public function password(): Response
    {
        return Inertia::render('auth/change-password');
    }

    public function updatePassword(Request $request, ManageAccess $access): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:255', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.min' => 'Choisissez un mot de passe d’au moins 12 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.different' => 'Choisissez un mot de passe différent du mot de passe actuel.',
        ]);
        $user = $access->changePassword($request->user(), $data['password']);
        Auth::guard('web')->setUser($user);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        $request->session()->put('auth_version', $user->auth_version);

        return to_route('profile')->with('success', 'Mot de passe mis à jour. Les autres sessions ont été déconnectées.');
    }
}
