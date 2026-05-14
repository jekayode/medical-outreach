<?php

namespace App\Enums;

enum DispensedStatus: string
{
    case Pending = 'pending';
    case Dispensed = 'dispensed';
    case DeclinedByBeneficiary = 'declined_by_beneficiary';
}
