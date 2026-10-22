<?php

namespace App\Services;

use App\Enums\FlexSuggestionStatus;
use App\Enums\UserRole;
use App\Models\ClassSchedule;
use App\Models\FlexSuggestion;
use App\Models\User;
use App\Support\Week;
use Illuminate\Support\Collection;

class FlexGapFinder
{
    public function scan(): int
    {
        $created = 0;
        $schedules = ClassSchedule::query()->with('teachers:id')->orderBy('start_time')->get()->groupBy('day_of_week');

        for ($day = 1; $day <= 7; $day++) {
            /** @var Collection<int, ClassSchedule> $blocks */
            $blocks = $schedules->get($day, collect())->sortBy(
                fn (ClassSchedule $schedule): string => Week::clock($schedule->start_time)
            )->values();

            if ($blocks->isEmpty()) {
                continue;
            }

            $targets = $this->targetsFor($blocks);
            $cursor = '09:00';

            $points = $blocks->map(fn (ClassSchedule $schedule): array => [
                Week::clock($schedule->start_time),
                Week::clock($schedule->end_time),
            ])->all();
            $points[] = ['21:00', '21:00'];

            foreach ($points as [$start, $end]) {
                $gap = $this->minutes($cursor, $start);

                if ($gap >= 120) {
                    $created += $this->propose($targets, $day, $cursor, $start, $gap);
                }

                if ($end > $cursor) {
                    $cursor = $end;
                }
            }
        }

        return $created;
    }

    /**
     * @param  Collection<int, ClassSchedule>  $blocks
     * @return Collection<int, User>
     */
    private function targetsFor(Collection $blocks): Collection
    {
        $teacherIds = $blocks->flatMap(function (ClassSchedule $schedule): array {
            return $schedule->teachers->pluck('id')->push($schedule->teacher_id)->filter()->unique()->all();
        })->unique()->values()->all();

        return User::query()
            ->where(function ($query) use ($teacherIds): void {
                $query->whereIn('role', [UserRole::Owner, UserRole::Admin]);

                if ($teacherIds !== []) {
                    $query->orWhereIn('id', $teacherIds);
                }
            })
            ->get();
    }

    /**
     * @param  Collection<int, User>  $targets
     */
    private function propose(Collection $targets, int $day, string $from, string $to, int $gap): int
    {
        $hours = intdiv($gap, 60);
        $minutes = $gap % 60;
        $decimal = app()->getLocale() === 'el' ? ',' : '.';
        $length = $minutes === 0
            ? (string) $hours
            : number_format($gap / 60, 1, $decimal, '');
        $unit = $hours === 1 && $minutes === 0
            ? __('ui.flex.hour')
            : __('ui.flex.hours');
        $content = __('ui.flex.gap', [
            'length' => $length,
            'unit' => $unit,
            'day' => Week::name($day),
            'from' => $from,
            'to' => $to,
        ]);

        $created = 0;

        foreach ($targets as $target) {
            $exists = FlexSuggestion::query()
                ->where('target_user_id', $target->id)
                ->where('content', $content)
                ->where('status', FlexSuggestionStatus::Unread)
                ->exists();

            if ($exists) {
                continue;
            }

            FlexSuggestion::query()->create([
                'target_user_id' => $target->id,
                'title' => __('ui.flex.title'),
                'content' => $content,
                'status' => FlexSuggestionStatus::Unread,
            ]);
            $created++;
        }

        return $created;
    }

    private function minutes(string $from, string $to): int
    {
        [$fromHour, $fromMinute] = array_map(intval(...), explode(':', $from));
        [$toHour, $toMinute] = array_map(intval(...), explode(':', $to));

        return ($toHour * 60 + $toMinute) - ($fromHour * 60 + $fromMinute);
    }
}
