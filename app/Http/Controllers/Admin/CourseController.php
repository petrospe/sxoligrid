<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingMethod;
use App\Enums\CourseType;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Courses/Index', [
            'courses' => Course::query()->orderBy('title')->get()->map(fn (Course $course): array => $this->present($course))->all(),
            'types' => CourseType::values(),
            'billing_methods' => BillingMethod::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Course::query()->create($this->validated($request));

        return redirect()->route('admin.courses.index')->with('success', __('ui.flash.course_created'));
    }

    public function edit(Course $course): Response
    {
        return Inertia::render('Admin/Courses/Edit', [
            'course' => $this->present($course),
            'types' => CourseType::values(),
            'billing_methods' => BillingMethod::values(),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $course->update($this->validated($request));

        return redirect()->route('admin.courses.index')->with('success', __('ui.flash.course_updated'));
    }

    public function destroy(Course $course): RedirectResponse
    {
        try {
            $course->delete();
        } catch (QueryException) {
            return back()->with('error', __('ui.flash.course_delete_blocked'));
        }

        return redirect()->route('admin.courses.index')->with('success', __('ui.flash.course_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'type' => ['required', Rule::in(CourseType::values())],
            'billing_methods' => ['required', 'array', 'min:1'],
            'billing_methods.*' => ['distinct', Rule::in(BillingMethod::values())],
        ]);

        $data['billing_methods'] = array_values(array_filter(
            BillingMethod::values(),
            fn (string $method): bool => in_array($method, $data['billing_methods'], true),
        ));

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,
            'price' => (float) $course->price,
            'type' => $course->type->value,
            'billing_methods' => array_values($course->billing_methods ?? []),
        ];
    }
}
