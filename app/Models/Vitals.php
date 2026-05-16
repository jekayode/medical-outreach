<?php

namespace App\Models;

use App\Enums\HivStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vitals extends Model
{
    use HasUuids;

    protected $table = 'vitals';

    protected $fillable = [
        'visit_id',
        'taken_by_user_id',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'pulse',
        'temperature',
        'weight_kg',
        'height_cm',
        'bmi',
        'blood_glucose',
        'hiv_status',
        'notes',
        'lab_notes',
        'taken_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'temperature' => 'decimal:1',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'bmi' => 'decimal:1',
            'blood_glucose' => 'decimal:1',
            'hiv_status' => HivStatus::class,
            'taken_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Vitals $vitals): void {
            if ($vitals->weight_kg !== null && $vitals->height_cm !== null && (float) $vitals->height_cm > 0) {
                $heightM = (float) $vitals->height_cm / 100;
                $vitals->bmi = round((float) $vitals->weight_kg / ($heightM * $heightM), 1);
            }
        });
    }

    /** @return BelongsTo<Visit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /** @return BelongsTo<User, $this> */
    public function takenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_by_user_id');
    }
}
