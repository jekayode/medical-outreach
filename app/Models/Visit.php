<?php

namespace App\Models;

use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'beneficiary_id',
        'outreach_id',
        'check_in_code',
        'checked_in_at',
        'checked_in_by_user_id',
        'current_stage',
        'status',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'completed_at' => 'datetime',
            'current_stage' => VisitStage::class,
            'status' => VisitStatus::class,
        ];
    }

    /** @return BelongsTo<Beneficiary, $this> */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    /** @return BelongsTo<Outreach, $this> */
    public function outreach(): BelongsTo
    {
        return $this->belongsTo(Outreach::class);
    }

    /** @return BelongsTo<User, $this> */
    public function checkedInByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by_user_id');
    }

    /** @return HasMany<Intervention, $this> */
    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    /** @return HasOne<Vitals, $this> */
    public function vitals(): HasOne
    {
        return $this->hasOne(Vitals::class);
    }

    /** @return HasOne<CounsellingSession, $this> */
    public function counsellingSession(): HasOne
    {
        return $this->hasOne(CounsellingSession::class);
    }

    /**
     * Visits that still need vitals for a given outreach (PRD §5.3).
     *
     * @param  Builder<Visit>  $query
     */
    public function scopeAwaitingVitals(Builder $query, Outreach $outreach): void
    {
        $query->where('outreach_id', $outreach->getKey())
            ->where('current_stage', VisitStage::CheckedIn)
            ->whereDoesntHave('vitals');
    }
}
