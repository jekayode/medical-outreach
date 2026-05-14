<?php

namespace App\Exports;

use App\Models\Beneficiary;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class BeneficiaryListExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @return Builder<Beneficiary>
     */
    public function query(): Builder
    {
        return Beneficiary::query()->orderBy('full_name');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            __('ID'),
            __('Full name'),
            __('Gender'),
            __('Date of birth'),
            __('Phone'),
            __('Email'),
            __('Residential address'),
            __('Existing medical conditions'),
            __('Medication status'),
            __('Medication list'),
            __('Allergies'),
            __('Emergency contact name'),
            __('Emergency contact relationship'),
            __('Emergency contact number'),
            __('Medical consent'),
            __('Communication preference'),
            __('Source'),
            __('Imported at'),
            __('Created by user ID'),
            __('Created at'),
            __('Updated at'),
            __('Deleted at'),
        ];
    }

    /**
     * @param  Beneficiary  $beneficiary
     * @return list<string|int|float|null>
     */
    public function map($beneficiary): array
    {
        return [
            $beneficiary->getKey(),
            $beneficiary->full_name,
            $beneficiary->gender?->value ?? '',
            $this->formatDate($beneficiary->date_of_birth),
            $beneficiary->phone,
            $beneficiary->email ?? '',
            $beneficiary->residential_address,
            $beneficiary->existing_medical_conditions ?? '',
            $beneficiary->medication_status?->value ?? '',
            $beneficiary->medication_list ?? '',
            $beneficiary->allergies ?? '',
            $beneficiary->emergency_contact_name ?? '',
            $beneficiary->emergency_contact_relationship ?? '',
            $beneficiary->emergency_contact_number ?? '',
            $beneficiary->medical_consent ? __('Yes') : __('No'),
            $beneficiary->communication_preference?->value ?? '',
            $beneficiary->source?->value ?? '',
            $this->formatDateTime($beneficiary->imported_at),
            $beneficiary->created_by_user_id ?? '',
            $this->formatDateTime($beneficiary->created_at),
            $this->formatDateTime($beneficiary->updated_at),
            $this->formatDateTime($beneficiary->deleted_at),
        ];
    }

    private function formatDate(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        return '';
    }

    private function formatDateTime(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateTimeString();
        }

        return '';
    }
}
