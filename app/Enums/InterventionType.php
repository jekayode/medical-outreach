<?php

namespace App\Enums;

enum InterventionType: string
{
    case GeneralConsultation = 'general_consultation';
    case EyeCare = 'eye_care';
    case DentalCare = 'dental_care';
}
