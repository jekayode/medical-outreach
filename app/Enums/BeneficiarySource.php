<?php

namespace App\Enums;

enum BeneficiarySource: string
{
    case GoogleFormImport = 'google_form_import';
    case WalkIn = 'walk_in';
}
