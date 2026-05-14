<?php

namespace App\Services;

use App\Enums\BeneficiarySource;
use App\Enums\CommunicationPreference;
use App\Enums\Gender;
use App\Enums\MedicationStatus;
use App\Models\Beneficiary;
use App\Models\Import;
use App\Models\Outreach;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

class BeneficiaryImportService
{
    /**
     * Map normalized CSV/Excel header (trimmed, lowercased) to internal field keys.
     * PRD §7.1 — duplicate "email" columns: last occurrence wins when merging row data.
     *
     * @var array<string, string>
     */
    private const HEADER_TO_FIELD = [
        'timestamp' => '_ignore',
        'email address' => 'email',
        'full name' => 'full_name',
        'gender' => 'gender',
        'date of birth' => 'date_of_birth',
        'phone number' => 'phone',
        'residential address' => 'residential_address',
        'existing medical conditions' => 'existing_medical_conditions',
        'medication status' => 'medication_status',
        'medication list' => 'medication_list',
        'allergies' => 'allergies',
        'emergency contact name' => 'emergency_contact_name',
        'relationship' => 'emergency_contact_relationship',
        'emergency contact number' => 'emergency_contact_number',
        'medical consent' => 'medical_consent',
        'communication preference' => 'communication_preference',
    ];

    /**
     * Headers that must be present (after normalization) for import to run.
     *
     * @var list<string>
     */
    private const REQUIRED_HEADERS = [
        'full name',
        'gender',
        'date of birth',
        'phone number',
        'residential address',
    ];

    /**
     * Normalized header → canonical key used in {@see self::HEADER_TO_FIELD} / {@see self::REQUIRED_HEADERS}.
     * Covers Google Forms question titles and common typos (e.g. "Data of Birth").
     *
     * @var array<string, string>
     */
    private const HEADER_SYNONYMS = [
        'data of birth' => 'date of birth',
        'do you have any of the following conditions?' => 'existing medical conditions',
        'are you on any medication?' => 'medication status',
        'if yes, please list' => 'medication list',
        'any allergies? i.e drug, food, etc' => 'allergies',
        'relationship to you' => 'relationship',
        'i agree to receive free medical care and understand that this outreach is not a substitute for ongoing medical treatment' => 'medical consent',
        'would you like to receive updates or health tips?' => 'communication preference',
    ];

