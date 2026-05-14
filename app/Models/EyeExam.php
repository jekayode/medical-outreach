<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EyeExam extends Model
{
    use HasUuids;

    protected $fillable = [
        'intervention_id',
        'examined_by_user_id',
        'visual_acuity_left',
        'visual_acuity_right',
        'findings',
        'glasses_prescribed',
        'glasses_prescription_details',
        'drops_prescribed',
        'referral_needed',
        'referral_notes',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'glasses_prescribed' => 'boolean',
            'drops_prescribed' => 'boolean',
            'referral_needed' => 'boolean',
        ];
    }

    /** @return BelongsTo<Intervention, $this> */
    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }

    /** @return BelongsTo<User, $this> */
    public function examinedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examined_by_user_id');
    }
}
