@extends('layouts.app')

@section('title', 'Request appointment')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-9">
            <h1 class="h2 text-brand">Request an appointment</h1>
            <p class="text-secondary">Staff will review your preferred schedule and confirm it.</p>

            <div class="card p-4">
                <form method="POST" action="{{ route('patient.appointments.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="preferred-date">Preferred date</label>
                            <input class="form-control" id="preferred-date" type="date" name="preferred_date" min="{{ today()->toDateString() }}" value="{{ old('preferred_date') }}" required>
                            <div class="form-text">Click to open the date picker.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="preferred-start-time">Preferred start time</label>
                            <select class="form-select" id="preferred-start-time" name="preferred_start_time" required>
                                <option value="" disabled @selected(!old('preferred_start_time'))>Choose a start time</option>
                                @for($minuteOfDay = 540; $minuteOfDay <= 1200; $minuteOfDay += 15)
                                    @php
                                        $timeChoice = sprintf('%02d:%02d', intdiv($minuteOfDay, 60), $minuteOfDay % 60);
                                        $hour12 = intdiv($minuteOfDay, 60) % 12 ?: 12;
                                        $period = intdiv($minuteOfDay, 60) < 12 ? 'AM' : 'PM';
                                    @endphp
                                    <option value="{{ $timeChoice }}" @selected(old('preferred_start_time') === $timeChoice)>{{ sprintf('%d:%02d %s', $hour12, $minuteOfDay % 60, $period) }}</option>
                                @endfor
                            </select>
                            <div class="form-text">Scroll through 9:00 AM to 8:00 PM in 15-minute steps.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="preferred-end-time">Preferred end time</label>
                            <select class="form-select" id="preferred-end-time" name="preferred_end_time" required>
                                <option value="" disabled @selected(!old('preferred_end_time'))>Choose an end time</option>
                                @for($minuteOfDay = 540; $minuteOfDay <= 1200; $minuteOfDay += 15)
                                    @php
                                        $timeChoice = sprintf('%02d:%02d', intdiv($minuteOfDay, 60), $minuteOfDay % 60);
                                        $hour12 = intdiv($minuteOfDay, 60) % 12 ?: 12;
                                        $period = intdiv($minuteOfDay, 60) < 12 ? 'AM' : 'PM';
                                    @endphp
                                    <option value="{{ $timeChoice }}" @selected(old('preferred_end_time') === $timeChoice)>{{ sprintf('%d:%02d %s', $hour12, $minuteOfDay % 60, $period) }}</option>
                                @endfor
                            </select>
                            <div class="form-text">Scroll through 9:00 AM to 8:00 PM in 15-minute steps.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="reason">Reason for visit</label>
                            <textarea class="form-control" id="reason" name="reason" maxlength="255" rows="4" required>{{ old('reason') }}</textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4">Submit request</button>
                </form>
            </div>
        </div>
    </div>
@endsection
