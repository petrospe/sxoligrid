<?php

use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseSectionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\FlexSuggestionController as AdminFlexSuggestionController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PrivateLessonController as AdminPrivateLessonController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Owner\FinanceController;
use App\Http\Controllers\PublicScheduleController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\PrivateLessonController as StudentPrivateLessonController;
use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\EventController as TeacherEventController;
use App\Http\Controllers\Teacher\FlexSuggestionController as TeacherFlexSuggestionController;
use App\Http\Controllers\Teacher\PrivateLessonController as TeacherPrivateLessonController;
use App\Http\Controllers\Teacher\StudentController as TeacherStudentController;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Expense;
use App\Models\FlexSuggestion;
use App\Models\Payment;
use App\Models\PrivateLesson;
use App\Models\TeacherPayroll;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicScheduleController::class, 'index'])->name('schedule.public');
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:owner,admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
        Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
        Route::get('courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
        Route::put('courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
        Route::post('courses/{course}/sections', [CourseSectionController::class, 'store'])->name('courses.sections.store');
        Route::delete('courses/{course}/sections/{section}', [CourseSectionController::class, 'destroy'])->name('courses.sections.destroy');

        Route::get('schedules', [ScheduleController::class, 'index'])->name('schedules.index');
        Route::post('schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::get('schedules/{schedule}/edit', [ScheduleController::class, 'edit'])->name('schedules.edit');
        Route::put('schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

        Route::get('enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
        Route::post('enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::patch('enrollments/{enrollment}', [EnrollmentController::class, 'update'])->name('enrollments.update');
        Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');

        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');

        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::patch('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');

        Route::get('events', [AdminEventController::class, 'index'])->name('events.index');
        Route::post('events', [AdminEventController::class, 'store'])->name('events.store');
        Route::get('events/{event}', [AdminEventController::class, 'show'])->name('events.show');
        Route::delete('events/{event}', [AdminEventController::class, 'destroy'])->name('events.destroy');
        Route::post('events/{event}/participants', [AdminEventController::class, 'addParticipant'])->name('events.participants.store');
        Route::delete('events/{event}/participants/{participant}', [AdminEventController::class, 'removeParticipant'])->name('events.participants.destroy');

        Route::get('private-lessons', [AdminPrivateLessonController::class, 'index'])->name('private-lessons.index');
        Route::post('private-lessons', [AdminPrivateLessonController::class, 'store'])->name('private-lessons.store');
        Route::patch('private-lessons/{privateLesson}', [AdminPrivateLessonController::class, 'update'])->name('private-lessons.update');

        Route::get('flex-suggestions', [AdminFlexSuggestionController::class, 'index'])->name('flex-suggestions.index');
        Route::post('flex-suggestions/scan', [AdminFlexSuggestionController::class, 'scan'])->name('flex-suggestions.scan');
        Route::post('flex-suggestions', [AdminFlexSuggestionController::class, 'store'])->name('flex-suggestions.store');
        Route::patch('flex-suggestions/{flexSuggestion}', [AdminFlexSuggestionController::class, 'update'])->name('flex-suggestions.update');

        Route::middleware('role:admin')->group(function (): void {
            Route::get('settings', [SettingsController::class, 'show'])->name('settings.show');
            Route::post('settings/landing', [SettingsController::class, 'updateLanding'])->name('settings.landing.update');
            Route::get('settings/backup', [SettingsController::class, 'backup'])->name('settings.backup');
            Route::post('settings/restore', [SettingsController::class, 'restore'])->name('settings.restore');
            Route::post('settings/demo', [SettingsController::class, 'installDemo'])->name('settings.demo.install');
            Route::delete('settings/demo', [SettingsController::class, 'removeDemo'])->name('settings.demo.remove');
        });
    });

    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function (): void {
        Route::get('/', [TeacherDashboardController::class, 'index'])->name('dashboard');
        Route::get('schedules/{schedule}/attendance', [AttendanceController::class, 'edit'])->name('attendance.edit');
        Route::post('schedules/{schedule}/attendance', [AttendanceController::class, 'update'])->name('attendance.update');
        Route::get('students', [TeacherStudentController::class, 'index'])->name('students.index');
        Route::get('flex-suggestions', [TeacherFlexSuggestionController::class, 'index'])->name('flex-suggestions.index');
        Route::patch('flex-suggestions/{flexSuggestion}', [TeacherFlexSuggestionController::class, 'update'])->name('flex-suggestions.update');
        Route::get('events', [TeacherEventController::class, 'index'])->name('events.index');
        Route::get('events/{event}', [TeacherEventController::class, 'show'])->name('events.show');
        Route::post('events/{event}/participants', [TeacherEventController::class, 'addParticipant'])->name('events.participants.store');
        Route::delete('events/{event}/participants/{participant}', [TeacherEventController::class, 'removeParticipant'])->name('events.participants.destroy');
        Route::patch('private-lessons/{privateLesson}', [TeacherPrivateLessonController::class, 'update'])->name('private-lessons.update');
    });

    Route::middleware('role:student')->prefix('student')->name('student.')->group(function (): void {
        Route::get('/', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::post('private-lessons', [StudentPrivateLessonController::class, 'store'])->name('private-lessons.store');
    });

    Route::middleware('finances')->prefix('owner')->name('owner.')->group(function (): void {
        Route::get('finances', [FinanceController::class, 'index'])->name('finances.index');
        Route::post('expenses', [FinanceController::class, 'storeExpense'])->name('expenses.store');
        Route::delete('expenses/{expense}', [FinanceController::class, 'destroyExpense'])->name('expenses.destroy');
        Route::post('payrolls', [FinanceController::class, 'storePayroll'])->name('payrolls.store');
        Route::patch('payrolls/{payroll}', [FinanceController::class, 'updatePayroll'])->name('payrolls.update');
        Route::delete('payrolls/{payroll}', [FinanceController::class, 'destroyPayroll'])->name('payrolls.destroy');
    });
});

Route::bind('user', fn (string $value): User => User::query()->findOrFail($value));
Route::bind('schedule', fn (string $value): ClassSchedule => ClassSchedule::query()->findOrFail($value));
Route::bind('enrollment', fn (string $value): Enrollment => Enrollment::query()->findOrFail($value));
Route::bind('payment', fn (string $value): Payment => Payment::query()->findOrFail($value));
Route::bind('event', fn (string $value): Event => Event::query()->findOrFail($value));
Route::bind('participant', fn (string $value): EventParticipant => EventParticipant::query()->findOrFail($value));
Route::bind('privateLesson', fn (string $value): PrivateLesson => PrivateLesson::query()->findOrFail($value));
Route::bind('flexSuggestion', fn (string $value): FlexSuggestion => FlexSuggestion::query()->findOrFail($value));
Route::bind('expense', fn (string $value): Expense => Expense::query()->findOrFail($value));
Route::bind('payroll', fn (string $value): TeacherPayroll => TeacherPayroll::query()->findOrFail($value));
