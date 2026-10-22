<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventParticipantRole;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use App\Services\EventRoster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Events/Index', [
            'events' => Event::query()->withCount('participants')->orderBy('event_date')->get()->map(fn (Event $event): array => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'event_date' => $event->event_date->format('Y-m-d\TH:i'),
                'location' => $event->location,
                'participants_count' => $event->participants_count,
            ])->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Event::query()->create($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
        ]));

        return back()->with('success', __('ui.flash.event_created'));
    }

    public function show(Event $event): Response
    {
        return Inertia::render('Events/Show', [
            'event' => $this->present($event),
            'people' => User::query()->orderBy('name')->get(['id', 'name', 'role'])->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role->value,
            ])->all(),
            'roles' => EventParticipantRole::values(),
            'baseUrl' => '/admin/events/'.$event->id,
        ]);
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', __('ui.flash.event_deleted'));
    }

    public function addParticipant(Request $request, Event $event, EventRoster $roster): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_in_event' => ['required', Rule::in(EventParticipantRole::values())],
        ]);

        $roster->add($event, (int) $data['user_id'], EventParticipantRole::from($data['role_in_event']));

        return back()->with('success', __('ui.flash.participant_saved'));
    }

    public function removeParticipant(Event $event, EventParticipant $participant, EventRoster $roster): RedirectResponse
    {
        $roster->remove($event, $participant);

        return back()->with('success', __('ui.flash.participant_removed'));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Event $event): array
    {
        $event->load('participants.user:id,name,role');

        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'event_date' => $event->event_date->format('Y-m-d H:i'),
            'location' => $event->location,
            'participants' => $event->participants->map(fn (EventParticipant $participant): array => [
                'id' => $participant->id,
                'role_in_event' => $participant->role_in_event->value,
                'user' => [
                    'id' => $participant->user->id,
                    'name' => $participant->user->name,
                    'role' => $participant->user->role->value,
                ],
            ])->all(),
        ];
    }
}
