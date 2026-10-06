@extends('layouts.app')

@section('title', 'Staff dashboard')

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">Staff Dashboard</h1>
            <p class="mb-0">Welcome back, {{ auth()->user()->name }}.</p>
        </div>
        <a class="btn pending-review-btn fw-semibold" href="{{ route('staff.appointments', ['status' => 'pending']) }}">
            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>
            Review pending appointments <span class="badge badge-pending ms-1">{{ $pendingCount }}</span>
        </a>
    </div>

    @php
        $dashboardStats = [
            ['Total Patients', $patientCount, 'bi-people-fill'],
            ['Appointments', $appointmentCount, 'bi-calendar2-check-fill'],
            ['Visit Records', $visitCount, 'bi-file-earmark-medical-fill'],
            ['Pending Appointments', $pendingCount, 'bi-clock-fill'],
            ["Today's Appointments", $todayAppointmentCount, 'bi-calendar-event-fill'],
            ['Confirmed Appointments', $confirmedCount, 'bi-calendar-check-fill'],
            ['Completed Appointments', $completedCount, 'bi-check-circle-fill'],
            ['Visit Records This Month', $monthVisitCount, 'bi-graph-up-arrow'],
            ['Appointment Completion', $completionRate.'%', 'bi-percent'],
        ];
    @endphp

    <div class="row g-3 g-xl-4 mb-4">
        @foreach($dashboardStats as [$label, $value, $icon])
            <div class="col-6 col-md-4 col-xl-3">
                <section class="dashboard-stat" aria-label="{{ $label }}: {{ $value }}">
                    <div class="dashboard-stat-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
                    <div class="dashboard-stat-label">{{ $label }}</div>
                    <div class="dashboard-stat-value">{{ $value }}</div>
                </section>
            </div>
        @endforeach
    </div>

    <section class="card quick-actions mb-4">
        <h2 class="mb-3">Quick Actions</h2>
        <div class="row g-2 g-lg-3">
            <div class="col-12 col-md-4"><a class="quick-action" href="{{ route('staff.patients') }}"><i class="bi bi-people-fill" aria-hidden="true"></i>Manage Patients</a></div>
            <div class="col-12 col-md-4"><a class="quick-action" href="{{ route('staff.appointments') }}"><i class="bi bi-calendar2-check-fill" aria-hidden="true"></i>Manage Appointments</a></div>
            <div class="col-12 col-md-4"><a class="quick-action" href="{{ route('staff.visits') }}"><i class="bi bi-file-earmark-medical-fill" aria-hidden="true"></i>Visit Records</a></div>
        </div>
        <div class="row g-2 g-lg-3 mt-0">
            <div class="col-12 col-md-4"><a class="quick-action" href="{{ route('staff.patients.create') }}"><i class="bi bi-person-plus-fill" aria-hidden="true"></i>Register a Patient</a></div>
            <div class="col-12 col-md-4"><a class="quick-action" href="{{ route('staff.appointments.create') }}"><i class="bi bi-calendar-plus-fill" aria-hidden="true"></i>Create Appointment</a></div>
            <div class="col-12 col-md-4"><a class="quick-action" href="{{ route('staff.reports') }}"><i class="bi bi-bar-chart-fill" aria-hidden="true"></i>View Reports</a></div>
        </div>
    </section>

    <div class="row g-3 dashboard-lists">
        <div class="col-lg-7">
            <section class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-3 px-4">
                    <h2 class="h5 mb-0">Today's Confirmed Appointments</h2>
                    <a class="small text-brand fw-semibold" href="{{ route('staff.appointments') }}">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th class="ps-4">Time</th><th>Patient</th><th class="pe-4">Reason</th></tr></thead>
                        <tbody>
                            @forelse($todayAppointments as $appointment)
                                <tr>
                                    <td class="ps-4">{{ substr((string) $appointment->confirmed_start_time, 0, 5) }}</td>
                                    <td>{{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name }}</td>
                                    <td class="pe-4"><a href="{{ route('staff.appointments.show', $appointment) }}">{{ $appointment->reason ?: 'Appointment' }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-secondary p-4">No confirmed appointments today.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-lg-5">
            <section class="card h-100">
                <div class="card-header py-3 px-4"><h2 class="h5 mb-0">Latest Appointment Requests</h2></div>
                <div class="list-group list-group-flush">
                    @forelse($latestAppointments as $appointment)
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2 px-4 py-3" href="{{ route('staff.appointments.show', $appointment) }}">
                            <span>{{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name }}<small class="d-block text-secondary">{{ $appointment->created_at?->format('M j, Y g:i A') }}</small></span>
                            <span class="badge badge-{{ $appointment->status }}">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>
                        </a>
                    @empty
                        <div class="p-4 text-secondary">No appointment requests yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
