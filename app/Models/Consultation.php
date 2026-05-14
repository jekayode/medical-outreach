<?php

namespace App\Models;

use App\Enums\ConsultationNextAction;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultation extends Model
{
    use HasUuids;

    protected $fillable = [
        'intervention_id',
        'doctor_user_id',
        'chief_complaint',
        'observations',
        'diagnosis',
        'next_action',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'next_action' => ConsultationNextAction::class,
        ];
    }

    /** @return BelongsTo<Intervention, $this> */
    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }

    /** @return BelongsTo<User, $this> */
    public function doctorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_user_id');
    }

    /** @return HasMany<LabOrder, $this> */
    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class);
    }
}
