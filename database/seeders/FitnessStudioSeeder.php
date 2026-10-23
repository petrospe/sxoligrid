<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\StudentSubscription;
use Illuminate\Database\Seeder;

class FitnessStudioSeeder extends Seeder
{
    use BuildsExample;

    /**
     * @return array{emails: list<string>, courses: list<string>, subscriptions: list<string>, expenses: list<string>, events: list<string>, flex: list<string>}
     */
    public static function markers(): array
    {
        return [
            'emails' => [
                'fitness-owner@sxoligrid.test',
                'fitness-teacher@sxoligrid.test',
                'fitness-teacher2@sxoligrid.test',
                'fitness-student@sxoligrid.test',
                'fitness-student2@sxoligrid.test',
                'fitness-visitor@sxoligrid.test',
            ],
            'courses' => ['Yoga', 'Pilates', 'Personal training'],
            'subscriptions' => ['12 μήνες fitness'],
            'expenses' => ['Συντήρηση οργάνων'],
            'events' => [],
            'flex' => [],
        ];
    }

    public function run(): void
    {
        $this->person('Πέτρος Στούντιο', 'fitness-owner@sxoligrid.test', 'owner', '6920000001', true);
        $morning = $this->person('Ελένη Πρωινή', 'fitness-teacher@sxoligrid.test', 'teacher', '6920000002');
        $evening = $this->person('Νίκος Βραδινός', 'fitness-teacher2@sxoligrid.test', 'teacher', '6920000003');
        $member = $this->person('Μαρία Μέλος', 'fitness-student@sxoligrid.test', 'student', '6920000004');
        $pilates = $this->person('Κώστας Πιλάτες', 'fitness-student2@sxoligrid.test', 'student', '6920000005');
        $visitor = $this->person('Γιάννης Επίσκεψη', 'fitness-visitor@sxoligrid.test', 'student', '6920000006');

        $yoga = $this->course('Yoga', 'Ομαδικά τμήματα με διαφορετική ώρα και καθηγητή.', 'group');
        $this->enroll($member, $this->meet($yoga, 'Πρωί 09:00', $morning, 1, '09:00', '10:00', 'Αίθουσα Α'));
        $this->enroll($pilates, $this->meet($yoga, 'Βράδυ 20:00', $evening, 3, '20:00', '21:00', 'Αίθουσα Α'));

        $pilatesCourse = $this->course('Pilates', 'Ομαδικό.', 'group');
        $this->enroll($pilates, $this->meet($pilatesCourse, 'Τρίτη 18:00', $morning, 2, '18:00', '19:00', 'Αίθουσα Β'));

        $personal = $this->course('Personal training', 'Ατομική προπόνηση.', 'private');
        $this->enroll($member, $this->meet($personal, 'Πέμπτη 19:00', $evening, 4, '19:00', '19:45', 'Αίθουσα Β'));

        $membership = StudentSubscription::query()->create([
            'student_id' => $member->id,
            'course_id' => null,
            'title' => '12 μήνες fitness',
            'total_amount' => 500,
            'starts_at' => '2026-10-01',
            'ends_at' => '2027-09-30',
            'status' => 'active',
        ]);
        Payment::query()->create([
            'student_id' => $member->id,
            'subscription_id' => $membership->id,
            'course_id' => null,
            'amount' => 100,
            'type' => 'installment',
            'status' => 'paid',
            'due_date' => '2026-10-01',
            'paid_at' => '2026-10-01 09:00:00',
        ]);
        Payment::query()->create([
            'student_id' => $visitor->id,
            'subscription_id' => null,
            'course_id' => $yoga->id,
            'amount' => 10,
            'type' => 'drop_in',
            'status' => 'paid',
            'due_date' => '2026-10-15',
            'paid_at' => '2026-10-15 20:00:00',
        ]);
        Expense::query()->create([
            'title' => 'Συντήρηση οργάνων',
            'amount' => 80,
            'category' => 'supplies',
            'expense_date' => '2026-10-08',
        ]);
    }
}
