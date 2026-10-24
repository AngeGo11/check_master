<?php

namespace App\Http\Controllers;

use App\Services\RoleDashboard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, RoleDashboard $dashboards): Response
    {
        $data = $request->validate(['space' => ['nullable', 'string', 'max:60']]);
        $response = Inertia::render('dashboard', [
            'dashboard' => $dashboards->build($request->user(), $data['space'] ?? null),
        ])->toResponse($request);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        return $response;
    }
}
