<?php

namespace Database\Seeders;

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Expense;
use App\Models\FlexSuggestion;
use App\Models\LessonLog;
use App\Models\Payment;
use App\Models\PrivateLesson;
use App\Models\StudentSubscription;
use App\Models\TeacherPayroll;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Records this example owns, so it can be removed without touching other data.
     *
     * @return array{emails: list<string>, courses: list<string>, subscriptions: list<string>, expenses: list<string>, events: list<string>, flex: list<string>}
     */
    public static function markers(): array
    {
        return [
            'emails' => [
                'owner@sxoligrid.test',
                'teacher@sxoligrid.test',
                'teacher2@sxoligrid.test',
                'student@sxoligrid.test',
                'student2@sxoligrid.test',
            ],
            'courses' => [
                'Μαθηματικά Λυκείου',
                'English B2',
                'Salsa',
                'Πιάνο',
            ],
            'subscriptions' => [
                'English B2 2026-2027',
                'Συνδρομή γυμναστηρίου',
            ],
            'expenses' => [
                'Ενοίκιο Οκτωβρίου',
                'Αναλώσιμα στούντιο',
            ],
            'events' => [
                'Χριστουγεννιάτικο Showcase',
            ],
            'flex' => [
                'Κενό 1 ώρας την Τρίτη (17:00–18:00). Προτείνεται μετακίνηση τμήματος Salsa.',
            ],
        ];
    }

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
            'type' => 'group',
        ]);
        $english = Course::query()->create([
            'title' => 'English B2',
            'description' => 'Προετοιμασία για Lower.',
            'type' => 'group',
        ]);
        $salsa = Course::query()->create([
            'title' => 'Salsa',
            'description' => 'Ομαδικό τμήμα αρχαρίων και μέσων.',
            'type' => 'group',
        ]);
        $piano = Course::query()->create([
            'title' => 'Πιάνο',
            'description' => 'Ατομικό μάθημα μουσικής.',
            'type' => 'private',
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

        Enrollment::query()->create(['student_id' => $giannis->id, 'course_id' => $math->id, 'status' => 'active']);
        Enrollment::query()->create(['student_id' => $giannis->id, 'course_id' => $english->id, 'status' => 'active']);
        Enrollment::query()->create(['student_id' => $anna->id, 'course_id' => $salsa->id, 'status' => 'active']);
        Enrollment::query()->create(['student_id' => $anna->id, 'course_id' => $piano->id, 'status' => 'active']);

        $englishYear = StudentSubscription::query()->create([
            'student_id' => $giannis->id,
            'course_id' => $english->id,
            'title' => 'English B2 2026-2027',
            'total_amount' => 1800,
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-05-31',
            'status' => 'active',
        ]);
        $gym = StudentSubscription::query()->create([
            'student_id' => $anna->id,
            'course_id' => null,
            'title' => 'Συνδρομή γυμναστηρίου',
            'total_amount' => 500,
            'starts_at' => '2026-10-01',
            'ends_at' => '2027-09-30',
            'status' => 'active',
        ]);

        Payment::query()->create([
            'student_id' => $giannis->id,
            'subscription_id' => $englishYear->id,
            'course_id' => $english->id,
            'amount' => 200,
            'type' => 'installment',
            'status' => 'paid',
            'due_date' => '2026-10-05',
            'paid_at' => '2026-10-05 10:00:00',
        ]);
        Payment::query()->create([
            'student_id' => $giannis->id,
            'subscription_id' => $englishYear->id,
            'course_id' => $english->id,
            'amount' => 150,
            'type' => 'installment',
            'status' => 'pending',
            'due_date' => '2026-11-05',
            'paid_at' => null,
        ]);
        Payment::query()->create([
            'student_id' => $anna->id,
            'subscription_id' => $gym->id,
            'course_id' => null,
            'amount' => 100,
            'type' => 'installment',
            'status' => 'paid',
            'due_date' => '2026-10-02',
            'paid_at' => '2026-10-02 11:00:00',
        ]);
        Payment::query()->create([
            'student_id' => $anna->id,
            'subscription_id' => null,
            'course_id' => $salsa->id,
            'amount' => 12,
            'type' => 'drop_in',
            'status' => 'pending',
            'due_date' => '2026-10-20',
            'paid_at' => null,
        ]);
        Payment::query()->create([
            'student_id' => $giannis->id,
            'subscription_id' => null,
            'course_id' => $piano->id,
            'amount' => 40,
            'type' => 'drop_in',
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
