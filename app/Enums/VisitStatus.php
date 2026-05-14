<?php

namespace App\Enums;

enum VisitStatus: string
{
    case Open = 'open';
    case Completed = 'completed';
    case NoShow = 'no_show';
}
