<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Access\ManageAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveGroupPermissionsRequest;
use App\Models\Permission;
use App\Models\UserGroup;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/groups/index', [
            'groups' => UserGroup::with(['types', 'permissions'])->withCount('users')->orderBy('id')->get()->map(fn ($group) => [
                ...$group->only('id', 'name', 'users_count'),
                'protected' => $group->code === UserGroup::ADMINISTRATOR,
                'types' => $group->types->map(fn ($type) => $type->only('id', 'name')),
                'permissions' => $group->permissions->pluck('code'),
            ]),
            'permissions' => Permission::orderBy('code')->get(['code', 'name']),
        ]);
    }

    public function update(SaveGroupPermissionsRequest $request, UserGroup $group, ManageAccess $access): RedirectResponse
    {
        $access->updateGroup($request->user(), $group, $request->validated('permissions'));

        return ($request->user()->fresh()->can('groups.view') ? to_route('groups.index') : to_route('dashboard'))
            ->with('success', 'Permissions du groupe mises à jour.');
    }
}
