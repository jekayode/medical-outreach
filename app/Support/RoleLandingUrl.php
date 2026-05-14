<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

final class RoleLandingUrl
{
    /**
     * Primary destination after login / registration, or when visiting `/dashboard`.
     */
    public static function home(?Authenticatable $user): string
    {
        if (! $user instanceof User) {
            return route('profile');
        }

        if ($user->hasRole('admin')) {
            return route('filament.admin.pages.dashboard');
        }

        if ($user->hasRole('check_in')) {
            return route('stations.check-in');
        }

        if ($user->hasRole('nurse')) {
            return route('stations.vitals');
        }

        if ($user->hasRole('doctor')) {
            return route('stations.doctor');
        }

        if ($user->hasRole('lab')) {
            return route('stations.lab');
        }

        if ($user->hasRole('pharmacist')) {
            return route('stations.pharmacy');
        }

        if ($user->hasRole('eye_care')) {
            return route('stations.eye-care');
        }

        if ($user->hasRole('dental_care')) {
            return route('stations.dental-care');
        }

        if ($user->hasRole('counsellor')) {
            return route('stations.counselling');
        }

        return route('profile');
    }
}
