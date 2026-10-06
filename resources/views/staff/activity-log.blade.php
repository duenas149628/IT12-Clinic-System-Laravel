@extends('layouts.app')

@section('title', 'Activity log')

@section('content')
    <h1 class="h2 text-brand">Activity log</h1>
    <p class="text-secondary">A readable history of sign-ins and clinic updates, newest first.</p>

    @if($filterError)<div class="alert alert-warning">{{ $filterError }}</div>@endif

    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-2 align-items-end">
                <div class="col-md-2"><label class="form-label" for="from-date">From date (UTC)</label><input class="form-control" id="from-date" type="date" name="from_date" value="{{ $from }}"></div>
                <div class="col-md-2"><label class="form-label" for="to-date">To date (UTC)</label><input class="form-control" id="to-date" type="date" name="to_date" value="{{ $to }}"></div>
                <div class="col-md-2"><label class="form-label" for="event-filter">Activity</label><select class="form-select" id="event-filter" name="event"><option value="">All activity</option>@foreach($events as $key => $label)<option value="{{ $key }}" @selected($event === $key)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label" for="user-filter">Account ID</label><input class="form-control" id="user-filter" type="number" min="1" name="user_id" value="{{ $userId }}"></div>
                <div class="col-md-3"><label class="form-label" for="activity-search">Search activity or details</label><input class="form-control" id="activity-search" name="search" value="{{ $search }}" placeholder="e.g. appointment #12"></div>
                <div class="col-auto"><button class="btn btn-primary">Filter</button> <a class="btn btn-outline-secondary" href="{{ route('staff.activity-log') }}">Clear</a></div>
            </form>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th class="ps-4">Time (UTC)</th><th>Activity</th><th>Account</th><th class="pe-4">Details</th></tr></thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td class="ps-4 text-nowrap">{{ $entry['time_utc'] ?? '' }}</td>
                            <td><span class="badge rounded-pill text-bg-light border text-dark">{{ $entry['event_label'] }}</span></td>
                            <td>{{ $entry['user_label'] }}</td>
                            <td class="pe-4">{{ $entry['details_summary'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-5 text-center text-secondary">No activity entries match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
