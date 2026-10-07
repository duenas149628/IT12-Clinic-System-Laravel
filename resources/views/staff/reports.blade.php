@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <div class="d-flex justify-content-between">
        <div>
            <h1 class="h2 text-brand">Reports</h1>
            <p class="text-secondary">Appointment and visit activity for a date range.</p>
        </div>
        <button class="header-action-btn screen-only align-self-start" id="print-report" type="button"><i class="bi bi-printer-fill" aria-hidden="true"></i>Print report</button>
    </div>

    <div class="card mb-4 p-3 screen-only">
        <form class="row g-3 align-items-end">
            <div class="col-md-4"><label class="form-label">Start date</label><input class="form-control" type="date" name="start_date" value="{{ $start }}" required></div>
            <div class="col-md-4"><label class="form-label">End date</label><input class="form-control" type="date" name="end_date" value="{{ $end }}" required></div>
            <div class="col-auto"><button class="btn btn-primary">Apply range</button><a class="btn btn-outline-secondary ms-2" href="{{ route('staff.reports') }}">This month</a></div>
        </form>
    </div>

    @if(!$valid)<div class="alert alert-warning">Choose a valid date range with the start date on or before the end date.</div>@endif

    <div class="row g-3 mb-4">
        @foreach(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'] as $key => $label)
            <div class="col"><div class="card p-3"><span class="text-secondary">{{ $label }}</span><strong class="fs-3">{{ $statusCounts[$key] ?? 0 }}</strong></div></div>
        @endforeach
    </div>

    <div class="card mb-4">
        <div class="card-header"><h2 class="h5">Appointments · {{ $start }} to {{ $end }}</h2></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Status</th><th>Reason</th></tr></thead>
                <tbody>
                    @forelse($appointments as $a)
                        <tr>
                            <td>{{ ($a->confirmed_date ?? $a->preferred_date)?->format('M j, Y') }}</td>
                            <td>{{ substr((string) ($a->confirmed_start_time ?? $a->preferred_start_time), 0, 5) }}–{{ substr((string) ($a->confirmed_end_time ?? $a->preferred_end_time), 0, 5) }}</td>
                            <td>{{ $a->patient?->first_name }} {{ $a->patient?->last_name }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $a->status)) }}</td>
                            <td>{{ $a->reason }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-4 text-secondary">No appointments in range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($appointments->count() < $appointmentsTotal)
            <div class="p-3 text-center screen-only">
                <a class="btn btn-outline-primary" href="{{ request()->fullUrlWithQuery(['report_appointments_rows' => min($appointmentsTotal, $reportAppointmentsRows + 5)]) }}">Show 5 more rows</a>
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><h2 class="h5">Visit records</h2></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Date</th><th>Patient</th><th>Complaint</th><th>Treatment</th></tr></thead>
                <tbody>
                    @forelse($visits as $v)
                        <tr>
                            <td>{{ $v->visit_date?->format('M j, Y') }}</td>
                            <td>{{ $v->patient?->first_name }} {{ $v->patient?->last_name }}</td>
                            <td>{{ $v->chief_complaint }}</td>
                            <td>{{ $v->treatment }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-4 text-secondary">No visits in range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($visits->count() < $visitsTotal)
            <div class="p-3 text-center screen-only">
                <a class="btn btn-outline-primary" href="{{ request()->fullUrlWithQuery(['report_visits_rows' => min($visitsTotal, $reportVisitsRows + 5)]) }}">Show 5 more rows</a>
            </div>
        @endif
    </div>
@endsection