    /**
     * @return array{import: Import, created: int, updated: int, failed: int}
     */
    public function importFromUpload(Outreach $outreach, string $absolutePath, User $importedBy): array
    {
        $rows = $this->readSpreadsheet($absolutePath);
        if ($rows === []) {
            throw new RuntimeException(__('The uploaded file is empty.'));
        }

        $rawHeaders = array_shift($rows);
        if (! is_array($rawHeaders)) {
            throw new RuntimeException(__('Could not read header row.'));
        }

        $headerIndex = $this->buildHeaderIndex($rawHeaders);
        $this->assertRequiredHeaders($headerIndex);

        $created = 0;
        $updated = 0;
        $failed = 0;
        $attempted = 0;
        /** @var list<array{row: int, message: string}> $errors */
        $errors = [];
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;
            if (! is_array($row) || $this->rowIsEmpty($row)) {
                continue;
            }

            $attempted++;

            try {
                $attributes = $this->mapRowToAttributes($headerIndex, $row);
                $this->validateMappedRow($attributes);

                $result = $this->upsertBeneficiary($attributes, $importedBy);
                $outreach->registeredBeneficiaries()->syncWithoutDetaching([$result['beneficiary']->getKey()]);
                if ($result['action'] === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (Throwable $e) {
                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $import = Import::query()->create([
            'outreach_id' => $outreach->getKey(),
            'imported_by_user_id' => $importedBy->getKey(),
            'filename' => basename($absolutePath),
            'total_rows' => $attempted,
            'successful_rows' => $created + $updated,
            'failed_rows' => $failed,
            'errors' => $errors === [] ? null : $errors,
        ]);

        return [
            'import' => $import,
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
        ];
    }

    /**
     * @return list<list<mixed>>
     */
    private function readSpreadsheet(string $absolutePath): array
    {
        $data = Excel::toArray([], $absolutePath);

        return $data[0] ?? [];
    }

    /**
     * @param  list<mixed>  $rawHeaders
     * @return array<string, int> normalized header => column index
     */
    private function buildHeaderIndex(array $rawHeaders): array
    {
        $index = [];
        foreach ($rawHeaders as $i => $label) {
            $key = $this->canonicalHeaderKey($this->normalizeHeader((string) $label));
            if ($key === '') {
                continue;
            }
            $index[$key] = (int) $i;
        }

        return $index;
    }

    /**
     * @param  array<string, int>  $headerIndex
     */
    private function assertRequiredHeaders(array $headerIndex): void
    {
        $missing = [];
        foreach (self::REQUIRED_HEADERS as $required) {
            if (! array_key_exists($required, $headerIndex)) {
                $missing[] = $required;
            }
        }

        if ($missing !== []) {
            throw new RuntimeException(__('Missing required columns: :cols', [
                'cols' => implode(', ', $missing),
            ]));
        }
    }

    private function normalizeHeader(string $label): string
    {
        $label = trim($label);
        $label = preg_replace('/\s+/u', ' ', $label) ?? $label;

        return strtolower($label);
    }

    private function canonicalHeaderKey(string $normalizedLabel): string
    {
        return self::HEADER_SYNONYMS[$normalizedLabel] ?? $normalizedLabel;
    }

    /**
     * @param  array<string, int>  $headerIndex
     * @param  list<mixed>  $row
     * @return array<string, mixed>
     */
    private function mapRowToAttributes(array $headerIndex, array $row): array
    {
        $out = [];
        foreach (self::HEADER_TO_FIELD as $header => $field) {
            if ($field === '_ignore' || ! isset($headerIndex[$header])) {
                continue;
            }
            $col = $headerIndex[$header];
            $value = $row[$col] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $out[$field] = is_string($value) ? trim($value) : $value;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function validateMappedRow(array $attributes): void
    {
        foreach (['full_name', 'gender', 'date_of_birth', 'phone', 'residential_address'] as $key) {
            if (! isset($attributes[$key]) || $attributes[$key] === '') {
                throw new RuntimeException(__('Missing value for :field.', ['field' => $key]));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{action: 'created'|'updated', beneficiary: Beneficiary}
     */
    private function upsertBeneficiary(array $attributes, User $importedBy): array
    {
        return DB::transaction(function () use ($attributes, $importedBy): array {
            $phone = (string) $attributes['phone'];
            $dob = $this->parseDate((string) $attributes['date_of_birth']);

            $existing = Beneficiary::query()
                ->where('phone', $phone)
                ->first();

            if (! $existing) {
                $existing = Beneficiary::query()
                    ->where('full_name', (string) $attributes['full_name'])
                    ->whereDate('date_of_birth', $dob->toDateString())
                    ->first();
            }

            $payload = $this->buildBeneficiaryPayload($attributes, $importedBy);

            if ($existing) {
                $existing->update($payload);

                return ['action' => 'updated', 'beneficiary' => $existing->refresh()];
            }

            $beneficiary = Beneficiary::query()->create($payload);

            return ['action' => 'created', 'beneficiary' => $beneficiary];
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function buildBeneficiaryPayload(array $attributes, User $importedBy): array
    {
        $gender = $this->parseGender((string) $attributes['gender']);
        $dob = $this->parseDate((string) $attributes['date_of_birth']);

        $medicationStatus = isset($attributes['medication_status'])
            ? $this->parseMedicationStatus((string) $attributes['medication_status'])
            : null;

        $communication = isset($attributes['communication_preference'])
            ? $this->parseCommunicationPreference((string) $attributes['communication_preference'])
            : null;

        return [
            'full_name' => (string) $attributes['full_name'],
            'gender' => $gender,
            'date_of_birth' => $dob->toDateString(),
            'phone' => (string) $attributes['phone'],
            'email' => isset($attributes['email']) ? (string) $attributes['email'] : null,
            'residential_address' => (string) $attributes['residential_address'],
            'existing_medical_conditions' => $attributes['existing_medical_conditions'] ?? null,
            'medication_status' => $medicationStatus,
            'medication_list' => $attributes['medication_list'] ?? null,
            'allergies' => $attributes['allergies'] ?? null,
            'emergency_contact_name' => $attributes['emergency_contact_name'] ?? null,
            'emergency_contact_relationship' => $attributes['emergency_contact_relationship'] ?? null,
            'emergency_contact_number' => $attributes['emergency_contact_number'] ?? null,
            'medical_consent' => $this->parseBool($attributes['medical_consent'] ?? false),
            'communication_preference' => $communication,
            'source' => BeneficiarySource::GoogleFormImport,
            'imported_at' => now(),
            'created_by_user_id' => $importedBy->getKey(),
        ];
    }

    private function parseGender(string $value): Gender
    {
        $v = strtolower(trim($value));
        $v = match ($v) {
            'm', 'male' => 'male',
            'f', 'female' => 'female',
            default => $v,
        };

        $gender = Gender::tryFrom($v);
        if (! $gender) {
            throw new RuntimeException(__('Invalid gender value: :v', ['v' => $value]));
        }

        return $gender;
    }

    private function parseMedicationStatus(string $value): ?MedicationStatus
    {
        $v = strtolower(trim($value));
        if ($v === '' || $v === 'n/a') {
            return null;
        }

        $v = match ($v) {
            'no', 'n' => MedicationStatus::None->value,
            'yes', 'y' => MedicationStatus::Regular->value,
            default => str_replace(' ', '_', $v),
        };

        return MedicationStatus::tryFrom($v);
    }

    private function parseCommunicationPreference(string $value): ?CommunicationPreference
    {
        $v = strtolower(trim($value));
        $v = str_replace([' ', '-'], ['_', '_'], $v);
        $v = match ($v) {
            'phonecall', 'phone_call' => 'phone_call',
            default => $v,
        };

        return CommunicationPreference::tryFrom($v);
    }

    private function parseDate(string $value): Carbon
    {
        $value = trim($value);

        foreach (['Y-m-d', 'd/m/Y', 'd/m/y', 'd-m-Y', 'm/d/Y', 'm/d/y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
                continue;
            }
        }

        return Carbon::parse($value)->startOfDay();
    }

    private function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $v = strtolower(trim((string) $value));

        if (str_starts_with($v, 'yes')) {
            return true;
        }

        return in_array($v, ['y', '1', 'true', 'agree'], true);
    }

    /**
     * @param  list<mixed>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
