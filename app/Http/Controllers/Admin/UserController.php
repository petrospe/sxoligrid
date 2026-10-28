<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()->orderBy('name')->get()->map(fn (User $user): array => $this->present($user))->all(),
            'roles' => $this->assignableRoles(request()->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::query()->create($this->validated($request));

        return redirect()->route('admin.users.index')->with('success', __('ui.flash.user_created'));
    }

    public function edit(Request $request, User $user): Response
    {
        $this->guardOwnerRecord($request, $user);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $this->present($user),
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->guardOwnerRecord($request, $user);

        $user->update($this->validated($request, $user));

        return redirect()->route('admin.users.index')->with('success', __('ui.flash.user_updated'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->guardOwnerRecord($request, $user);
        abort_if($user->is($request->user()), 403);

        try {
            $user->delete();
        } catch (QueryException) {
            return back()->with('error', __('ui.flash.user_delete_blocked'));
        }

        return redirect()->route('admin.users.index')->with('success', __('ui.flash.user_deleted'));
    }

    /**
     * @return list<string>
     */
    private function assignableRoles(User $actor): array
    {
        $roles = [UserRole::Admin->value, UserRole::Teacher->value, UserRole::Student->value];

        if ($actor->isOwner()) {
            $roles[] = UserRole::Owner->value;
        }

        return $roles;
    }

    private function guardOwnerRecord(Request $request, User $user): void
    {
        if ($user->isOwner() && ! $request->user()->isOwner()) {
            abort(403);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'distinct', Rule::in($this->assignableRoles($request->user()))],
            'phone' => ['nullable', 'string', 'max:30'],
            'can_view_finances' => ['sometimes', 'boolean'],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $data['roles'] = User::normalizeRoles($data['roles']);

        if ($user?->is($request->user()) && $request->user()->isOwner() && ! in_array(UserRole::Owner->value, $data['roles'], true)) {
            throw ValidationException::withMessages([
                'roles' => __('ui.errors.keep_own_owner_role'),
            ]);
        }

        $isOwner = in_array(UserRole::Owner->value, $data['roles'], true);
        $isAdmin = in_array(UserRole::Admin->value, $data['roles'], true);

        $data['can_view_finances'] = $isOwner || (
            $request->user()->isOwner()
            && $isAdmin
            && $request->boolean('can_view_finances')
        );

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => $user->roleValues(),
            'can_view_finances' => $user->can_view_finances,
        ];
    }
}
