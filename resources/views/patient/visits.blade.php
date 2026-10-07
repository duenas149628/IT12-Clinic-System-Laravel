@extends('layouts.app')

@section('title', 'Visit history')

@section('content')
    <h1 class="h2 text-brand">Visit history</h1>
    <p class="text-secondary">Your recorded checkups and treatments.</p>

    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label">Visit date</label><input class="form-control" type="date" name="visit_date" value="{{ $date }}"></div>
                <div class="col-auto"><button class="btn btn-primary">Filter</button> <a class="btn btn-outline-secondary" href="{{ route('patient.visits') }}">Clear</a></div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        @forelse($visits as $v)
            <div class="col-12">
                <article class="card p-4">
                    <h2 class="h5 text-brand">{{ $v->visit_date?->format('F j, Y') }}</h2>
                    <dl class="row mb-0">
                        @foreach(['Chief complaint' => 'chief_complaint', 'Findings' => 'findings', 'Treatment' => 'treatment', 'Notes' => 'notes', 'Follow-up' => 'follow_up'] as $label => $field)
                            @if($v->$field)
                                <dt class="col-md-3">{{ $label }}</dt><dd class="col-md-9 text-break">{{ $v->$field }}</dd>
                            @endif
                        @endforeach
                    </dl>
                </article>
            </div>
        @empty
            <div class="col"><div class="card p-5 text-center text-secondary">No visit records match your filter.</div></div>
        @endforelse
    </div>

    @if($visits->count() < $visitsTotal)
        <div class="p-3 text-center">
            <a class="btn btn-outline-primary" href="{{ request()->fullUrlWithQuery(['visits_rows' => min($visitsTotal, $visitsRows + 5)]) }}">Show 5 more rows</a>
        </div>
    @endif
@endsection
