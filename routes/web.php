<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompatibilityController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\VisitRecordController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/health', function () {
    DB::select('select 1');
    return response('ok');
});


Route::get('/', [AuthController::class, 'home']);

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.submit');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->middleware('password.change')->name('dashboard');

    Route::get('/password/change', [AuthController::class, 'passwordForm'])->name('password.change');
    Route::put('/password/change', [AuthController::class, 'updatePassword'])->name('password.update');
    Route::get('/logout', [AuthController::class, 'logoutForm'])->name('logout.confirm');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('patient')->name('patient.')->middleware(['password.change', 'role:patient'])->group(function (): void {
        Route::get('/dashboard', [PatientController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [PatientController::class, 'profile'])->name('profile');
        Route::put('/profile', [PatientController::class, 'updateProfile'])->name('profile.update');
        Route::get('/appointments', [AppointmentController::class, 'patientIndex'])->name('appointments');
        Route::get('/appointments/request', [AppointmentController::class, 'requestForm'])->name('appointments.request');
        Route::post('/appointments/request', [AppointmentController::class, 'createRequest'])->name('appointments.store');
        Route::get('/visits', [VisitRecordController::class, 'patientIndex'])->name('visits');
    });

    Route::prefix('staff')->name('staff.')->middleware(['password.change', 'role:staff'])->group(function (): void {
        Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');
        Route::get('/patients', [StaffController::class, 'patients'])->name('patients');
        Route::get('/patients/create', [StaffController::class, 'patientCreateForm'])->name('patients.create');
        Route::post('/patients', [StaffController::class, 'patientCreate'])->name('patients.store');
        Route::get('/patients/{patient}', [StaffController::class, 'patientShow'])->whereNumber('patient')->name('patients.show');
        Route::get('/patients/{patient}/edit', [StaffController::class, 'patientEditForm'])->whereNumber('patient')->name('patients.edit');
        Route::put('/patients/{patient}', [StaffController::class, 'patientUpdate'])->whereNumber('patient')->name('patients.update');

        Route::get('/appointments', [AppointmentController::class, 'staffIndex'])->name('appointments');
        Route::get('/appointments/requests', [AppointmentController::class, 'pendingRequests'])->name('appointment-requests');
        Route::get('/appointments/create', [AppointmentController::class, 'staffCreateForm'])->name('appointments.create');
        Route::post('/appointments', [AppointmentController::class, 'staffCreate'])->name('appointments.store');
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'staffShow'])->whereNumber('appointment')->name('appointments.show');
        Route::post('/appointments/{appointment}/actions', [AppointmentController::class, 'staffAction'])->whereNumber('appointment')->name('appointments.action');

        Route::get('/visits', [VisitRecordController::class, 'staffIndex'])->name('visits');
        Route::get('/visits/create', [VisitRecordController::class, 'createForm'])->name('visits.create');
        Route::post('/visits', [VisitRecordController::class, 'create'])->name('visits.store');
        Route::get('/visits/{visit}', [VisitRecordController::class, 'show'])->whereNumber('visit')->name('visits.show');
        Route::get('/visits/{visit}/edit', [VisitRecordController::class, 'editForm'])->whereNumber('visit')->name('visits.edit');
        Route::put('/visits/{visit}', [VisitRecordController::class, 'update'])->whereNumber('visit')->name('visits.update');

        Route::get('/reports', [StaffController::class, 'reports'])->name('reports');
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log');
        Route::get('/accounts', [StaffController::class, 'accounts'])->name('accounts');
    });
});

// Compatibility redirects for old page URLs shared in bookmarks.
Route::redirect('/auth/login.php', '/login');
Route::redirect('/auth/register.php', '/register');
Route::redirect('/auth/password_change.php', '/password/change');
Route::redirect('/auth/logout.php', '/logout');
Route::redirect('/patient/dashboard.php', '/patient/dashboard');
Route::redirect('/patient/profile.php', '/patient/profile');
Route::redirect('/patient/request.php', '/patient/appointments/request');
Route::redirect('/patient/appointments.php', '/patient/appointments');
Route::redirect('/patient/visit_history.php', '/patient/visits');
Route::redirect('/staff/dashboard.php', '/staff/dashboard');
Route::redirect('/staff/patients.php', '/staff/patients');
Route::redirect('/staff/appointments.php', '/staff/appointments');
Route::redirect('/staff/visit_records.php', '/staff/visits');
Route::redirect('/staff/activity_log.php', '/staff/activity-log');
Route::redirect('/staff/reports.php', '/staff/reports');
Route::redirect('/staff/accounts.php', '/staff/accounts');
Route::redirect('/staff/patient_create.php', '/staff/patients/create');
Route::get('/staff/patient_edit.php', [CompatibilityController::class, 'patientEdit']);
Route::get('/staff/patient_view.php', [CompatibilityController::class, 'patientVisits']);
Route::redirect('/staff/appointment_create.php', '/staff/appointments/create');
Route::redirect('/staff/appointment_requests.php', '/staff/appointments/requests');
Route::get('/staff/appointment_view.php', [CompatibilityController::class, 'appointmentView']);
Route::get('/staff/visit_create.php', [CompatibilityController::class, 'visitCreate']);
Route::get('/staff/visit_edit.php', [CompatibilityController::class, 'visitEdit']);
Route::get('/staff/visit_view.php', [CompatibilityController::class, 'visitView']);
Route::redirect('/staff/visit_history.php', '/staff/visits');
