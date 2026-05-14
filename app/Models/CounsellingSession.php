<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounsellingSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'visit_id',
        'counsellor_user_id',
        'types',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'types' => 'array',
        ];
    }

    /** @return BelongsTo<Visit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /** @return BelongsTo<User, $this> */
    public function counsellorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counsellor_user_id');
    }
}
