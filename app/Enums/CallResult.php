<?php

namespace App\Enums;

enum CallResult: string
{
    case NO_ANSWER = 'no_answer';
    case REFUSED = 'refused';
    case MEETING_SET = 'meeting_set';
}
