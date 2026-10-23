<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Expense;
use App\Models\LessonLog;
use App\Models\Payment;
use App\Models\PrivateLesson;
use App\Models\StudentSubscription;
use Illuminate\Database\Seeder;

class ConservatorySeeder extends Seeder
{
    use BuildsExample;

    /**
     * @return array{emails: list<string>, courses: list<string>, subscriptions: list<string>, expenses: list<string>, events: list<string>, flex: list<string>}
     */
    public static function markers(): array
    {
        return [
            'emails' => [
                'music-owner@sxoligrid.test',
                'music-teacher@sxoligrid.test',
                'music-teacher2@sxoligrid.test',
                'music-student@sxoligrid.test',
                'music-student2@sxoligrid.test',
            ],
            'courses' => ['Πιάνο ωδείου', 'Θεωρία της μουσικής'],
            'subscriptions' => ['Πιάνο 2026-2027'],
            'expenses' => ['Κούρδισμα πιάνου'],
            'events' => ['Εαρινό ρεσιτάλ'],
            'flex' => [],
        ];
    }

    public function run(): void
    {
        $this->person('Σοφία Ωδείου', 'music-owner@sxoligrid.test', 'owner', '6930000001', true);
        $pianoTeacher = $this->person('Αλέξης Πιάνου', 'music-teacher@sxoligrid.test', 'teacher', '6930000002');
        $theoryTeacher = $this->person('Δήμητρα Θεωρίας', 'music-teacher2@sxoligrid.test', 'teacher', '6930000003');
        $sofia = $this->person('Σοφία Μαθήτρια', 'music-student@sxoligrid.test', 'student', '6930000004');
        $nikos = $this->person('Νίκος Σολφέζ', 'music-student2@sxoligrid.test', 'student', '6930000005');

        $piano = $this->course('Πιάνο ωδείου', 'Ατομικό μάθημα.', 'private');
        $lesson = $this->meet($piano, 'Τετάρτη 17:00', $pianoTeacher, 3, '17:00', '17:45', 'Αίθουσα 1');
        $this->enroll($sofia, $lesson);

        $theory = $this->course('Θεωρία της μουσικής', 'Ομαδικά τμήματα θεωρίας.', 'group');
        $this->enroll($sofia, $this->meet($theory, 'Δευτέρα 16:00', $theoryTeacher, 1, '16:00', '17:00', 'Αίθουσα 2'));
        $this->enroll($nikos, $this->meet($theory, 'Πέμπτη 16:00', $pianoTeacher, 4, '16:00', '17:00', 'Αίθουσα 2'));

        $year = StudentSubscription::query()->create([
            'student_id' => $sofia->id,
            'course_id' => $piano->id,
            'title' => 'Πιάνο 2026-2027',
            'total_amount' => 1200,
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-06-15',
            'status' => 'active',
        ]);
        Payment::query()->create([
            'student_id' => $sofia->id,
            'subscription_id' => $year->id,
            'course_id' => $piano->id,
            'amount' => 200,
            'type' => 'installment',
            'status' => 'paid',
            'due_date' => '2026-10-01',
            'paid_at' => '2026-10-01 12:00:00',
        ]);
        PrivateLesson::query()->create([
            'student_id' => $sofia->id,
            'teacher_id' => $pianoTeacher->id,
            'scheduled_at' => '2026-11-04 17:00:00',
            'duration_minutes' => 45,
            'status' => 'confirmed',
        ]);
        LessonLog::query()->create([
            'schedule_id' => $lesson->id,
            'student_id' => $sofia->id,
            'teacher_id' => $pianoTeacher->id,
            'happened_on' => '2026-10-14',
            'performance_notes' => 'Καθαρότερη άρθρωση στο αριστερό χέρι. Το τέμπο μένει σταθερό.',
            'test_title' => 'Σκάλα Ντο μείζονα',
            'test_score' => 'Άριστα',
        ]);

        $recital = Event::query()->create([
            'title' => 'Εαρινό ρεσιτάλ',
            'description' => 'Ρεσιτάλ μαθητών πιάνου και θεωρίας.',
            'event_date' => '2027-05-20 19:00:00',
            'location' => 'Αίθουσα 1',
        ]);
        EventParticipant::query()->create(['event_id' => $recital->id, 'user_id' => $sofia->id, 'role_in_event' => 'performer']);
        EventParticipant::query()->create(['event_id' => $recital->id, 'user_id' => $pianoTeacher->id, 'role_in_event' => 'instructor']);
        Expense::query()->create([
            'title' => 'Κούρδισμα πιάνου',
            'amount' => 60,
            'category' => 'supplies',
            'expense_date' => '2026-10-03',
        ]);
    }
}
