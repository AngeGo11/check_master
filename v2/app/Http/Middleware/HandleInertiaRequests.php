<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user() ? [
                    ...$request->user()->only('id', 'name', 'email', 'must_change_password'),
                    'types' => $request->user()->types()->orderBy('user_types.id')->get(['user_types.id', 'name'])->map(fn ($type) => $type->only('id', 'name')),
                    'groups' => $request->user()->groups()->orderBy('user_groups.id')->get(['user_groups.id', 'name'])->map(fn ($group) => $group->only('id', 'name')),
                ] : null,
                'permissions' => fn () => $request->user()?->permissionCodes() ?? [],
            ],
            'unreadMessageCount' => fn () => $request->user() ? app(\App\Services\CollaborationAccess::class)->unread($request->user()) : 0,
            'flash' => ['success' => fn () => $request->session()->get('success')],
        ];
    }
}
