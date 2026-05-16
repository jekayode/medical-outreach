<?php

namespace App\Models;

use App\Enums\BeneficiarySource;
use App\Enums\CommunicationPreference;
use App\Enums\Gender;
use App\Enums\MedicationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiary extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'full_name',
        'gender',
        'date_of_birth',
        'phone',
        'email',
        'residential_address',
        'existing_medical_conditions',
        'medication_status',
        'medication_list',
        'allergies',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_number',
        'medical_consent',
        'communication_preference',
        'source',
        'imported_at',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'date_of_birth' => 'date',
            'medication_status' => MedicationStatus::class,
            'communication_preference' => CommunicationPreference::class,
            'source' => BeneficiarySource::class,
            'medical_consent' => 'boolean',
            'imported_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<Visit, $this> */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /** @return BelongsToMany<Outreach, $this> */
    public function registeredOutreaches(): BelongsToMany
    {
        return $this->belongsToMany(Outreach::class, 'beneficiary_outreach')->withTimestamps();
    }
}
