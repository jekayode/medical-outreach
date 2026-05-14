<?php

namespace App\Models;

use App\Enums\OutreachStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outreach extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'location',
        'start_date',
        'end_date',
        'code_prefix',
        'status',
        'next_check_in_sequence',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => OutreachStatus::class,
            'next_check_in_sequence' => 'integer',
        ];
    }

    /** @return HasMany<Visit, $this> */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /** @return HasMany<Import, $this> */
    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    /** @return BelongsToMany<Beneficiary, $this> */
    public function registeredBeneficiaries(): BelongsToMany
    {
        return $this->belongsToMany(Beneficiary::class, 'beneficiary_outreach')->withTimestamps();
    }
}
