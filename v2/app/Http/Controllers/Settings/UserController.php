<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Access\ManageAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveUserRequest;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'type_id' => ['nullable', 'integer', 'exists:user_types,id'],
            'group_id' => ['nullable', 'integer', 'exists:user_groups,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = trim($filters['search'] ?? '');
        $users = User::query()->with(['types', 'groups'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
            }))
            ->when($filters['type_id'] ?? null, fn ($query, $id) => $query->whereHas('types', fn ($types) => $types->where('user_types.id', $id)))
            ->when($filters['group_id'] ?? null, fn ($query, $id) => $query->whereHas('groups', fn ($groups) => $groups->where('user_groups.id', $id)))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')->orderBy('id')->paginate(20)->withQueryString()
            ->through(fn (User $user) => [
                ...$user->only('id', 'name', 'email', 'is_active', 'must_change_password'),
                'types' => $user->types->map(fn ($type) => $type->only('id', 'name')),
                'groups' => $user->groups->map(fn ($group) => $group->only('id', 'name')),
            ]);

        return Inertia::render('settings/users/index', [
            'users' => $users, 'filters' => $filters, ...$this->options(),
            'statistics' => ['total' => User::count(), 'active' => User::where('is_active', true)->count(), 'inactive' => User::where('is_active', false)->count()],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('settings/users/form', ['account' => null, ...$this->options()]);
    }

    public function store(SaveUserRequest $request, ManageAccess $access): RedirectResponse
    {
        $account = $access->createUser($request->user(), $request->validated());

        return to_route('users.edit', $account)->with('success', 'Compte créé. Son mot de passe devra être personnalisé à la première connexion.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('settings/users/form', [
            'account' => [
                ...$user->only('id', 'name', 'email', 'is_active', 'must_change_password'),
                'type_ids' => $user->types()->pluck('user_types.id'), 'group_ids' => $user->groups()->pluck('user_groups.id'),
            ], ...$this->options(),
        ]);
    }

    public function update(SaveUserRequest $request, User $user, ManageAccess $access): RedirectResponse
    {
        $account = $access->updateUser($request->user(), $user, $request->validated());
        if ($request->user()->is($account)) {
            Auth::guard('web')->setUser($account);
            $request->session()->regenerate();
            $request->session()->put('auth_version', $account->auth_version);
        }

        return ($request->user()->fresh()->can('users.view') ? to_route('users.index') : to_route('profile'))
            ->with('success', 'Utilisateur mis à jour. Les changements d’accès prennent effet immédiatement.');
    }

    private function options(): array
    {
        return [
            'types' => UserType::orderBy('id')->get(['id', 'name']),
            'groups' => UserGroup::with('types')->orderBy('id')->get()->map(fn ($group) => [
                ...$group->only('id', 'name'), 'type_ids' => $group->types->pluck('id'),
            ]),
        ];
    }
}
