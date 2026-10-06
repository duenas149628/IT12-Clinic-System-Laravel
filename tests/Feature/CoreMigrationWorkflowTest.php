<?php

namespace Tests\Feature;

use App\Http\Middleware\SecureClinicResponse;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\VisitRecord;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CoreMigrationWorkflowTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('visit_records');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->bigIncrements('user_id');
            $table->string('name', 100);
            $table->string('email', 255)->unique();
            $table->string('password');
            $table->enum('role', ['patient', 'staff'])->default('patient');
            $table->boolean('must_change_password')->default(false);
            $table->timestamps();
        });
        Schema::create('patients', function (Blueprint $table): void {
            $table->bigIncrements('patient_id');
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->string('patient_number', 20)->unique();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->date('birth_date')->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->timestamps();
        });
        Schema::create('appointments', function (Blueprint $table): void {
            $table->bigIncrements('appointment_id');
            $table->unsignedBigInteger('patient_id');
            $table->date('preferred_date');
            $table->time('preferred_start_time');
            $table->time('preferred_end_time');
            $table->date('confirmed_date')->nullable();
            $table->time('confirmed_start_time')->nullable();
            $table->time('confirmed_end_time')->nullable();
            $table->string('reason', 255)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->timestamps();
        });
        Schema::create('visit_records', function (Blueprint $table): void {
            $table->bigIncrements('visit_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id')->unique();
            $table->date('visit_date');
            $table->text('chief_complaint')->nullable();
            $table->text('findings')->nullable();
            $table->text('treatment')->nullable();
            $table->text('notes')->nullable();
            $table->text('follow_up')->nullable();
            $table->timestamps();
        });
    }

    private function user(string $role = 'patient', bool $mustChange = false): User
    {
        return User::create([
            'name' => ucfirst($role).' User',
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => Hash::make('StrongPassword123!'),
            'role' => $role,
            'must_change_password' => $mustChange,
        ]);
    }

    private function patient(?User $user = null): Patient
    {
        $user ??= $this->user();

        return Patient::create([
            'user_id' => $user->user_id,
            'patient_number' => 'TL-'.str_pad((string) (Patient::count() + 1), 5, '0', STR_PAD_LEFT),
            'first_name' => 'Pat',
            'last_name' => 'Example',
        ]);
    }

    public function test_public_auth_screens_render_and_guest_registration_creates_owned_patient_record(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Sign in to your clinic account');
        $this->get(route('register'))->assertOk()->assertSee('Create your patient account');

        $this->post(route('register.submit'), [
            'first_name' => 'Mina',
            'last_name' => 'Example',
            'email' => 'mina@example.test',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertRedirect(route('login'));

        $user = User::where('email', 'mina@example.test')->firstOrFail();
        $this->assertSame('patient', $user->role);
        $this->assertSame('TL-00001', $user->patient->patient_number);
    }

    public function test_production_enforces_canonical_https_and_security_headers(): void
    {
        config([
            'app.env' => 'production',
            'app.canonical_url' => 'https://clinic.example',
        ]);

        $this->get('/login?return=home')
            ->assertStatus(308)
            ->assertRedirect('https://clinic.example/login?return=home');

        $request = Request::create('https://clinic.example/login');
        $response = app(SecureClinicResponse::class)
            ->handle($request, fn () => response('ok'));

        $this->assertSame('max-age=31536000', $response->headers->get('Strict-Transport-Security'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('max-age=0, no-store, private', $response->headers->get('Cache-Control'));
    }

    public function test_login_redirects_by_role_and_requires_temporary_password_change(): void
    {
        $patient = $this->user('patient', true);

        $this->post(route('login.submit'), [
            'email' => $patient->email,
            'password' => 'StrongPassword123!',
        ])->assertRedirect(route('password.change'));

        $this->get(route('patient.dashboard'))->assertRedirect(route('password.change'));

        $this->put(route('password.update'), [
            'current_password' => 'StrongPassword123!',
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ])->assertRedirect(route('patient.dashboard'));

        $this->assertFalse($patient->fresh()->must_change_password);
    }

    public function test_legacy_php_password_hashes_authenticate_and_guests_cannot_reach_patient_data(): void
    {
        $legacyPassword = 'LegacyCompatible456!';
        $userId = DB::table('users')->insertGetId([
            'name' => 'Legacy Patient',
            'email' => 'legacy-patient@example.test',
            'password' => password_hash($legacyPassword, PASSWORD_DEFAULT),
            'role' => 'patient',
            'must_change_password' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patient = User::findOrFail($userId);
        $record = $this->patient($patient);

        $this->get(route('patient.profile'))->assertRedirect(route('login'));
        $this->get(route('patient.appointments'))->assertRedirect(route('login'));
        $this->get(route('staff.patients.show', $record))->assertRedirect(route('login'));

        $this->post(route('login.submit'), [
            'email' => $patient->email,
            'password' => $legacyPassword,
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($patient);
    }

    public function test_patient_pages_enforce_ownership_and_update_profile(): void
    {
        $patientUser = $this->user();
        $patient = $this->patient($patientUser);
        $other = $this->patient();

        $this->actingAs($patientUser)->get(route('patient.dashboard'))->assertOk();
        $this->get(route('patient.profile'))->assertOk();
        $this->put(route('patient.profile.update'), [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'birth_date' => '2000-01-01',
            'sex' => 'Female',
            'contact_number' => '5551234',
            'address' => 'Clinic Road',
        ])->assertRedirect(route('patient.profile'));
        $this->assertSame('Updated Name', $patientUser->fresh()->name);

        $this->post(route('patient.appointments.store'), [
            'preferred_date' => now()->addDays(5)->format('Y-m-d'),
            'preferred_start_time' => '10:00',
            'preferred_end_time' => '11:00',
            'reason' => 'Checkup',
        ])->assertRedirect(route('patient.appointments.request'));

        $ownAppointment = Appointment::where('patient_id', $patient->patient_id)->firstOrFail();
        $this->assertSame('pending', $ownAppointment->status);
        $this->assertSame(1, $patient->fresh()->appointments()->count());
        $this->assertSame(0, $other->appointments()->count());
        $this->get(route('patient.appointments'))->assertOk()->assertSee('Checkup');
        $this->get(route('patient.visits'))->assertOk();
        $this->get(route('staff.patients.show', $other))->assertRedirect(route('patient.dashboard'));

        $staff = $this->user('staff');
        $this->actingAs($staff)->get(route('patient.profile'))->assertRedirect(route('staff.dashboard'));
        $this->get(route('staff.patients.show', $other))->assertOk();
    }

    public function test_staff_conflict_confirmation_completion_and_visit_edit_flow(): void
    {
        $staff = $this->user('staff');
        $patient = $this->patient();
        $otherPatient = $this->patient();
        $first = $patient->appointments()->create([
            'preferred_date' => now()->addDays(3)->toDateString(),
            'preferred_start_time' => '10:00',
            'preferred_end_time' => '11:00',
            'reason' => 'First',
            'status' => 'pending',
        ]);
        $overlap = $otherPatient->appointments()->create([
            'preferred_date' => now()->addDays(3)->toDateString(),
            'preferred_start_time' => '10:30',
            'preferred_end_time' => '11:30',
            'reason' => 'Second',
            'status' => 'pending',
        ]);

        $this->actingAs($staff)->get(route('staff.dashboard'))->assertOk();
        $this->get(route('staff.patients'))->assertOk();
        $this->get(route('staff.appointments'))->assertOk();
        $this->get(route('staff.appointments.show', $first))->assertOk();

        $this->post(route('staff.appointments.action', $first), [
            'action' => 'accept',
        ])->assertSessionHasNoErrors();
        $first = $first->fresh();
        $this->assertSame('confirmed', $first->status);
        $this->assertSame($first->preferred_date->toDateString(), $first->confirmed_date->toDateString());
        $this->assertSame('10:00', substr((string) $first->confirmed_start_time, 0, 5));
        $this->assertSame('11:00', substr((string) $first->confirmed_end_time, 0, 5));

        $this->post(route('staff.appointments.action', $overlap), [
            'action' => 'confirm',
            'confirmed_date' => $first->confirmed_date->format('Y-m-d'),
            'confirmed_start_time' => '10:30',
            'confirmed_end_time' => '11:30',
        ])->assertSessionHasErrors('confirmed_start_time');
        $this->assertSame('pending', $overlap->fresh()->status);

        $this->post(route('staff.appointments.action', $first), ['action' => 'completed'])
            ->assertSessionHasNoErrors();
        $first = $first->fresh();
        $this->assertSame('completed', $first->status);
        $visit = $first->visitRecord;
        $this->assertNotNull($visit);
        $this->assertSame(1, $first->fresh()->visitRecord()->count());
        $this->assertSame($first->confirmed_date->toDateString(), $visit->visit_date->toDateString());

        $this->post(route('staff.appointments.action', $first), ['action' => 'completed'])
            ->assertSessionHasErrors('action');
        $this->assertSame(1, VisitRecord::where('appointment_id', $first->appointment_id)->count());

        $this->get(route('staff.visits'))->assertOk();
        $this->get(route('staff.visits.show', $visit))->assertOk();
        $this->put(route('staff.visits.update', $visit), [
            'appointment_id' => $first->appointment_id,
            'chief_complaint' => 'Sensitive tooth',
            'findings' => '',
            'treatment' => 'Examined',
            'notes' => '',
            'follow_up' => '',
        ])->assertRedirect(route('staff.visits.show', $visit));

        $this->get(route('staff.reports'))->assertOk();
        $this->get(route('staff.activity-log'))->assertOk();
        $this->get(route('staff.accounts'))->assertOk();
    }

    public function test_staff_can_register_and_edit_a_patient_and_drive_remaining_status_transitions(): void
    {
        $staff = $this->user('staff');
        $existingPatient = $this->patient();
        $this->actingAs($staff);

        $this->get(route('staff.patients.create'))->assertOk();
        $this->post(route('staff.patients.store'), [
            'first_name' => 'New',
            'last_name' => 'Patient',
            'email' => 'new-patient@example.test',
            'birth_date' => '1998-05-12',
            'sex' => 'Female',
            'contact_number' => '5555555',
            'address' => 'Dental Street',
        ])->assertRedirect(route('staff.patients.create'));

        $createdPatient = Patient::where('first_name', 'New')->firstOrFail();
        $this->assertSame('TL-00002', $createdPatient->patient_number);
        $this->assertTrue($createdPatient->user->must_change_password);
        $credentials = session('created_patient_credentials');
        $this->assertIsArray($credentials);
        $this->assertNotSame('1234', $credentials['temporary_password']);
        $this->assertGreaterThanOrEqual(20, strlen($credentials['temporary_password']));
        $this->assertTrue(Hash::check($credentials['temporary_password'], $createdPatient->user->password));

        $this->get(route('staff.patients.show', $existingPatient))->assertOk();
        $this->put(route('staff.patients.update', $existingPatient), [
            'first_name' => 'StaffUpdated',
            'last_name' => 'Patient',
            'birth_date' => '',
            'sex' => '',
            'contact_number' => '',
            'address' => '',
        ])->assertRedirect(route('staff.patients.show', $existingPatient));
        $this->assertSame('StaffUpdated Patient', $existingPatient->fresh()->user->name);

        $this->get(route('staff.appointments.create'))->assertOk();
        $this->post(route('staff.appointments.store'), [
            'patient_id' => $createdPatient->patient_id,
            'preferred_date' => now()->addDays(8)->toDateString(),
            'preferred_start_time' => '13:00',
            'preferred_end_time' => '14:00',
            'reason' => 'Staff scheduled request',
        ])->assertRedirect();
        $custom = Appointment::where('reason', 'Staff scheduled request')->firstOrFail();

        $this->post(route('staff.appointments.action', $custom), [
            'action' => 'confirm',
            'confirmed_date' => now()->addDays(9)->toDateString(),
            'confirmed_start_time' => '13:30',
            'confirmed_end_time' => '14:30',
        ])->assertSessionHasNoErrors();
        $custom = $custom->fresh();
        $this->assertSame(now()->addDays(9)->toDateString(), $custom->confirmed_date->toDateString());

        $this->post(route('staff.appointments.action', $custom), [
            'action' => 'reschedule',
            'confirmed_date' => now()->addDays(10)->toDateString(),
            'confirmed_start_time' => '15:00',
            'confirmed_end_time' => '16:00',
        ])->assertSessionHasNoErrors();
        $this->assertSame(now()->addDays(10)->toDateString(), $custom->fresh()->confirmed_date->toDateString());

        $noShow = $createdPatient->appointments()->create([
            'preferred_date' => now()->addDays(11)->toDateString(),
            'preferred_start_time' => '09:00',
            'preferred_end_time' => '10:00',
            'confirmed_date' => now()->addDays(11)->toDateString(),
            'confirmed_start_time' => '09:00',
            'confirmed_end_time' => '10:00',
            'reason' => 'No-show case',
            'status' => 'confirmed',
        ]);
        $this->post(route('staff.appointments.action', $noShow), ['action' => 'no_show'])->assertSessionHasNoErrors();
        $this->assertSame('no_show', $noShow->fresh()->status);

        $this->post(route('staff.appointments.action', $custom), ['action' => 'cancel'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $custom->fresh()->status);
        $this->assertNull($custom->fresh()->confirmed_date);
    }
}
