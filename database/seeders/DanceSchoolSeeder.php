<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Expense;
use App\Models\FlexSuggestion;
use App\Models\LessonLog;
use App\Models\Payment;
use App\Models\StudentSubscription;
use App\Models\TeacherPayroll;
use App\Models\User;
use Illuminate\Database\Seeder;

class DanceSchoolSeeder extends Seeder
{
    use BuildsExample;

    /**
     * @return array{emails: list<string>, courses: list<string>, subscriptions: list<string>, expenses: list<string>, events: list<string>, flex: list<string>}
     */
    public static function markers(): array
    {
        return [
            'emails' => [
                'dance-owner@sxoligrid.test',
                'dance-teacher@sxoligrid.test',
                'dance-teacher2@sxoligrid.test',
                'dance-student@sxoligrid.test',
                'dance-student2@sxoligrid.test',
                'dance-visitor@sxoligrid.test',
            ],
            'courses' => ['Salsa advanced', 'Bachata'],
            'subscriptions' => ['Salsa advanced 2026-2027'],
            'expenses' => ['Ενοίκιο στούντιο χορού'],
            'events' => ['Χειμερινό showcase χορού'],
            'flex' => ['Κενό στην αίθουσα χορού την Τετάρτη πριν τις 21:00.'],
        ];
    }

    public function run(): void
    {
        $this->person('Νίκη Ρυθμού', 'dance-owner@sxoligrid.test', 'owner', '6910000001', true);
        $maria = $this->person('Μαρία Χορού', 'dance-teacher@sxoligrid.test', 'teacher', '6910000002');
        $kostas = $this->person('Κώστας Χορού', 'dance-teacher2@sxoligrid.test', 'teacher', '6910000003');
        $anna = $this->person('Άννα Σάλσα', 'dance-student@sxoligrid.test', 'student', '6910000004');
        $giannis = $this->person('Γιάννης Μπατσάτα', 'dance-student2@sxoligrid.test', 'student', '6910000005');
        $visitor = $this->person('Ελένη Επισκέπτρια', 'dance-visitor@sxoligrid.test', 'student', '6910000006');

        $salsa = $this->course('Salsa advanced', 'Προχωρημένο ομαδικό. Τα τμήματα έχουν διαφορετικούς μαθητές, καθηγητές και ώρες.', 'group');
        $monday = $this->meet($salsa, 'Δευτέρα 19:00', $maria, 1, '19:00', '20:30', 'Studio 1');
        $wednesday = $this->meet($salsa, 'Τετάρτη 21:00', $kostas, 3, '21:00', '22:30', 'Studio 1');
        $this->enroll($anna, $monday);
        $this->enroll($giannis, $wednesday);

        $bachata = $this->course('Bachata', 'Ομαδικό αρχαρίων.', 'group');
        $this->enroll($anna, $this->meet($bachata, 'Τρίτη 20:00', $maria, 2, '20:00', '21:00', 'Studio 2'));

        $year = StudentSubscription::query()->create([
            'student_id' => $anna->id,
            'course_id' => $salsa->id,
            'title' => 'Salsa advanced 2026-2027',
            'total_amount' => 900,
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-06-30',
            'status' => 'active',
        ]);
        Payment::query()->create([
            'student_id' => $anna->id,
            'subscription_id' => $year->id,
            'course_id' => $salsa->id,
            'amount' => 150,
            'type' => 'installment',
            'status' => 'paid',
            'due_date' => '2026-10-05',
            'paid_at' => '2026-10-05 18:00:00',
        ]);
        Payment::query()->create([
            'student_id' => $visitor->id,
            'subscription_id' => null,
            'course_id' => $salsa->id,
            'amount' => 15,
            'type' => 'drop_in',
            'status' => 'paid',
            'due_date' => '2026-10-13',
            'paid_at' => '2026-10-13 19:00:00',
        ]);

        Attendance::query()->create([
            'class_schedule_id' => $monday->id,
            'student_id' => $anna->id,
            'attended_on' => '2026-10-13',
        ]);
        LessonLog::query()->create([
            'schedule_id' => $monday->id,
            'student_id' => $anna->id,
            'teacher_id' => $maria->id,
            'happened_on' => '2026-10-13',
            'performance_notes' => 'Καλή περιστροφή, πρόσεξε το βάρος στο αριστερό πόδι.',
            'test_title' => 'Συνδυασμός 3',
            'test_score' => '8/10',
        ]);

        $showcase = Event::query()->create([
            'title' => 'Χειμερινό showcase χορού',
            'description' => 'Παράσταση των τμημάτων salsa και bachata.',
            'event_date' => '2026-12-18 20:00:00',
            'location' => 'Studio 1',
        ]);
        EventParticipant::query()->create(['event_id' => $showcase->id, 'user_id' => $anna->id, 'role_in_event' => 'performer']);
        EventParticipant::query()->create(['event_id' => $showcase->id, 'user_id' => $maria->id, 'role_in_event' => 'instructor']);

        Expense::query()->create([
            'title' => 'Ενοίκιο στούντιο χορού',
            'amount' => 700,
            'category' => 'rent',
            'expense_date' => '2026-10-01',
        ]);
        TeacherPayroll::query()->create([
            'teacher_id' => $maria->id,
            'type' => 'hourly_rate',
            'base_amount' => 120,
            'payment_period' => '2026-10',
            'status' => 'pending',
        ]);
        $admin = User::query()->where('email', 'admin@sxoligrid.test')->first();
        if ($admin instanceof User) {
            FlexSuggestion::query()->create([
                'target_user_id' => $admin->id,
                'title' => 'Κενό στο πρόγραμμα',
                'content' => 'Κενό στην αίθουσα χορού την Τετάρτη πριν τις 21:00.',
                'status' => 'unread',
            ]);
        }
    }
}
