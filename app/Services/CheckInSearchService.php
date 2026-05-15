<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\Outreach;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Merges visit search (existing check-in codes) with people who still need a visit for this outreach.
 *
 * Name/phone matches include beneficiaries not yet linked on the outreach pivot (legacy or manual rows);
 * {@see VisitCheckInService} attaches the pivot when the visit is created.
 */
final class CheckInSearchService
{
    public function __construct(
        private StationBeneficiarySearch $visitSearch,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function search(Outreach $outreach, string $rawTerm): Collection
    {
        $term = trim($rawTerm);
        if ($term === '') {
            return collect();
        }

        $visitRows = $this->visitSearch->search($outreach, $term)->map(function (array $row): array {
            return array_merge($row, ['kind' => 'visit']);
        });

        $visitBeneficiaryIds = $visitRows->pluck('beneficiary_id')->all();

        if ($this->termLooksLikeCheckInCode($term)) {
            return $visitRows->values();
        }

        $pendingRows = $this->searchPendingBeneficiaries($outreach, $term, $visitBeneficiaryIds);

        return $visitRows->concat($pendingRows)->values();
    }

    private function termLooksLikeCheckInCode(string $term): bool
    {
        $stripped = preg_replace('/\s+/', '', $term) ?? $term;

        if (preg_match('/^([A-Za-z][A-Za-z0-9]*)-(\d+)$/', $stripped)) {
            return true;
        }

        return (bool) preg_match('/^\d{1,4}$/', $stripped);
    }

    /**
     * @param  list<string>  $excludeBeneficiaryIds
     * @return Collection<int, array<string, mixed>>
     */
    private function searchPendingBeneficiaries(Outreach $outreach, string $term, array $excludeBeneficiaryIds): Collection
    {
        $query = Beneficiary::query()
            ->whereDoesntHave('visits', function (Builder $q) use ($outreach): void {
                $q->where('outreach_id', $outreach->getKey());
            });

        if ($excludeBeneficiaryIds !== []) {
            $query->whereNotIn('id', $excludeBeneficiaryIds);
        }

        $stripped = preg_replace('/\s+/', '', $term) ?? $term;
        $digitsOnly = preg_replace('/\D+/', '', $term) ?? '';

        if (strlen($digitsOnly) >= 5) {
            $query->where('phone', 'like', '%'.$digitsOnly.'%');
        } elseif (strlen($digitsOnly) === 4 && ! preg_match('/^\d{1,4}$/', $stripped)) {
            $query->where('phone', 'like', '%'.$digitsOnly);
        } else {
            $escaped = addcslashes($term, '%_\\');
            $query->whereRaw('LOWER(full_name) LIKE ?', ['%'.mb_strtolower($escaped, 'UTF-8').'%']);
        }

        return $query->limit(25)->get()->map(function (Beneficiary $beneficiary): array {
            return [
                'kind' => 'pending',
                'visit_id' => null,
                'beneficiary_id' => $beneficiary->getKey(),
                'check_in_code' => null,
                'full_name' => $beneficiary->full_name,
                'gender_value' => $beneficiary->gender->value,
                'gender_label' => Str::headline($beneficiary->gender->value),
                'age' => $beneficiary->date_of_birth?->age,
                'phone' => $beneficiary->phone,
                'interventions' => [],
            ];
        });
    }
}
