<?php

namespace App\Enums;

enum VisitStage: string
{
    case CheckedIn = 'checked_in';
    case VitalsDone = 'vitals_done';
    case InProgress = 'in_progress';
    case Counselling = 'counselling';
    case Completed = 'completed';
}
