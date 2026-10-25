<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseSectionController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $course->sections()->create($data);

        return back()->with('success', __('ui.flash.section_created'));
    }

    public function update(Request $request, Course $course, CourseSection $section): RedirectResponse
    {
        abort_unless($section->course_id === $course->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $section->update($data);

        return back()->with('success', __('ui.flash.section_updated'));
    }

    public function destroy(Course $course, CourseSection $section): RedirectResponse
    {
        abort_unless($section->course_id === $course->id, 404);

        $section->delete();

        return back()->with('success', __('ui.flash.section_deleted'));
    }
}
