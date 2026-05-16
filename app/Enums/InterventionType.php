<?php

namespace App\Enums;

use App\Models\CounsellingSession;
use App\Models\Visit;

/**
 * Clinical intervention types that are tracked as Intervention records on a Visit.
 *
 * Counselling is intentionally absent here — it is visit-scoped and stored directly
 * on the Visit via a CounsellingSession record rather than as an Intervention line.
 * This reflects the workflow where counselling runs in parallel with, or after,
 * clinical interventions rather than as a discrete clinical intervention itself.
 *
 * @see CounsellingSession
 * @see Visit::counsellingSession()
 */
enum InterventionType: string
{
    case GeneralConsultation = 'general_consultation';
    case EyeCare = 'eye_care';
    case DentalCare = 'dental_care';
}
