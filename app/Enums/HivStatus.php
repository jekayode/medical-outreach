<?php

namespace App\Enums;

enum HivStatus: string
{
    case Negative = 'negative';
    case Positive = 'positive';
    case Declined = 'declined';
    case NotTested = 'not_tested';
}
