<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use App\Support\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function home(): RedirectResponse
    {
        return redirect()->route(Auth::check() ? 'dashboard' : 'login');
    }

    public function dashboard(): RedirectResponse
    {
        return redirect()->route(Auth::user()->role === 'staff' ? 'staff.dashboard' : 'patient.dashboard');
    }

    public function loginForm(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('dashboard') : view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:1024'],
        ]);

        $key = Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please wait 15 minutes and try again.',
            ]);
        }

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], false)) {
            RateLimiter::hit($key, 900);
            AuditLog::write('login.failure', ['email_hash' => hash('sha256', Str::lower($data['email']))]);

            throw ValidationException::withMessages(['email' => 'Invalid email or password.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        AuditLog::write('login.success');

        return redirect()->route(Auth::user()->must_change_password ? 'password.change' : 'dashboard');
    }

    public function registerForm(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('dashboard') : view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:1024', 'confirmed'],
        ]);

        if (mb_strlen(trim($data['first_name'].' '.$data['last_name'])) > 100) {
            throw ValidationException::withMessages(['first_name' => 'The combined name must be 100 characters or fewer.']);
        }

        $patient = DB::transaction(function () use ($data): Patient {
            $user = User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'patient',
                'must_change_password' => false,
            ]);

            $patient = Patient::create([
                'user_id' => $user->user_id,
                'patient_number' => 'TMP-'.Str::random(12),
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
            ]);

            $patient->patient_number = 'TL-'.str_pad((string) $patient->patient_id, 5, '0', STR_PAD_LEFT);
            $patient->save();

            return $patient;
        });

        AuditLog::write('account.registered', [
            'new_user_id' => $patient->user_id,
            'patient_id' => $patient->patient_id,
        ]);

        return redirect()->route('login')->with('status', 'Registration successful. Please log in to continue.');
    }

    public function passwordForm(): View
    {
        return view('auth.password-change');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:1024'],
            'password' => ['required', 'string', 'min:12', 'max:1024', 'confirmed'],
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            AuditLog::write('password.change.failure');
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }

        if ($data['current_password'] === $data['password']) {
            throw ValidationException::withMessages(['password' => 'Choose a password different from your current password.']);
        }

        $user->password = $data['password'];
        $user->must_change_password = false;
        $user->save();
        $request->session()->regenerate();
        AuditLog::write('password.change.success');

        return redirect()->route($user->role === 'staff' ? 'staff.dashboard' : 'patient.dashboard')
            ->with('status', 'Your password has been changed.');
    }

    public function logoutForm(): View|RedirectResponse
    {
        return Auth::check() ? view('auth.logout') : redirect()->route('login');
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditLog::write('logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }
}
