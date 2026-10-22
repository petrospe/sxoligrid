<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FlexSuggestionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\FlexSuggestion;
use App\Models\User;
use App\Services\FlexGapFinder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FlexSuggestionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/FlexSuggestions/Index', [
            'suggestions' => FlexSuggestion::query()
                ->with('target:id,name,role')
                ->orderByDesc('id')
                ->get()
                ->map(fn (FlexSuggestion $suggestion): array => [
                    'id' => $suggestion->id,
                    'title' => $suggestion->title,
                    'content' => $suggestion->content,
                    'status' => $suggestion->status->value,
                    'target' => $suggestion->target->name,
                ])
                ->all(),
            'targets' => User::query()
                ->whereIn('role', [UserRole::Owner, UserRole::Admin, UserRole::Teacher])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name])
                ->all(),
            'statuses' => FlexSuggestionStatus::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $target = User::query()->find($data['target_user_id']);

        if ($target === null || $target->isStudent()) {
            throw ValidationException::withMessages([
                'target_user_id' => __('ui.errors.suggestion_target'),
            ]);
        }

        FlexSuggestion::query()->create([
            ...$data,
            'status' => FlexSuggestionStatus::Unread,
        ]);

        return back()->with('success', __('ui.flash.suggestion_saved'));
    }

    public function scan(FlexGapFinder $finder): RedirectResponse
    {
        $created = $finder->scan();

        $message = $created === 0
            ? __('ui.flash.gaps_none')
            : trans('ui.flash.gaps_found', ['count' => $created]);

        return back()->with('success', $message);
    }

    public function update(Request $request, FlexSuggestion $flexSuggestion): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(FlexSuggestionStatus::values())],
        ]);

        $flexSuggestion->update($data);

        return back()->with('success', __('ui.flash.suggestion_updated'));
    }
}
