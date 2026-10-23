<?php

namespace Database\Seeders;

use App\Models\BillingPolicy;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Expense;
use App\Models\FlexSuggestion;
use App\Models\LessonLog;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PrivateLesson;
use App\Models\StudentSubscription;
use App\Models\TeacherPayroll;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = $this->person('Ελένη Οικονόμου', 'owner@sxoligrid.test', 'owner', '6900000001', true);
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@sxoligrid.test'],
            [
                'name' => 'Νίκος Αθανασίου',
                'password' => 'password',
                'role' => 'admin',
                'phone' => '6900000002',
                'can_view_finances' => false,
                'email_verified_at' => now(),
            ],
        );
        $maria = $this->person('Μαρία Παπαδοπούλου', 'teacher@sxoligrid.test', 'teacher', '6900000003');
        $kostas = $this->person('Κώστας Δημητρίου', 'teacher2@sxoligrid.test', 'teacher', '6900000004');
        $giannis = $this->person('Γιάννης Αλεξίου', 'student@sxoligrid.test', 'student', '6900000005');
        $anna = $this->person('Άννα Βασιλείου', 'student2@sxoligrid.test', 'student', '6900000006');

        $math = Course::query()->create([
            'title' => 'Μαθηματικά Λυκείου',
            'description' => 'Τμήμα προετοιμασίας για πανελλαδικές.',
            'price' => 120,
            'prices' => ['monthly' => 120],
            'type' => 'group',
            'billing_methods' => ['monthly'],
        ]);
        $english = Course::query()->create([
            'title' => 'English B2',
            'description' => 'Προετοιμασία για Lower.',
            'price' => 70,
            'prices' => ['monthly' => 70, 'quarterly' => 190, 'semester' => 360],
            'type' => 'group',
            'billing_methods' => ['monthly', 'quarterly', 'semester'],
        ]);
        $salsa = Course::query()->create([
            'title' => 'Salsa',
            'description' => 'Ομαδικό τμήμα αρχαρίων και μέσων.',
            'price' => 50,
            'prices' => ['monthly' => 50, 'drop_in' => 12],
            'type' => 'group',
            'billing_methods' => ['monthly', 'drop_in'],
        ]);
        $piano = Course::query()->create([
            'title' => 'Πιάνο',
            'description' => 'Ατομικό μάθημα μουσικής.',
            'price' => 40,
            'prices' => ['drop_in' => 40],
            'type' => 'private',
            'billing_methods' => ['drop_in'],
        ]);

        ClassSchedule::query()->create([
            'course_id' => $math->id,
            'teacher_id' => $kostas->id,
            'day_of_week' => 1,
            'start_time' => '17:00',
            'end_time' => '19:00',
            'room' => 'Α1',
        ]);
        $salsaEarly = ClassSchedule::query()->create([
            'course_id' => $salsa->id,
            'teacher_id' => $maria->id,
            'day_of_week' => 2,
            'start_time' => '16:00',
            'end_time' => '17:00',
            'room' => 'Studio 1',
        ]);
        ClassSchedule::query()->create([
            'course_id' => $salsa->id,
            'teacher_id' => $maria->id,
            'day_of_week' => 2,
            'start_time' => '18:00',
            'end_time' => '19:30',
            'room' => 'Studio 1',
        ]);
        ClassSchedule::query()->create([
            'course_id' => $piano->id,
            'teacher_id' => $kostas->id,
            'day_of_week' => 3,
            'start_time' => '16:00',
            'end_time' => '16:45',
            'room' => 'Studio 2',
        ]);
        ClassSchedule::query()->create([
            'course_id' => $english->id,
            'teacher_id' => $maria->id,
            'day_of_week' => 4,
            'start_time' => '17:00',
            'end_time' => '18:30',
            'room' => 'Α2',
        ]);

        Enrollment::query()->create(['student_id' => $giannis->id, 'course_id' => $math->id, 'status' => 'active', 'billing_method' => 'monthly']);
        Enrollment::query()->create(['student_id' => $giannis->id, 'course_id' => $english->id, 'status' => 'active', 'billing_method' => 'monthly']);
        Enrollment::query()->create(['student_id' => $anna->id, 'course_id' => $salsa->id, 'status' => 'active', 'billing_method' => 'monthly']);
        Enrollment::query()->create(['student_id' => $anna->id, 'course_id' => $piano->id, 'status' => 'active', 'billing_method' => 'drop_in']);

        $policy = BillingPolicy::current();
        if ($policy->tiers() === []) {
            $policy->update([
                'multi_course_discounts' => [
                    ['min_courses' => 2, 'percent' => 10],
                    ['min_courses' => 3, 'percent' => 15],
                ],
            ]);
        }

        $unlimited = Package::query()->create([
            'title' => 'Μηνιαίο Unlimited Χορού',
            'price' => 80,
            'duration_days' => 30,
            'sessions_count' => null,
        ]);
        $musicPack = Package::query()->create([
            'title' => 'Πακέτο 10 Μαθημάτων Μουσικής',
            'price' => 150,
            'duration_days' => 90,
            'sessions_count' => 10,
        ]);
        $tutoring = Package::query()->create([
            'title' => 'Μηνιαία φροντιστηρίου',
            'price' => 120,
            'duration_days' => 30,
            'sessions_count' => null,
        ]);

        StudentSubscription::query()->create([
            'student_id' => $anna->id,
            'package_id' => $unlimited->id,
            'remaining_sessions' => null,
            'starts_at' => '2026-10-01',
            'expires_at' => '2026-10-31',
            'status' => 'active',
        ]);
        StudentSubscription::query()->create([
            'student_id' => $anna->id,
            'package_id' => $musicPack->id,
            'remaining_sessions' => 8,
            'starts_at' => '2026-10-01',
            'expires_at' => '2026-12-30',
            'status' => 'active',
        ]);
        StudentSubscription::query()->create([
            'student_id' => $giannis->id,
            'package_id' => $tutoring->id,
            'remaining_sessions' => null,
            'starts_at' => '2026-10-01',
            'expires_at' => '2026-10-31',
            'status' => 'active',
        ]);

        Payment::query()->create([
            'student_id' => $giannis->id,
            'course_id' => $math->id,
            'amount' => 120,
            'type' => 'monthly_subscription',
            'status' => 'paid',
            'due_date' => '2026-10-05',
            'paid_at' => '2026-10-05 10:00:00',
        ]);
        Payment::query()->create([
            'student_id' => $anna->id,
            'course_id' => $piano->id,
            'amount' => 150,
            'type' => 'offer_package',
            'status' => 'paid',
            'due_date' => '2026-10-02',
            'paid_at' => '2026-10-02 11:00:00',
        ]);
        Payment::query()->create([
            'student_id' => $giannis->id,
            'course_id' => $english->id,
            'amount' => 70,
            'type' => 'monthly_subscription',
            'status' => 'paid',
            'due_date' => '2026-10-08',
            'paid_at' => '2026-10-08 09:30:00',
        ]);
        Payment::query()->create([
            'student_id' => $anna->id,
            'course_id' => null,
            'amount' => 15,
            'type' => 'drop_in_ticket',
            'status' => 'pending',
            'due_date' => '2026-10-20',
            'paid_at' => null,
        ]);
        Payment::query()->create([
            'student_id' => $giannis->id,
            'course_id' => null,
            'amount' => 40,
            'type' => 'private_session',
            'status' => 'overdue',
            'due_date' => '2026-10-10',
            'paid_at' => null,
        ]);

        PrivateLesson::query()->create([
            'student_id' => $giannis->id,
            'teacher_id' => $kostas->id,
            'scheduled_at' => '2026-10-29 17:00:00',
            'duration_minutes' => 45,
            'status' => 'requested',
        ]);

        LessonLog::query()->create([
            'schedule_id' => $salsaEarly->id,
            'student_id' => $anna->id,
            'teacher_id' => $maria->id,
            'performance_notes' => 'Εξαιρετική τεχνική στο δεξί χέρι, πρόσεξε τη στάση του σώματος.',
            'test_title' => 'Πρόοδος 3ης εβδομάδας',
            'test_score' => '9/10',
        ]);

        $showcase = Event::query()->create([
            'title' => 'Χριστουγεννιάτικο Showcase',
            'description' => 'Παράσταση χορού και μουσικής για γονείς και φίλους.',
            'event_date' => '2026-12-20 19:00:00',
            'location' => 'Κεντρική αίθουσα',
        ]);
        EventParticipant::query()->create([
            'event_id' => $showcase->id,
            'user_id' => $anna->id,
            'role_in_event' => 'performer',
        ]);
        EventParticipant::query()->create([
            'event_id' => $showcase->id,
            'user_id' => $maria->id,
            'role_in_event' => 'instructor',
        ]);

        Expense::query()->create([
            'title' => 'Ενοίκιο Οκτωβρίου',
            'amount' => 200,
            'category' => 'rent',
            'expense_date' => '2026-10-01',
        ]);
        Expense::query()->create([
            'title' => 'Αναλώσιμα στούντιο',
            'amount' => 30,
            'category' => 'supplies',
            'expense_date' => '2026-10-12',
        ]);

        TeacherPayroll::query()->create([
            'teacher_id' => $maria->id,
            'type' => 'monthly_salary',
            'base_amount' => 80,
            'payment_period' => '2026-10',
            'status' => 'paid',
        ]);
        TeacherPayroll::query()->create([
            'teacher_id' => $kostas->id,
            'type' => 'hourly_rate',
            'base_amount' => 100,
            'payment_period' => '2026-10',
            'status' => 'pending',
        ]);

        FlexSuggestion::query()->create([
            'target_user_id' => $admin->id,
            'title' => 'Κενό στο πρόγραμμα',
            'content' => 'Κενό 1 ώρας την Τρίτη (17:00–18:00). Προτείνεται μετακίνηση τμήματος Salsa.',
            'status' => 'unread',
        ]);
        FlexSuggestion::query()->create([
            'target_user_id' => $owner->id,
            'title' => 'Κενό στο πρόγραμμα',
            'content' => 'Κενό 1 ώρας την Τρίτη (17:00–18:00). Προτείνεται μετακίνηση τμήματος Salsa.',
            'status' => 'unread',
        ]);
    }

    private function person(string $name, string $email, string $role, string $phone, bool $finances = false): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'phone' => $phone,
            'can_view_finances' => $finances,
            'email_verified_at' => now(),
        ]);
    }
}
