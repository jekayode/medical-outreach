<?php

namespace App\Services;

use App\Models\Outreach;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class StationBeneficiarySearch
{
    /**
     * Search checked-in visits for the given outreach (PRD §5).
     *
     * Pre-registered beneficiaries without a Visit are listed only on the check-in screen (see {@see CheckInSearchService}).
     *
     * @return Collection<int, array{
     *     visit_id: string,
     *     beneficiary_id: string,
     *     check_in_code: string,
     *     full_name: string,
     *     gender_value: string,
     *     gender_label: string,
     *     age: int|null,
     *     interventions: list<array{
     *         type_value: string,
     *         type_label: string,
     *         status_value: string,
     *         status_label: string
     *     }>
     * }>
     */
    public function search(Outreach $outreach, string $rawTerm): Collection
    {
        $term = trim($rawTerm);
        if ($term === '') {
            return collect();
        }

        $stripped = preg_replace('/\s+/', '', $term) ?? $term;

        $base = Visit::query()
            ->where('outreach_id', $outreach->getKey())
            ->with(['beneficiary', 'interventions']);

        if (preg_match('/^([A-Za-z][A-Za-z0-9]*)-(\d+)$/', $stripped, $m)) {
            $code = Str::upper((string) $m[1]).'-'.str_pad((string) $m[2], 4, '0', STR_PAD_LEFT);

            return $this->formatResults($base->clone()->where('check_in_code', $code));
        }

        if (preg_match('/^\d{1,4}$/', $stripped)) {
            $prefix = Str::upper($outreach->code_prefix);
            $code = $prefix.'-'.str_pad($stripped, 4, '0', STR_PAD_LEFT);

            return $this->formatResults($base->clone()->where('check_in_code', $code));
        }

        $digitsOnly = preg_replace('/\D+/', '', $term) ?? '';

        if (strlen($digitsOnly) >= 5) {
            $q = $base->clone()->whereHas('beneficiary', function (Builder $b) use ($digitsOnly): void {
                $b->where('phone', 'like', '%'.$digitsOnly.'%');
            });

            return $this->formatResults($q->limit(25));
        }

        if (strlen($digitsOnly) === 4 && ! preg_match('/^\d{1,4}$/', $stripped)) {
            $q = $base->clone()->whereHas('beneficiary', function (Builder $b) use ($digitsOnly): void {
                $b->where('phone', 'like', '%'.$digitsOnly);
            });

            return $this->formatResults($q->limit(25));
        }

        $escaped = addcslashes($term, '%_\\');
        $like = '%'.$escaped.'%';

        $q = $base->clone()->whereHas('beneficiary', function (Builder $b) use ($like): void {
            $b->where('full_name', 'like', $like);
        });

        return $this->formatResults($q->limit(25));
    }

    /**
     * @param  Builder<Visit>  $query
     * @return Collection<int, array<string, mixed>>
     */
    private function formatResults(Builder $query): Collection
    {
        return $query->get()->map(function (Visit $visit): array {
            $beneficiary = $visit->beneficiary;

            return [
                'visit_id' => $visit->getKey(),
                'beneficiary_id' => $beneficiary->getKey(),
                'check_in_code' => $visit->check_in_code,
                'full_name' => $beneficiary->full_name,
                'gender_value' => $beneficiary->gender->value,
                'gender_label' => Str::headline($beneficiary->gender->value),
                'age' => $beneficiary->date_of_birth?->age,
                'interventions' => $visit->interventions->map(function ($intervention): array {
                    return [
                        'type_value' => $intervention->type->value,
                        'type_label' => Str::headline(str_replace('_', ' ', $intervention->type->value)),
                        'status_value' => $intervention->status->value,
                        'status_label' => Str::headline(str_replace('_', ' ', $intervention->status->value)),
                    ];
                })->values()->all(),
            ];
        });
    }
}
