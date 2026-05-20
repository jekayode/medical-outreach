<?php

use App\Http\Controllers\Admin\DatabaseBackupDownloadController;
use App\Http\Controllers\DashboardRedirectController;
use App\Livewire\Stations\CheckIn;
use App\Livewire\Stations\Counselling;
use App\Livewire\Stations\DentalCare;
use App\Livewire\Stations\Doctor;
use App\Livewire\Stations\EyeCare;
use App\Livewire\Stations\Lab;
use App\Livewire\Stations\Pharmacy;
use App\Livewire\Stations\Vitals;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/dashboard', DashboardRedirectController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/admin/database-backup/download', DatabaseBackupDownloadController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.database-backup.download');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/stations/check-in', CheckIn::class)->middleware('role:check_in')->name('stations.check-in');
    Route::get('/stations/vitals', Vitals::class)->middleware('role:nurse')->name('stations.vitals');
    Route::get('/stations/doctor', Doctor::class)->middleware('role:doctor')->name('stations.doctor');
    Route::get('/stations/lab', Lab::class)->middleware('role:lab')->name('stations.lab');
    Route::get('/stations/pharmacy', Pharmacy::class)->middleware('role:pharmacist')->name('stations.pharmacy');
    Route::get('/stations/eye-care', EyeCare::class)->middleware('role:eye_care')->name('stations.eye-care');
    Route::get('/stations/dental-care', DentalCare::class)->middleware('role:dental_care')->name('stations.dental-care');
    Route::get('/stations/counselling', Counselling::class)->middleware('role:counsellor')->name('stations.counselling');
});

require __DIR__.'/auth.php';
