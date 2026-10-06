<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompatibilityController extends Controller
{
    public function patientEdit(Request $request): RedirectResponse
    {
        return $request->filled('id')
            ? redirect()->route('staff.patients.edit', $request->query('id'))
            : redirect()->route('staff.patients');
    }

    public function patientVisits(Request $request): RedirectResponse
    {
        return redirect()->route('staff.visits', $request->query());
    }

    public function appointmentView(Request $request): RedirectResponse
    {
        return $request->filled('id')
            ? redirect()->route('staff.appointments.show', $request->query('id'))
            : redirect()->route('staff.appointments');
    }

    public function visitCreate(Request $request): RedirectResponse
    {
        return $request->filled('id')
            ? redirect()->route('staff.visits.create', ['appointment_id' => $request->query('id')])
            : redirect()->route('staff.visits');
    }

    public function visitEdit(Request $request): RedirectResponse
    {
        return $request->filled('id')
            ? redirect()->route('staff.visits.edit', $request->query('id'))
            : redirect()->route('staff.visits');
    }

    public function visitView(Request $request): RedirectResponse
    {
        return $request->filled('id')
            ? redirect()->route('staff.visits.show', $request->query('id'))
            : redirect()->route('staff.visits');
    }
}
