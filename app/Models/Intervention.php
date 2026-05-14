<?php

namespace App\Models;

use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Intervention extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'visit_id',
        'type',
        'status',
        'started_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InterventionType::class,
            'status' => InterventionStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Visit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /** @return HasOne<Consultation, $this> */
    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }

    /** @return HasMany<Prescription, $this> */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /** @return HasOne<EyeExam, $this> */
    public function eyeExam(): HasOne
    {
        return $this->hasOne(EyeExam::class);
    }

    /** @return HasOne<DentalExam, $this> */
    public function dentalExam(): HasOne
    {
        return $this->hasOne(DentalExam::class);
    }
}
