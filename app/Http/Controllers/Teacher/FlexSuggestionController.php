<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\FlexSuggestionStatus;
use App\Http\Controllers\Controller;
use App\Models\FlexSuggestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FlexSuggestionController extends Controller
{
    public function index(Request $request): Response
    {
        $suggestions = FlexSuggestion::query()
            ->where('target_user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (FlexSuggestion $suggestion): array => [
                'id' => $suggestion->id,
                'title' => $suggestion->title,
                'content' => $suggestion->content,
                'status' => $suggestion->status->value,
            ])
            ->all();

        return Inertia::render('Teacher/FlexSuggestions', [
            'suggestions' => $suggestions,
            'statuses' => FlexSuggestionStatus::values(),
        ]);
    }

    public function update(Request $request, FlexSuggestion $flexSuggestion): RedirectResponse
    {
        abort_unless($flexSuggestion->target_user_id === $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in([
                FlexSuggestionStatus::Accepted->value,
                FlexSuggestionStatus::Rejected->value,
            ])],
        ]);

        $flexSuggestion->update($data);

        return back()->with('success', __('ui.flash.suggestion_updated'));
    }
}
