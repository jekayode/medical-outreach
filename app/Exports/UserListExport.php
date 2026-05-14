<?php

namespace App\Exports;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class UserListExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @return Builder<User>
     */
    public function query(): Builder
    {
        return User::query()->with('roles')->orderBy('name');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            __('ID'),
            __('Name'),
            __('Email'),
            __('Roles'),
            __('Active'),
            __('Email verified at'),
            __('Created at'),
            __('Updated at'),
        ];
    }

    /**
     * @param  User  $user
     * @return list<string|int|float|null>
     */
    public function map($user): array
    {
        $roles = $user->roles->pluck('name')->sort()->values()->implode(', ');

        return [
            $user->getKey(),
            $user->name,
            $user->email,
            $roles,
            $user->is_active ? __('Yes') : __('No'),
            $this->formatDateTime($user->email_verified_at),
            $this->formatDateTime($user->created_at),
            $this->formatDateTime($user->updated_at),
        ];
    }

    private function formatDateTime(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateTimeString();
        }

        return '';
    }
}
