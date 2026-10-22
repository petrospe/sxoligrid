<?php

namespace App\Enums;

enum EventParticipantRole: string
{
    use Values;

    case Performer = 'performer';
    case Attendee = 'attendee';
    case Instructor = 'instructor';
    case Helper = 'helper';
}
