<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\PrivateLessonStatus;
use App\Http\Controllers\Controller;
use App\Models\PrivateLesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrivateLessonController extends Controller
{
    public function update(Request $request, PrivateLesson $privateLesson): RedirectResponse
    {
        abort_unless($privateLesson->teacher_id === $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in([
                PrivateLessonStatus::Confirmed->value,
                PrivateLessonStatus::Completed->value,
                PrivateLessonStatus::Cancelled->value,
            ])],
        ]);

        $privateLesson->update($data);

        return back()->with('success', __('ui.flash.private_teacher_updated'));
    }
}
