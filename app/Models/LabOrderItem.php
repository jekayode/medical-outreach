<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabOrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'lab_order_id',
        'test_name',
        'notes',
        'result',
        'result_recorded_by_user_id',
        'result_recorded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result_recorded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LabOrder, $this> */
    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class);
    }

    /** @return BelongsTo<User, $this> */
    public function resultRecordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'result_recorded_by_user_id');
    }
}
