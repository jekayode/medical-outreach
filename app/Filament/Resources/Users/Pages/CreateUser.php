<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $roleToAssign = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->roleToAssign = (string) ($data['role'] ?? 'check_in');
        unset($data['role'], $data['password_confirmation']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->roleToAssign) {
            $this->record->syncRoles([$this->roleToAssign]);
        }
    }
}
