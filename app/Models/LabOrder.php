<?php

namespace App\Models;

use App\Enums\LabOrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabOrder extends Model
{
    use HasUuids;

    protected $fillable = [
        'consultation_id',
        'ordered_by_user_id',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LabOrderStatus::class,
        ];
    }

    /** @return BelongsTo<Consultation, $this> */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function orderedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by_user_id');
    }

    /** @return HasMany<LabOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(LabOrderItem::class);
    }
}
