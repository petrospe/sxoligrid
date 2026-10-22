<?php

namespace App\Enums;

enum FlexSuggestionStatus: string
{
    use Values;

    case Unread = 'unread';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
