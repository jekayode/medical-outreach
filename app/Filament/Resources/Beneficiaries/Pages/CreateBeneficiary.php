<?php

namespace App\Filament\Resources\Beneficiaries\Pages;

use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBeneficiary extends CreateRecord
{
    protected static string $resource = BeneficiaryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = auth()->id();

        return $data;
    }
}
