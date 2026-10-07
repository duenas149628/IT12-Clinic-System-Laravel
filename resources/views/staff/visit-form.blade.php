@extends('layouts.app')

@section('title', $visit ? 'Edit visit record' : 'Create visit record')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-9">
            <h1 class="h2 text-brand">{{ $visit ? 'Edit visit record' : 'Create visit record' }}</h1>
            <p class="text-secondary">
                {{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name }}
                · {{ $appointment->confirmed_date?->format('F j, Y') }}
            </p>

            <div class="card p-4">
                <form method="POST" action="{{ $visit ? route('staff.visits.update', $visit) : route('staff.visits.store') }}">
                    @csrf
                    @if($visit)
                        @method('PUT')
                    @else
                        <input type="hidden" name="appointment_id" value="{{ $appointment->appointment_id }}">
                    @endif

                    @foreach(['chief_complaint' => 'Chief complaint', 'findings' => 'Findings', 'treatment' => 'Treatment', 'notes' => 'Notes', 'follow_up' => 'Follow-up'] as $field => $label)
                        <div class="mb-3">
                            <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                            <textarea class="form-control" id="{{ $field }}" rows="3" name="{{ $field }}">{{ old($field, $visit?->$field) }}</textarea>
                        </div>
                    @endforeach

                    <button class="btn btn-primary">{{ $visit ? 'Save record' : 'Create visit record' }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
