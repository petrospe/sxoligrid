<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->index();
            $table->string('phone')->nullable();
            $table->boolean('can_view_finances')->default(false);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room');
            $table->timestamps();

            $table->index(['day_of_week', 'start_time']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['student_id', 'course_id']);
        });

        Schema::create('subscriptions_and_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('type');
            $table->string('status');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index('paid_at');
        });

        Schema::create('private_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('flex_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('status')->default('unread');
            $table->timestamps();

            $table->index(['target_user_id', 'status']);
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('duration_days')->nullable();
            $table->unsignedInteger('sessions_count')->nullable();
            $table->timestamps();
        });

        Schema::create('student_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('remaining_sessions')->nullable();
            $table->date('starts_at');
            $table->date('expires_at')->nullable();
            $table->string('status');
            $table->timestamps();

            $table->index(['student_id', 'status']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('event_date');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('role_in_event');
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });

        Schema::create('lesson_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->nullable()->constrained('class_schedules')->nullOnDelete();
            $table->foreignId('private_lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->text('performance_notes')->nullable();
            $table->string('test_title')->nullable();
            $table->string('test_score')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->string('category');
            $table->date('expense_date');
            $table->timestamps();

            $table->index('expense_date');
        });

        Schema::create('teacher_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->string('type');
            $table->decimal('base_amount', 10, 2);
            $table->string('payment_period');
            $table->string('status');
            $table->timestamps();

            $table->index(['payment_period', 'status']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->date('attended_on');
            $table->timestamps();

            $table->unique(['class_schedule_id', 'student_id', 'attended_on'], 'attendances_once_per_day');
        });

        DB::table('users')->insert([
            'name' => 'Νίκος Αθανασίου',
            'email' => 'admin@sxoligrid.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '6900000002',
            'can_view_finances' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('teacher_payrolls');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('lesson_logs');
        Schema::dropIfExists('event_participants');
        Schema::dropIfExists('events');
        Schema::dropIfExists('student_subscriptions');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('flex_suggestions');
        Schema::dropIfExists('private_lessons');
        Schema::dropIfExists('subscriptions_and_payments');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('courses');

        DB::table('users')->where('email', 'admin@sxoligrid.test')->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'can_view_finances']);
        });
    }
};
