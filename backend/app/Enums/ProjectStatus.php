<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case NotStarted = 'not_started';
    case WorkingOnIt = 'working_on_it';
    case Cancelled = 'cancelled';
    case Done = 'done';
}
