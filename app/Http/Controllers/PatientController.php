<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Support\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function dashboard(): View
    {
        $patient = Auth::user()->patient;
        $appointments = $patient?->appointments();

        return view('patient.dashboard', [
            'patient' => $patient,
            'appointmentCount' => $appointments ? (clone $appointments)->count() : 0,
            'pendingCount' => $appointments ? (clone $appointments)->where('status', 'pending')->count() : 0,
            'visitCount' => $patient?->visitRecords()->count() ?? 0,
            'upcoming' => $appointments
                ? (clone $appointments)->where('status', 'confirmed')->whereDate('confirmed_date', '>=', today())
                    ->orderBy('confirmed_date')->orderBy('confirmed_start_time')->limit(5)->get()
                : collect(),
        ]);
    }

    public function profile(): View
    {
        return view('patient.profile', ['patient' => $this->currentPatient()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $patient = $this->currentPatient();
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'sex' => ['nullable', 'in:Male,Female'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        if (mb_strlen(trim($data['first_name'].' '.$data['last_name'])) > 100) {
            return back()->withErrors(['first_name' => 'The combined name must be 100 characters or fewer.'])->withInput();
        }

        DB::transaction(function () use ($patient, $data): void {
            $patient->update($data);
            $user = $patient->user;
            if ($user) {
                $user->update(['name' => trim($data['first_name'].' '.$data['last_name'])]);
            }
        });

        AuditLog::write('patient.profile.updated', ['patient_id' => $patient->patient_id]);

        return redirect()->route('patient.profile')->with('status', 'Profile updated successfully.');
    }

    private function currentPatient(): Patient
    {
        return Auth::user()->patient()->firstOrFail();
    }
}
