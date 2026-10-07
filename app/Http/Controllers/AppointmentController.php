<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\VisitRecord;
use App\Support\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    private const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'];

    public function pendingRequests(): RedirectResponse
    {
        return redirect()->route('staff.appointments', ['status' => 'pending']);
    }

    public function requestForm(): View
    {
        $patient = Auth::user()->patient()->firstOrFail();

        return view('patient.request', compact('patient'));
    }

    public function createRequest(Request $request): RedirectResponse
    {
        $patient = Auth::user()->patient()->firstOrFail();
        $data = $request->validate([
            'preferred_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'preferred_start_time' => ['required', 'date_format:H:i', 'after_or_equal:09:00', 'before_or_equal:20:00'],
            'preferred_end_time' => ['required', 'date_format:H:i', 'after:preferred_start_time', 'before_or_equal:20:00'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        if ($data['preferred_date'] === today()->toDateString()
            && $data['preferred_start_time'] <= now()->format('H:i')) {
            throw ValidationException::withMessages([
                'preferred_start_time' => 'For today, choose a start time that has not passed.',
            ]);
        }

        $appointment = $patient->appointments()->create($data + ['status' => 'pending']);
        AuditLog::write('appointment.request.created', ['appointment_id' => $appointment->appointment_id]);

        return redirect()->route('patient.appointments.request')->with('status', 'Appointment request submitted successfully.');
    }

    public function patientIndex(Request $request): View
    {
        $appointmentsRows = $this->rowLimit($request, 'appointments_rows');
        $patient = Auth::user()->patient()->firstOrFail();
        $statusOptions = self::STATUSES;
        $status = in_array($request->query('status'), $statusOptions, true) ? $request->query('status') : 'all';
        $date = $this->validDate($request->query('date')) ? $request->query('date') : '';

        $query = $patient->appointments()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($date !== '', fn ($query) => $query->whereRaw('COALESCE(confirmed_date, preferred_date) = ?', [$date]))
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'confirmed' THEN 2 WHEN 'completed' THEN 3 WHEN 'cancelled' THEN 4 ELSE 5 END")
            ->orderByRaw('COALESCE(confirmed_date, preferred_date) DESC')
            ->orderByDesc('appointment_id');
        $appointmentsTotal = (clone $query)->count();
        $appointments = $query->limit($appointmentsRows)->get();

        return view('patient.appointments', compact('patient', 'appointments', 'appointmentsTotal', 'appointmentsRows', 'status', 'date', 'statusOptions'));
    }

    public function staffIndex(Request $request): View
    {
        $appointmentsRows = $this->rowLimit($request, 'appointments_rows');
        $statusOptions = self::STATUSES;
        $status = in_array($request->query('status'), $statusOptions, true) ? $request->query('status') : 'all';
        $date = $this->validDate($request->query('date')) ? $request->query('date') : '';
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 100);
        $todayView = $request->query('view') === 'today';

        $query = Appointment::query()->with('patient');
        if ($todayView) {
            $status = 'confirmed';
            $date = today()->toDateString();
            $query->where('status', 'confirmed')->whereDate('confirmed_date', $date);
        } else {
            $query->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->when($date !== '', fn ($q) => $q->whereRaw('COALESCE(confirmed_date, preferred_date) = ?', [$date]));
        }

        if ($search !== '') {
            $query->whereHas('patient', function ($patientQuery) use ($search): void {
                $patientQuery->where(function ($q) use ($search): void {
                    $needle = '%'.addcslashes($search, '%_\\').'%';
                    $q->where('first_name', 'like', $needle)
                        ->orWhere('last_name', 'like', $needle)
                        ->orWhere('patient_number', 'like', $needle);
                });
            });
        }

        $appointmentsTotal = (clone $query)->count();
        $appointments = $query->orderByDesc('created_at')->orderByDesc('appointment_id')->limit($appointmentsRows)->get();
        $pendingCount = Appointment::where('status', 'pending')->count();
        $todayCount = Appointment::where('status', 'confirmed')->whereDate('confirmed_date', today())->count();

        $month = (string) $request->query('month', $date !== '' ? substr($date, 0, 7) : now()->format('Y-m'));
        $calendarMonth = preg_match('/^\d{4}-\d{2}$/', $month)
            ? CarbonImmutable::createFromFormat('!Y-m', $month)
            : null;
        if (! $calendarMonth || $calendarMonth->format('Y-m') !== $month) {
            $calendarMonth = now()->startOfMonth()->toImmutable();
        }
        $monthStart = $calendarMonth->startOfMonth();
        $nextMonth = $monthStart->addMonth();

        $confirmedDays = Appointment::with('patient')
            ->where('status', 'confirmed')
            ->whereDate('confirmed_date', '>=', $monthStart->toDateString())
            ->whereDate('confirmed_date', '<', $nextMonth->toDateString())
            ->orderBy('confirmed_start_time')
            ->get()
            ->groupBy(fn (Appointment $appointment) => $appointment->confirmed_date->toDateString());

        $pendingDays = Appointment::query()
            ->selectRaw('preferred_date, COUNT(*) as total')
            ->where('status', 'pending')
            ->whereDate('preferred_date', '>=', $monthStart->toDateString())
            ->whereDate('preferred_date', '<', $nextMonth->toDateString())
            ->groupBy('preferred_date')
            ->pluck('total', 'preferred_date');

        $selectedDate = $this->validDate($request->query('selected_date'))
            ? $request->query('selected_date')
            : ($date !== '' ? $date : today()->toDateString());

        $dayAppointments = Appointment::with('patient')
            ->where(function ($query) use ($selectedDate): void {
                $query->where(function ($pending) use ($selectedDate): void {
                    $pending->where('status', 'pending')->whereDate('preferred_date', $selectedDate);
                })->orWhere(function ($confirmed) use ($selectedDate): void {
                    $confirmed->where('status', 'confirmed')->whereDate('confirmed_date', $selectedDate);
                });
            })
            ->orderByRaw("CASE status WHEN 'confirmed' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE WHEN status = 'confirmed' THEN confirmed_start_time ELSE preferred_start_time END")
            ->get();

        $gridStart = $monthStart->startOfWeek(CarbonImmutable::MONDAY);
        $calendarDays = collect(range(0, 41))->map(fn (int $offset) => $gridStart->addDays($offset));

        return view('staff.appointments', compact(
            'appointments', 'appointmentsTotal', 'appointmentsRows', 'pendingCount', 'todayCount', 'statusOptions', 'status', 'date',
            'search', 'todayView', 'calendarMonth', 'calendarDays', 'confirmedDays',
            'pendingDays', 'selectedDate', 'dayAppointments'
        ));
    }

    private function rowLimit(Request $request, string $key): int
    {
        $rows = filter_var($request->query($key, 5), FILTER_VALIDATE_INT);

        return min(500, max(5, is_int($rows) ? (int) (ceil($rows / 5) * 5) : 5));
    }

    public function staffCreateForm(Request $request): View
    {
        $patients = Patient::query()->orderBy('last_name')->orderBy('first_name')->get();

        return view('staff.appointment-form', [
            'patients' => $patients,
            'patientId' => $request->query('patient_id'),
        ]);
    }

    public function staffCreate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,patient_id'],
            'preferred_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'preferred_start_time' => ['required', 'date_format:H:i'],
            'preferred_end_time' => ['required', 'date_format:H:i', 'after:preferred_start_time'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $appointment = Appointment::create($data + ['status' => 'pending']);
        AuditLog::write('appointment.request.created_by_staff', [
            'appointment_id' => $appointment->appointment_id,
            'patient_id' => $appointment->patient_id,
        ]);

        return redirect()->route('staff.appointments.show', $appointment)->with('status', 'Pending appointment request created.');
    }

    public function staffShow(Appointment $appointment): View
    {
        $appointment->load(['patient', 'visitRecord']);

        return view('staff.appointment-show', compact('appointment'));
    }

    public function staffAction(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:accept,confirm,reschedule,cancel,completed,no_show'],
            'confirmed_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'confirmed_start_time' => ['nullable', 'date_format:H:i'],
            'confirmed_end_time' => ['nullable', 'date_format:H:i', 'after:confirmed_start_time'],
        ]);
        $action = $data['action'];
        AuditLog::write('appointment.action.submitted', [
            'appointment_id' => $appointment->appointment_id,
            'action' => $action,
        ]);

        if (in_array($action, ['accept', 'confirm'], true) && $appointment->status !== 'pending') {
            return back()->withErrors(['action' => 'Only pending appointments can be confirmed.']);
        }
        if ($action === 'reschedule' && $appointment->status !== 'confirmed') {
            return back()->withErrors(['action' => 'Only confirmed appointments can be rescheduled.']);
        }
        if (in_array($action, ['completed', 'no_show'], true) && $appointment->status !== 'confirmed') {
            return back()->withErrors(['action' => 'Only confirmed appointments can be marked completed or no-show.']);
        }

        if (in_array($action, ['accept', 'confirm', 'reschedule'], true)) {
            $date = $action === 'accept' ? $appointment->preferred_date->toDateString() : ($data['confirmed_date'] ?? '');
            $start = $action === 'accept' ? substr((string) $appointment->preferred_start_time, 0, 5) : ($data['confirmed_start_time'] ?? '');
            $end = $action === 'accept' ? substr((string) $appointment->preferred_end_time, 0, 5) : ($data['confirmed_end_time'] ?? '');

            if ($date === '' || $start === '' || $end === '' || $start >= $end) {
                return back()->withErrors(['confirmed_start_time' => 'Enter a valid date and time range, with the end after the start.'])->withInput();
            }

            $conflict = DB::transaction(function () use ($appointment, $date, $start, $end): bool {
                $lockedAppointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->appointment_id);
                $conflict = Appointment::query()
                    ->where('status', 'confirmed')
                    ->whereDate('confirmed_date', $date)
                    ->where('confirmed_start_time', '<', $end)
                    ->where('confirmed_end_time', '>', $start)
                    ->where('appointment_id', '!=', $lockedAppointment->appointment_id)
                    ->lockForUpdate()
                    ->exists();

                if ($conflict) {
                    return true;
                }

                $lockedAppointment->update([
                    'confirmed_date' => $date,
                    'confirmed_start_time' => $start,
                    'confirmed_end_time' => $end,
                    'status' => 'confirmed',
                ]);

                return false;
            });

            if ($conflict) {
                return back()->withErrors(['confirmed_start_time' => 'This time overlaps an existing confirmed appointment.'])->withInput();
            }

            AuditLog::write('appointment.schedule.confirmed', ['appointment_id' => $appointment->appointment_id]);

            return back()->with('status', match ($action) {
                'accept' => 'Appointment accepted successfully.',
                'reschedule' => 'Appointment rescheduled successfully.',
                default => 'Appointment confirmed successfully.',
            });
        }

        if ($action === 'cancel') {
            $appointment->update([
                'confirmed_date' => null,
                'confirmed_start_time' => null,
                'confirmed_end_time' => null,
                'status' => 'cancelled',
            ]);
            AuditLog::write('appointment.cancelled', ['appointment_id' => $appointment->appointment_id]);

            return back()->with('status', 'Appointment cancelled successfully.');
        }

        if ($action === 'completed') {
            DB::transaction(function () use ($appointment): void {
                $locked = Appointment::query()->lockForUpdate()->findOrFail($appointment->appointment_id);
                if ($locked->status !== 'confirmed') {
                    throw ValidationException::withMessages(['action' => 'Only confirmed appointments can be marked as completed.']);
                }

                $locked->update(['status' => 'completed']);
                VisitRecord::firstOrCreate(
                    ['appointment_id' => $locked->appointment_id],
                    [
                        'patient_id' => $locked->patient_id,
                        'visit_date' => $locked->confirmed_date,
                        'chief_complaint' => '',
                        'findings' => '',
                        'treatment' => '',
                        'notes' => '',
                        'follow_up' => '',
                    ]
                );
            });
            AuditLog::write('appointment.completed', ['appointment_id' => $appointment->appointment_id]);

            return back()->with('status', 'Appointment completed and visit record created successfully.');
        }

        $appointment->update(['status' => 'no_show']);
        AuditLog::write('appointment.no_show', ['appointment_id' => $appointment->appointment_id]);

        return back()->with('status', 'Appointment marked as no-show.');
    }

    private function validDate(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
            && \DateTimeImmutable::createFromFormat('!Y-m-d', $value)?->format('Y-m-d') === $value;
    }
}
