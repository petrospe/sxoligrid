<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\User;
use App\Support\Directory;
use App\Support\ScheduleMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(): Response
    {
        $schedules = ClassSchedule::query()
            ->with(['course:id,title,type', 'teacher:id,name', 'teachers:id,name'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('Admin/Schedules/Index', [
            'schedules' => ScheduleMapper::withTeacher($schedules),
            'courses' => $this->courses(),
            'teachers' => Directory::options(UserRole::Teacher),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->saveSchedule(new ClassSchedule, $this->validated($request));

        return redirect()->route('admin.schedules.index')->with('success', __('ui.flash.schedule_saved'));
    }

    public function edit(ClassSchedule $schedule): Response
    {
        $schedule->load(['course:id,title,type', 'teacher:id,name', 'teachers:id,name']);

        return Inertia::render('Admin/Schedules/Edit', [
            'schedule' => ScheduleMapper::withTeacher(collect([$schedule]))[0],
            'courses' => $this->courses(),
            'teachers' => Directory::options(UserRole::Teacher),
        ]);
    }

    public function update(Request $request, ClassSchedule $schedule): RedirectResponse
    {
        $this->saveSchedule($schedule, $this->validated($request));

        return redirect()->route('admin.schedules.index')->with('success', __('ui.flash.schedule_updated'));
    }

    public function destroy(ClassSchedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return redirect()->route('admin.schedules.index')->with('success', __('ui.flash.schedule_deleted'));
    }

    /**
     * @return list<array{id: int, title: string}>
     */
    private function courses(): array
    {
        return Course::query()->orderBy('title')->get(['id', 'title'])->map(fn (Course $course): array => [
            'id' => $course->id,
            'title' => $course->title,
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'teacher_ids' => ['present', 'array'],
            'teacher_ids.*' => ['integer', 'distinct'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'room' => ['required', 'string', 'max:100'],
            'visible_on_landing' => ['required', 'boolean'],
        ]);

        if ($data['end_time'] <= $data['start_time']) {
            throw ValidationException::withMessages([
                'end_time' => __('ui.errors.end_after_start'),
            ]);
        }

        $ids = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $data['teacher_ids'])));

        if ($ids !== []) {
            $teachers = User::query()->whereIn('id', $ids)->get();

            if ($teachers->count() !== count($ids) || $teachers->contains(fn (User $user): bool => ! $user->isTeacher())) {
                throw ValidationException::withMessages([
                    'teacher_ids' => __('ui.errors.choose_teacher'),
                ]);
            }
        }

        $data['teacher_ids'] = $ids;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveSchedule(ClassSchedule $schedule, array $data): void
    {
        /** @var list<int> $ids */
        $ids = $data['teacher_ids'];
        unset($data['teacher_ids']);
        $data['teacher_id'] = $ids[0] ?? null;
        $schedule->fill($data);
        $schedule->save();
        $schedule->teachers()->sync($ids);
    }
}
