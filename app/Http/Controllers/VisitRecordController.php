<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\VisitRecord;
use App\Support\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VisitRecordController extends Controller
{
    public function patientIndex(Request $request): View
    {
        $patient = Auth::user()->patient()->firstOrFail();
        $date = (string) $request->query('visit_date', '');
        if (! $this->validDate($date)) {
            $date = '';
        }

        $visits = $patient->visitRecords()
            ->when($date !== '', fn ($query) => $query->whereDate('visit_date', $date))
            ->orderByDesc('visit_date')->orderByDesc('visit_id')->get();

        return view('patient.visits', compact('patient', 'visits', 'date'));
    }

    public function staffIndex(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 100);
        $date = (string) $request->query('visit_date', '');
        if (! $this->validDate($date)) {
            $date = '';
        }

        $visits = VisitRecord::with('patient')
            ->when($date !== '', fn ($query) => $query->whereDate('visit_date', $date))
            ->when($search !== '', function ($query) use ($search): void {
                $needle = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($q) use ($needle): void {
                    $q->whereHas('patient', function ($patient) use ($needle): void {
                        $patient->where('patient_number', 'like', $needle)
                            ->orWhere('first_name', 'like', $needle)
                            ->orWhere('last_name', 'like', $needle);
                    })->orWhere('chief_complaint', 'like', $needle)
                        ->orWhere('treatment', 'like', $needle);
                });
            })
            ->orderByDesc('visit_date')->orderByDesc('visit_id')
            ->paginate(15)->withQueryString();

        return view('staff.visits', compact('visits', 'search', 'date'));
    }

    public function createForm(Request $request): View|RedirectResponse
    {
        $appointment = Appointment::with('patient')->findOrFail($request->integer('appointment_id'));
        abort_unless($appointment->status === 'completed', 404);

        if ($appointment->visitRecord) {
            return redirect()->route('staff.visits.show', $appointment->visitRecord);
        }

        return view('staff.visit-form', ['visit' => null, 'appointment' => $appointment]);
    }

    public function create(Request $request): RedirectResponse
    {
        $data = $this->validatedVisit($request);
        $appointment = Appointment::with('patient')->findOrFail($data['appointment_id']);
        if ($appointment->status !== 'completed') {
            throw ValidationException::withMessages(['appointment_id' => 'A visit record can only be created for a completed appointment.']);
        }
        if ($appointment->visitRecord) {
            return redirect()->route('staff.visits.show', $appointment->visitRecord);
        }

        $visit = VisitRecord::create([
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->appointment_id,
            'visit_date' => $appointment->confirmed_date,
            'chief_complaint' => $data['chief_complaint'] ?? '',
            'findings' => $data['findings'] ?? '',
            'treatment' => $data['treatment'] ?? '',
            'notes' => $data['notes'] ?? '',
            'follow_up' => $data['follow_up'] ?? '',
        ]);

        AuditLog::write('visit.record.created', ['visit_id' => $visit->visit_id]);

        return redirect()->route('staff.visits.show', $visit)->with('status', 'Visit record created.');
    }

    public function show(VisitRecord $visit): View
    {
        $visit->load(['patient', 'appointment']);

        return view('staff.visit-show', compact('visit'));
    }

    public function editForm(VisitRecord $visit): View
    {
        $visit->load(['patient', 'appointment']);

        return view('staff.visit-form', ['visit' => $visit, 'appointment' => $visit->appointment]);
    }

    public function update(Request $request, VisitRecord $visit): RedirectResponse
    {
        $data = $this->validatedVisit($request);
        unset($data['appointment_id']);

        $visit->update($data);
        AuditLog::write('visit.record.updated', ['visit_id' => $visit->visit_id]);

        return redirect()->route('staff.visits.show', $visit)->with('status', 'Visit record updated.');
    }

    private function validatedVisit(Request $request): array
    {
        $data = $request->validate([
            'appointment_id' => ['required', 'integer', 'exists:appointments,appointment_id'],
            'chief_complaint' => ['nullable', 'string', 'max:60000'],
            'findings' => ['nullable', 'string', 'max:60000'],
            'treatment' => ['nullable', 'string', 'max:60000'],
            'notes' => ['nullable', 'string', 'max:60000'],
            'follow_up' => ['nullable', 'string', 'max:60000'],
        ]);

        if (collect($data)->only(['chief_complaint', 'findings', 'treatment', 'notes', 'follow_up'])->every(fn ($value) => trim((string) $value) === '')) {
            throw ValidationException::withMessages(['chief_complaint' => 'Please enter at least one visit detail.']);
        }

        return $data;
    }

    private function validDate(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            && \DateTimeImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') === $date;
    }
}
