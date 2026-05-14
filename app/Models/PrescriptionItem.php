<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\DispensedStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'prescription_id',
        'drug_name',
        'dosage',
        'frequency',
        'duration',
        'quantity',
        'availability',
        'dispensed_status',
        'dispensed_by_user_id',
        'dispensed_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'availability' => AvailabilityStatus::class,
            'dispensed_status' => DispensedStatus::class,
            'dispensed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Prescription, $this> */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /** @return BelongsTo<User, $this> */
    public function dispensedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by_user_id');
    }
}
