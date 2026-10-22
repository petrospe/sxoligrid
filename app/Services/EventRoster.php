<?php

namespace App\Services;

use App\Enums\EventParticipantRole;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class EventRoster
{
    public function add(Event $event, int $userId, EventParticipantRole $role): EventParticipant
    {
        $user = User::query()->find($userId);

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'user_id' => __('ui.errors.user_not_found'),
            ]);
        }

        return EventParticipant::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => $user->id,
            ],
            [
                'role_in_event' => $role,
            ],
        );
    }

    public function remove(Event $event, EventParticipant $participant): void
    {
        abort_unless($participant->event_id === $event->id, 404);

        $participant->delete();
    }
}
