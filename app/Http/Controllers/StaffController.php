<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\VisitRecord;
use App\Support\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function dashboard(): View
    {
        $appointmentCount = Appointment::count();
        $completedCount = Appointment::where('status', 'completed')->count();

        return view('staff.dashboard', [
            'patientCount' => Patient::count(),
            'appointmentCount' => $appointmentCount,
            'visitCount' => VisitRecord::count(),
            'pendingCount' => Appointment::where('status', 'pending')->count(),
            'todayAppointmentCount' => Appointment::where('status', 'confirmed')->whereDate('confirmed_date', today())->count(),
            'confirmedCount' => Appointment::where('status', 'confirmed')->count(),
            'completedCount' => $completedCount,
            'monthVisitCount' => VisitRecord::whereBetween('visit_date', [now()->startOfMonth()->toDateString(), today()->toDateString()])->count(),
            'completionRate' => $appointmentCount > 0 ? (int) round($completedCount / $appointmentCount * 100) : 0,
            'todayAppointments' => Appointment::with('patient')->where('status', 'confirmed')->whereDate('confirmed_date', today())->orderBy('confirmed_start_time')->get(),
            'latestAppointments' => Appointment::with('patient')->orderByDesc('created_at')->orderByDesc('appointment_id')->limit(5)->get(),
        ]);
    }

    public function patients(Request $request): View
    {
        $patientsRows = $this->rowLimit($request, 'patients_rows');
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 100);
        $query = Patient::query()->with('user')
            ->when($search !== '', function ($query) use ($search): void {
                $needle = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($q) use ($needle): void {
                    $q->where('patient_number', 'like', $needle)
                        ->orWhere('first_name', 'like', $needle)
                        ->orWhere('last_name', 'like', $needle)
                        ->orWhere('contact_number', 'like', $needle)
                        ->orWhereHas('user', fn ($user) => $user->where('email', 'like', $needle));
                });
            })
            ->orderBy('last_name')->orderBy('first_name');

        $patientsTotal = (clone $query)->count();
        $patients = $query->limit($patientsRows)->get();

        return view('staff.patients', compact('patients', 'patientsTotal', 'patientsRows', 'search'));
    }

    public function patientCreateForm(): View
    {
        return view('staff.patient-form', ['patient' => null, 'createdAccount' => session('created_patient_credentials')]);
    }

    public function patientCreate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'sex' => ['nullable', 'in:Male,Female'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        if (mb_strlen(trim($data['first_name'].' '.$data['last_name'])) > 100) {
            throw ValidationException::withMessages(['first_name' => 'The combined name must be 100 characters or fewer.']);
        }

        $temporaryPassword = Str::random(24);
        $account = DB::transaction(function () use ($data, $temporaryPassword): array {
            $user = User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'password' => $temporaryPassword,
                'role' => 'patient',
                'must_change_password' => true,
            ]);
            $patientData = collect($data)->except('email')->all();
            $patient = Patient::create($patientData + [
                'user_id' => $user->user_id,
                'patient_number' => 'TMP-'.Str::random(12),
            ]);
            $patient->patient_number = 'TL-'.str_pad((string) $patient->patient_id, 5, '0', STR_PAD_LEFT);
            $patient->save();

            return [
                'user_id' => $user->user_id,
                'patient_id' => $patient->patient_id,
                'patient_number' => $patient->patient_number,
                'email' => $user->email,
                'temporary_password' => $temporaryPassword,
            ];
        });

        AuditLog::write('patient.account.created_by_staff', [
            'new_user_id' => $account['user_id'],
            'patient_id' => $account['patient_id'],
        ]);

        return redirect()->route('staff.patients.create')
            ->with('created_patient_credentials', $account)
            ->with('status', 'Patient account created.');
    }

    public function patientShow(Patient $patient): View
    {
        $patient->load([
            'user',
            'appointments' => fn ($query) => $query->orderByDesc('created_at')->orderByDesc('appointment_id'),
            'visitRecords' => fn ($query) => $query->orderByDesc('visit_date')->orderByDesc('visit_id'),
        ]);

        return view('staff.patient-show', compact('patient'));
    }

    public function patientEditForm(Patient $patient): View
    {
        $patient->load('user');

        return view('staff.patient-form', ['patient' => $patient, 'createdAccount' => null]);
    }

    public function patientUpdate(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'sex' => ['nullable', 'in:Male,Female'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);
        if (mb_strlen(trim($data['first_name'].' '.$data['last_name'])) > 100) {
            throw ValidationException::withMessages(['first_name' => 'The combined name must be 100 characters or fewer.']);
        }

        DB::transaction(function () use ($patient, $data): void {
            $patient->update($data);
            $patient->user?->update(['name' => trim($data['first_name'].' '.$data['last_name'])]);
        });

        AuditLog::write('patient.record.updated', ['patient_id' => $patient->patient_id]);

        return redirect()->route('staff.patients.show', $patient)->with('status', 'Patient details updated.');
    }

    public function accounts(): View
    {
        return view('staff.accounts', [
            'staffAccounts' => User::where('role', 'staff')->orderBy('name')->orderBy('user_id')->get(),
        ]);
    }

    public function reports(Request $request): View
    {
        $reportAppointmentsRows = $this->rowLimit($request, 'report_appointments_rows');
        $reportVisitsRows = $this->rowLimit($request, 'report_visits_rows');
        $startDefault = now()->startOfMonth()->toDateString();
        $endDefault = today()->toDateString();
        $start = (string) $request->query('start_date', $startDefault);
        $end = (string) $request->query('end_date', $endDefault);
        $valid = $this->validDate($start) && $this->validDate($end) && $start <= $end;
        if (! $valid) {
            $start = $startDefault;
            $end = $endDefault;
        }

        $statusCounts = [];
        $appointments = collect();
        $visits = collect();
        $appointmentsTotal = 0;
        $visitsTotal = 0;
        if ($valid) {
            $statusCounts = Appointment::query()
                ->selectRaw('status, COUNT(*) as total')
                ->whereRaw('COALESCE(confirmed_date, preferred_date) BETWEEN ? AND ?', [$start, $end])
                ->groupBy('status')->pluck('total', 'status')->all();

            $appointmentsQuery = Appointment::with('patient')
                ->whereRaw('COALESCE(confirmed_date, preferred_date) BETWEEN ? AND ?', [$start, $end])
                ->orderByRaw('COALESCE(confirmed_date, preferred_date) DESC')
                ->orderByDesc('appointment_id');
            $appointmentsTotal = (clone $appointmentsQuery)->count();
            $appointments = $appointmentsQuery->limit($reportAppointmentsRows)->get();

            $visitsQuery = VisitRecord::with('patient')
                ->whereBetween('visit_date', [$start, $end])
                ->orderByDesc('visit_date')->orderByDesc('visit_id');
            $visitsTotal = (clone $visitsQuery)->count();
            $visits = $visitsQuery->limit($reportVisitsRows)->get();
        }

        return view('staff.reports', compact(
            'start', 'end', 'valid', 'statusCounts', 'appointments', 'visits',
            'appointmentsTotal', 'visitsTotal', 'reportAppointmentsRows', 'reportVisitsRows'
        ));
    }

    private function rowLimit(Request $request, string $key): int
    {
        $rows = filter_var($request->query($key, 5), FILTER_VALIDATE_INT);

        return min(500, max(5, is_int($rows) ? (int) (ceil($rows / 5) * 5) : 5));
    }

    private function validDate(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            && CarbonImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') === $date;
    }
}
