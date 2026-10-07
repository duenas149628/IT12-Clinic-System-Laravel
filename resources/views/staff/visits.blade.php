@extends('layouts.app')

@section('title', 'Visit records')

@section('content')
    <h1 class="h2 text-brand">Visit records</h1>
    <p class="text-secondary">Search clinical visit history.</p>

    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search patient or visit details</label>
                    <input class="form-control" name="search" value="{{ $search }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Visit date</label>
                    <input class="form-control" type="date" name="visit_date" value="{{ $date }}">
                </div>
                <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Date</th><th>Patient number</th><th>Patient</th><th>Chief complaint</th><th></th></tr></thead>
                <tbody>
                    @forelse($visits as $v)
                        <tr>
                            <td>{{ $v->visit_date?->format('M j, Y') }}</td>
                            <td>{{ $v->patient?->patient_number }}</td>
                            <td>{{ $v->patient?->first_name }} {{ $v->patient?->last_name }}</td>
                            <td>{{ $v->chief_complaint }}</td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('staff.visits.show', $v) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-5 text-center text-secondary">No visit records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($visits->count() < $visitsTotal)
            <div class="p-3 text-center">
                <a class="btn btn-outline-primary" href="{{ request()->fullUrlWithQuery(['visits_rows' => min($visitsTotal, $visitsRows + 5)]) }}">Show 5 more rows</a>
            </div>
        @endif
    </div>
@endsection
