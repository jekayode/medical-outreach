<?php

namespace App\Enums;

enum ConsultationNextAction: string
{
    case Lab = 'lab';
    case Pharmacy = 'pharmacy';
    case Counselling = 'counselling';
    case Done = 'done';
}
