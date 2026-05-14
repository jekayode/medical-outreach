<?php

namespace App\Enums;

enum InterventionStatus: string
{
    case Pending = 'pending';
    case InConsultation = 'in_consultation';
    case InExam = 'in_exam';
    case AwaitingLab = 'awaiting_lab';
    case ConsultationReview = 'consultation_review';
    case AwaitingPharmacy = 'awaiting_pharmacy';
    case AwaitingCounselling = 'awaiting_counselling';
    case Completed = 'completed';
}
