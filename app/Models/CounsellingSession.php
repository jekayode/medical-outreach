<?php

namespace App\Models;

use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\VisitStage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a counselling session attached directly to a Visit.
 *
 * Unlike clinical interventions (GeneralConsultation, EyeCare, DentalCare) which are
 * modelled as Intervention records, counselling is visit-scoped. It runs in parallel
 * with or after clinical interventions and does not need its own Intervention line.
 * The visit's current_stage transitions through VisitStage::Counselling to track progress,
 * and Intervention rows use InterventionStatus::AwaitingCounselling to signal the handoff.
 *
 * @see InterventionType
 * @see InterventionStatus::AwaitingCounselling
 * @see VisitStage::Counselling
 */
class CounsellingSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'visit_id',
        'counsellor_user_id',
        'types',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'types' => 'array',
        ];
    }

    /** @return BelongsTo<Visit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /** @return BelongsTo<User, $this> */
    public function counsellorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counsellor_user_id');
    }
}
