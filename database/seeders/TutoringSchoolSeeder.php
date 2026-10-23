<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\LessonLog;
use App\Models\Payment;
use App\Models\StudentSubscription;
use Illuminate\Database\Seeder;

class TutoringSchoolSeeder extends Seeder
{
    use BuildsExample;

    /**
     * @return array{emails: list<string>, courses: list<string>, subscriptions: list<string>, expenses: list<string>, events: list<string>, flex: list<string>}
     */
    public static function markers(): array
    {
        return [
            'emails' => [
                'tutor-owner@sxoligrid.test',
                'tutor-teacher@sxoligrid.test',
                'tutor-teacher2@sxoligrid.test',
                'tutor-student@sxoligrid.test',
                'tutor-student2@sxoligrid.test',
            ],
            'courses' => ['Αγγλικά D senior', 'Μαθηματικά Γ\' Λυκείου'],
            'subscriptions' => ['Αγγλικά D senior 2026-2027'],
            'expenses' => ['Ενοίκιο φροντιστηρίου'],
            'events' => [],
            'flex' => [],
        ];
    }

    public function run(): void
    {
        $this->person('Ελένη Φροντιστηρίου', 'tutor-owner@sxoligrid.test', 'owner', '6940000001', true);
        $englishTeacher = $this->person('Μαρία Αγγλικών', 'tutor-teacher@sxoligrid.test', 'teacher', '6940000002');
        $mathTeacher = $this->person('Κώστας Μαθηματικών', 'tutor-teacher2@sxoligrid.test', 'teacher', '6940000003');
        $giannis = $this->person('Γιάννης Senior', 'tutor-student@sxoligrid.test', 'student', '6940000004');
        $anna = $this->person('Άννα Senior', 'tutor-student2@sxoligrid.test', 'student', '6940000005');

        $english = $this->course('Αγγλικά D senior', 'Προετοιμασία για το επίπεδο D senior. Δύο τμήματα, διαφορετικές μέρες.', 'group');
        $monday = $this->meet($english, 'Δευτέρα 17:00', $englishTeacher, 1, '17:00', '18:30', 'Α1');
        $wednesday = $this->meet($english, 'Τετάρτη 17:00', $mathTeacher, 3, '17:00', '18:30', 'Α2');
        $this->enroll($giannis, $monday);
        $this->enroll($anna, $wednesday);

        $math = $this->course('Μαθηματικά Γ\' Λυκείου', 'Προετοιμασία για πανελλαδικές.', 'group');
        $this->enroll($giannis, $this->meet($math, 'Τρίτη 17:00', $mathTeacher, 2, '17:00', '19:00', 'Α1'));

        $year = StudentSubscription::query()->create([
            'student_id' => $giannis->id,
            'course_id' => $english->id,
            'title' => 'Αγγλικά D senior 2026-2027',
            'total_amount' => 1800,
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-05-31',
            'status' => 'active',
        ]);
        Payment::query()->create([
            'student_id' => $giannis->id,
            'subscription_id' => $year->id,
            'course_id' => $english->id,
            'amount' => 200,
            'type' => 'installment',
            'status' => 'paid',
            'due_date' => '2026-10-05',
            'paid_at' => '2026-10-05 10:00:00',
        ]);
        Payment::query()->create([
            'student_id' => $giannis->id,
            'subscription_id' => $year->id,
            'course_id' => $english->id,
            'amount' => 150,
            'type' => 'installment',
            'status' => 'pending',
            'due_date' => '2026-11-05',
            'paid_at' => null,
        ]);

        Attendance::query()->create([
            'class_schedule_id' => $monday->id,
            'student_id' => $giannis->id,
            'attended_on' => '2026-10-12',
        ]);
        LessonLog::query()->create([
            'schedule_id' => $monday->id,
            'student_id' => $giannis->id,
            'teacher_id' => $englishTeacher->id,
            'happened_on' => '2026-10-12',
            'performance_notes' => 'Καλή κατανόηση κειμένου. Θέλει εξάσκηση στα writing tasks.',
            'test_title' => 'Quiz Unit 3',
            'test_score' => '16/20',
        ]);
        Expense::query()->create([
            'title' => 'Ενοίκιο φροντιστηρίου',
            'amount' => 450,
            'category' => 'rent',
            'expense_date' => '2026-10-01',
        ]);
    }
}
