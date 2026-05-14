<?php

namespace App\Enums;

enum OutreachStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Closed = 'closed';
}
