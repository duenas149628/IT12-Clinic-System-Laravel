@extends('layouts.app')

@section('title', 'Appointments')

@section('content')
    <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h2 text-brand">Appointments</h1>
            <p class="text-secondary">Review the schedule and patient requests.</p>
        </div>
        <a class="header-action-btn align-self-start" href="{{ route('staff.appointments.create') }}">
            <i class="bi bi-calendar-plus-fill" aria-hidden="true"></i>Create appointment
        </a>
    </div>

    <div class="row g-3 align-items-start mb-4">
        <aside class="col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h2 class="h5 text-brand mb-3">Filter appointments</h2>
                    <form class="d-grid gap-3">
                        <div>
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="all">All statuses</option>
                                @foreach($statusOptions as $s)
                                    <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Date</label>
                            <input class="form-control" type="date" name="date" value="{{ $date }}">
                        </div>
                        <div>
                            <label class="form-label">Patient search</label>
                            <input class="form-control" name="search" maxlength="100" value="{{ $search }}">
                        </div>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary">Filter</button>
                            <a class="btn btn-outline-secondary" href="{{ route('staff.appointments') }}">Clear filters</a>
                        </div>
                    </form>
                </div>
            </div>
        </aside>

        <div class="col-lg-9">
            <section class="card p-3 p-lg-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                        <h2 class="h4 text-brand">Visual schedule · {{ $calendarMonth->format('F Y') }}</h2>
                        <p class="text-secondary mb-0">Green marks confirmed appointments; amber marks pending requests needing attention.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="{{ route('staff.appointments', ['month' => $calendarMonth->copy()->subMonth()->format('Y-m')]) }}" aria-label="Previous month">‹</a>
                        <a class="btn btn-outline-secondary" href="{{ route('staff.appointments', ['month' => now()->format('Y-m')]) }}">This month</a>
                        <a class="btn btn-outline-secondary" href="{{ route('staff.appointments', ['month' => $calendarMonth->copy()->addMonth()->format('Y-m')]) }}" aria-label="Next month">›</a>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-xl-7">
                        <div class="calendar-grid mb-1">
                            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                                <div class="text-center small fw-bold text-secondary">{{ $day }}</div>
                            @endforeach
                        </div>
                        <div class="calendar-grid">
                            @foreach($calendarDays as $day)
                                @php($key = $day->format('Y-m-d'))
                                <a class="calendar-day {{ $day->month !== $calendarMonth->month ? 'outside' : '' }} {{ $key === $selectedDate ? 'selected' : '' }}" href="{{ route('staff.appointments', ['month' => $calendarMonth->format('Y-m'), 'selected_date' => $key]) }}">
                                    <span class="fw-semibold">{{ $day->day }}</span>
                                    <div class="calendar-badges">
                                        @if(isset($confirmedDays[$key]))
                                            <span class="badge text-bg-success">{{ $confirmedDays[$key]->count() }} appt{{ $confirmedDays[$key]->count() === 1 ? '' : 's' }}</span>
                                        @endif
                                        @if(($pendingDays[$key] ?? 0) > 0)
                                            <span class="badge badge-pending">{{ $pendingDays[$key] }} pending</span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-xl-5">
                        <div class="border rounded-4 p-3 h-100">
                            <h3 class="h5">{{ \Carbon\Carbon::parse($selectedDate)->format('l, F j') }}</h3>
                            <p class="small text-secondary">{{ $dayAppointments->count() }} appointment{{ $dayAppointments->count() === 1 ? '' : 's' }} or pending request{{ $dayAppointments->count() === 1 ? '' : 's' }}</p>
                            <div class="d-grid gap-2">
                                @forelse($dayAppointments as $a)
                                    <a class="text-decoration-none border rounded p-2 d-flex justify-content-between gap-2" href="{{ route('staff.appointments.show', $a) }}">
                                        <span>
                                            <strong>{{ $a->status === 'confirmed' ? substr((string) $a->confirmed_start_time, 0, 5) : substr((string) $a->preferred_start_time, 0, 5) }}</strong>
                                            · {{ $a->patient?->first_name }} {{ $a->patient?->last_name }}
                                            <small class="d-block text-secondary">{{ $a->reason }}</small>
                                        </span>
                                        <span class="badge badge-{{ $a->status }}">{{ ucfirst($a->status) }}</span>
                                    </a>
                                @empty
                                    <div class="text-center text-secondary border rounded p-4">No appointments for this day.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card p-3">
                <span class="text-secondary">Pending requests needing attention</span>
                <strong class="fs-3">{{ $pendingCount }}</strong>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3">
                <span class="text-secondary">Confirmed today</span>
                <strong class="fs-3">{{ $todayCount }}</strong>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th>Status</th><th>Created</th><th>Patient</th><th>Appointment date</th><th>Time</th><th>Reason</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($appointments as $a)
                        <tr>
                            <td><span class="badge badge-{{ $a->status }}">{{ ucfirst(str_replace('_', ' ', $a->status)) }}</span></td>
                            <td>{{ $a->created_at?->format('M j, Y') }}</td>
                            <td>{{ $a->patient?->first_name }} {{ $a->patient?->last_name }}<small class="d-block text-secondary">{{ $a->patient?->patient_number }}</small></td>
                            <td>{{ ($a->confirmed_date ?? $a->preferred_date)?->format('M j, Y') }}</td>
                            <td>{{ substr((string) ($a->confirmed_start_time ?? $a->preferred_start_time), 0, 5) }}</td>
                            <td>{{ $a->reason }}</td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('staff.appointments.show', $a) }}">Manage</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center p-5 text-secondary">No appointments match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($appointments->count() < $appointmentsTotal)
            <div class="p-3 text-center">
                <a class="btn btn-outline-primary" href="{{ request()->fullUrlWithQuery(['appointments_rows' => min($appointmentsTotal, $appointmentsRows + 5)]) }}">Show 5 more rows</a>
            </div>
        @endif
    </div>
@endsection
