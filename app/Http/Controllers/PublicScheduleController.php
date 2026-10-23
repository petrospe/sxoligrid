<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\LandingProfile;
use App\Support\ScheduleMapper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $schedules = ClassSchedule::query()
            ->with(['course:id,title,type', 'section:id,name'])
            ->where('visible_on_landing', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get(['id', 'course_id', 'section_id', 'day_of_week', 'start_time', 'end_time', 'room']);

        return Inertia::render('Public/Schedule', [
            'show_landing' => $request->user() === null,
            'landing' => LandingProfile::current()->forLocale(app()->getLocale()),
            'schedules' => ScheduleMapper::publicList($schedules),
        ]);
    }
}
