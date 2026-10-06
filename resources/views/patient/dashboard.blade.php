@extends('layouts.app')

@section('title', 'Patient dashboard')

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">Patient Dashboard</h1>
            <p class="mb-2">Welcome back, {{ auth()->user()->name }}.</p>
            <span class="badge rounded-pill text-bg-light text-brand px-3 py-2">Patient number: {{ $patient?->patient_number ?? 'Not assigned' }}</span>
        </div>
        <a class="request-appointment-btn align-self-start" href="{{ route('patient.appointments.request') }}"><i class="bi bi-calendar-plus-fill" aria-hidden="true"></i>Request an appointment</a>
    </div>

    @php
        $patientStats = [
            ['Appointments', $appointmentCount, 'bi-calendar2-check-fill'],
            ['Pending Requests', $pendingCount, 'bi-clock-fill'],
            ['Visit Records', $visitCount, 'bi-file-earmark-medical-fill'],
        ];
    @endphp

    <div class="row g-3 g-xl-4 mb-4">
        @foreach($patientStats as [$label, $value, $icon])
            <div class="col-6 col-md-4">
                <section class="patient-stat" aria-label="{{ $label }}: {{ $value }}">
                    <div class="patient-stat-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
                    <div class="patient-stat-label">{{ $label }}</div>
                    <div class="patient-stat-value">{{ $value }}</div>
                </section>
            </div>
        @endforeach
    </div>

    <section class="card quick-actions mb-4">
        <h2 class="mb-3">Quick Actions</h2>
        <div class="row g-2 g-lg-3">
            <div class="col-12 col-sm-6 col-lg-3"><a class="quick-action" href="{{ route('patient.appointments.request') }}"><i class="bi bi-calendar-plus-fill" aria-hidden="true"></i>Request Appointment</a></div>
            <div class="col-12 col-sm-6 col-lg-3"><a class="quick-action" href="{{ route('patient.appointments') }}"><i class="bi bi-calendar2-check-fill" aria-hidden="true"></i>My Appointments</a></div>
            <div class="col-12 col-sm-6 col-lg-3"><a class="quick-action" href="{{ route('patient.visits') }}"><i class="bi bi-file-earmark-medical-fill" aria-hidden="true"></i>Visit History</a></div>
            <div class="col-12 col-sm-6 col-lg-3"><a class="quick-action" href="{{ route('patient.profile') }}"><i class="bi bi-person-fill-gear" aria-hidden="true"></i>Update Profile</a></div>
        </div>
    </section>

    <section class="card dashboard-lists overflow-hidden">
        <div class="card-header d-flex justify-content-between align-items-center py-3 px-4">
            <h2 class="h5 mb-0">Upcoming Confirmed Appointments</h2>
            <a class="small text-brand fw-semibold" href="{{ route('patient.appointments') }}">View all</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th class="ps-4">Date</th><th>Time</th><th class="pe-4">Reason</th></tr></thead>
                <tbody>
                    @forelse($upcoming as $appointment)
                        <tr>
                            <td class="ps-4">{{ $appointment->confirmed_date?->format('F j, Y') }}</td>
                            <td>{{ substr((string) $appointment->confirmed_start_time, 0, 5) }}–{{ substr((string) $appointment->confirmed_end_time, 0, 5) }}</td>
                            <td class="pe-4">{{ $appointment->reason ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-secondary py-4">No upcoming appointments.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
