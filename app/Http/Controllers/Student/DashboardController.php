<?php

namespace App\Http\Controllers\Student;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\EventParticipant;
use App\Models\LessonLog;
use App\Models\Payment;
use App\Models\PrivateLesson;
use App\Models\StudentSubscription;
use App\Services\LessonCalendar;
use App\Support\Directory;
use App\Support\ScheduleMapper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, LessonCalendar $calendar): Response
    {
        $student = $request->user();
        $history = $calendar->forStudent($student);

        $enrollments = Enrollment::query()
            ->where('student_id', $student->id)
            ->with([
                'course:id,title,type',
                'section:id,name',
                'section.schedules.course:id,title,type',
                'section.schedules.section:id,name',
                'section.schedules.teacher:id,name',
                'section.schedules.teachers:id,name',
            ])
            ->get()
            ->map(fn (Enrollment $enrollment): array => [
                'id' => $enrollment->id,
                'status' => $enrollment->status->value,
                'course' => [
                    'title' => $enrollment->course->title,
                    'type' => $enrollment->course->type->value,
                ],
                'section' => [
                    'name' => $enrollment->section->name,
                ],
                'schedules' => ScheduleMapper::withTeacher($enrollment->section->schedules),
            ])
            ->all();

        $subscriptions = StudentSubscription::query()
            ->where('student_id', $student->id)
            ->with('course:id,title')
            ->withSum(['payments as paid_amount' => function ($query): void {
                $query->where('status', PaymentStatus::Paid);
            }], 'amount')
            ->orderByDesc('starts_at')
            ->get()
            ->map(function (StudentSubscription $subscription): array {
                $status = $subscription->status->value;

                if (
                    $status === SubscriptionStatus::Active->value
                    && $subscription->ends_at !== null
                    && $subscription->ends_at->toDateString() < now()->toDateString()
                ) {
                    $status = SubscriptionStatus::Expired->value;
                }

                $paid = $subscription->paidAmount();

                return [
                    'id' => $subscription->id,
                    'title' => $subscription->title,
                    'course' => $subscription->course?->title,
                    'total_amount' => round((float) $subscription->total_amount, 2),
                    'paid_amount' => $paid,
                    'remaining_amount' => $subscription->remainingAmount(),
                    'starts_at' => $subscription->starts_at->toDateString(),
                    'ends_at' => $subscription->ends_at?->toDateString(),
                    'status' => $status,
                ];
            })
            ->all();

        $payments = Payment::query()
            ->where('student_id', $student->id)
            ->orderByDesc('due_date')
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'type' => $payment->type->value,
                'status' => $payment->status->value,
                'due_date' => $payment->due_date?->toDateString(),
            ])
            ->all();

        $lessons = PrivateLesson::query()
            ->where('student_id', $student->id)
            ->with('teacher:id,name')
            ->orderBy('scheduled_at')
            ->get()
            ->filter(fn (PrivateLesson $lesson): bool => $calendar->showsPrivateLesson($lesson))
            ->values()
            ->map(fn (PrivateLesson $lesson): array => [
                'id' => $lesson->id,
                'scheduled_at' => $lesson->scheduled_at->format('Y-m-d H:i'),
                'duration_minutes' => $lesson->duration_minutes,
                'status' => $lesson->status->value,
                'teacher' => $lesson->teacher->name,
            ])
            ->all();

        $logs = LessonLog::query()
            ->where('student_id', $student->id)
            ->with(['teacher:id,name', 'schedule.course:id,title'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (LessonLog $log): array => [
                'id' => $log->id,
                'performance_notes' => $log->performance_notes,
                'test_title' => $log->test_title,
                'test_score' => $log->test_score,
                'teacher' => $log->teacher->name,
                'context' => $log->schedule?->course?->title ?? __('ui.common.private_lesson'),
                'created_at' => $log->created_at?->format('Y-m-d H:i'),
            ])
            ->all();

        $events = EventParticipant::query()
            ->where('user_id', $student->id)
            ->with('event')
            ->get()
            ->map(fn (EventParticipant $participant): array => [
                'id' => $participant->event->id,
                'title' => $participant->event->title,
                'event_date' => $participant->event->event_date->format('Y-m-d H:i'),
                'location' => $participant->event->location,
                'role_in_event' => $participant->role_in_event->value,
            ])
            ->all();

        return Inertia::render('Student/Dashboard', [
            'profile' => [
                'name' => $student->name,
                'email' => $student->email,
                'phone' => $student->phone,
            ],
            'enrollments' => $enrollments,
            'subscriptions' => $subscriptions,
            'payments' => $payments,
            'privateLessons' => $lessons,
            'pastLessons' => $history['past'],
            'upcomingLessons' => $history['upcoming'],
            'lessonLogs' => $logs,
            'events' => $events,
            'teachers' => Directory::options(UserRole::Teacher),
        ]);
    }
}
