<?php

namespace App\Services;

use App\Models\Outreach;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckInCodeGenerator
{
    /**
     * Atomically allocate the next check-in code for an outreach (PRD §8).
     */
    public function generate(Outreach $outreach): string
    {
        return DB::transaction(function () use ($outreach): string {
            /** @var Outreach $locked */
            $locked = Outreach::query()->whereKey($outreach->getKey())->lockForUpdate()->firstOrFail();

            $locked->next_check_in_sequence = $locked->next_check_in_sequence + 1;
            $locked->save();

            $prefix = Str::upper($locked->code_prefix);

            return sprintf('%s-%04d', $prefix, $locked->next_check_in_sequence);
        });
    }

    /**
     * Ensure code is unique before persisting a visit (defensive; sequence should guarantee uniqueness).
     */
    public function codeExists(string $checkInCode): bool
    {
        return Visit::query()->where('check_in_code', Str::upper($checkInCode))->exists();
    }
}
