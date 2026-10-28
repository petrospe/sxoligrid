<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseType;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\User;
use App\Support\Week;
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
        $courses = Course::query()
            ->with([
                'sections.enrollments.student:id,name',
                'sections.schedules.teacher:id,name',
                'sections.schedules.teachers:id,name',
            ])
            ->orderBy('title')
            ->get()
            ->map(fn (Course $course): array => [
                ...$this->present($course),
                'sections' => $this->sections($course),
            ])
            ->all();

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
            'types' => CourseType::values(),
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
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(CourseType::values())],
        ]);
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
            'type' => $course->type->value,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sections(Course $course): array
    {
        $sections = $course->sections->all();
        usort($sections, fn (CourseSection $left, CourseSection $right): int => strnatcasecmp($left->name, $right->name));

        return array_map(fn (CourseSection $section): array => [
            'id' => $section->id,
            'name' => $section->name,
            'schedules' => $section->schedules
                ->sortBy([['day_of_week', 'asc'], ['start_time', 'asc']])
                ->map(fn ($schedule): array => [
                    'id' => $schedule->id,
                    'day_of_week' => (int) $schedule->day_of_week,
                    'start_time' => Week::clock($schedule->start_time),
                    'end_time' => Week::clock($schedule->end_time),
                    'room' => $schedule->room,
                ])
                ->values()
                ->all(),
            'users' => $this->users($section),
        ], $sections);
    }

    /**
     * Students come from the section's enrollments. Teachers come from its timetable, once each.
     *
     * @return list<array{id: int, name: string, role: string, status: string|null}>
     */
    private function users(CourseSection $section): array
    {
        /** @var array<int, array{id: int, name: string, role: string, status: string|null}> $people */
        $people = [];

        foreach ($section->schedules as $schedule) {
            if ($schedule->teacher instanceof User) {
                $people[$schedule->teacher->id] = $this->person($schedule->teacher, 'teacher', null);
            }

            foreach ($schedule->teachers as $teacher) {
                $people[$teacher->id] = $this->person($teacher, 'teacher', null);
            }
        }

        foreach ($section->enrollments as $enrollment) {
            $student = $enrollment->student;

            if (isset($people[$student->id])) {
                continue;
            }

            $people[$student->id] = $this->person($student, 'student', $enrollment->status->value);
        }

        $list = array_values($people);
        usort($list, function (array $left, array $right): int {
            $role = ($left['role'] === 'teacher' ? 0 : 1) <=> ($right['role'] === 'teacher' ? 0 : 1);

            return $role !== 0 ? $role : strnatcasecmp($left['name'], $right['name']);
        });

        return $list;
    }

    /**
     * @return array{id: int, name: string, role: string, status: string|null}
     */
    private function person(User $user, string $role, ?string $status): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $role,
            'status' => $status,
        ];
    }
}
