<?php

namespace App\Enums;

enum MedicationStatus: string
{
    case None = 'none';
    case Occasional = 'occasional';
    case Regular = 'regular';
}
