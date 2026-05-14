<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalExam extends Model
{
    use HasUuids;

    protected $fillable = [
        'intervention_id',
        'examined_by_user_id',
        'findings',
        'treatment_performed',
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
