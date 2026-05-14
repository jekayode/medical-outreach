<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'outreach_id',
        'imported_by_user_id',
        'filename',
        'total_rows',
        'successful_rows',
        'failed_rows',
        'errors',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'errors' => 'array',
        ];
    }

    /** @return BelongsTo<Outreach, $this> */
    public function outreach(): BelongsTo
    {
        return $this->belongsTo(Outreach::class);
    }

    /** @return BelongsTo<User, $this> */
    public function importedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by_user_id');
    }
}
