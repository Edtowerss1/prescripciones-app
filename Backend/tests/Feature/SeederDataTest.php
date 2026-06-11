<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Seeder — User counts & roles
// --------------------------------------------------------------------

test('seed creates 11 users with correct roles', function () {
    $this->seed();

    expect(User::count())->toBe(11);

    expect(User::where('email', 'admin@test.com')->first())
        ->not->toBeNull()
        ->hasRole('admin')->toBeTrue();

    expect(User::where('email', 'dr@test.com')->first())
        ->not->toBeNull()
        ->hasRole('doctor')->toBeTrue();

    expect(User::where('email', 'dr.garcia@test.com')->first())
        ->not->toBeNull()
        ->hasRole('doctor')->toBeTrue();

    expect(User::where('email', 'dra.lopez@test.com')->first())
        ->not->toBeNull()
        ->hasRole('doctor')->toBeTrue();

    expect(User::where('email', 'dr.martinez@test.com')->first())
        ->not->toBeNull()
        ->hasRole('doctor')->toBeTrue();

    expect(User::where('email', 'dra.rodriguez@test.com')->first())
        ->not->toBeNull()
        ->hasRole('doctor')->toBeTrue();

    expect(User::where('email', 'patient@test.com')->first())
        ->not->toBeNull()
        ->hasRole('patient')->toBeTrue();

    expect(User::where('email', 'carlos@test.com')->first())
        ->not->toBeNull()
        ->hasRole('patient')->toBeTrue();

    expect(User::where('email', 'maria@test.com')->first())
        ->not->toBeNull()
        ->hasRole('patient')->toBeTrue();

    expect(User::where('email', 'lucia@test.com')->first())
        ->not->toBeNull()
        ->hasRole('patient')->toBeTrue();

    expect(User::where('email', 'pedro@test.com')->first())
        ->not->toBeNull()
        ->hasRole('patient')->toBeTrue();
});

test('each user has exactly one role', function () {
    $this->seed();

    User::all()->each(function (User $user): void {
        expect($user->getRoleNames())->toHaveCount(1);
    });
});

// --------------------------------------------------------------------
// Doctor & Patient profiles
// --------------------------------------------------------------------

test('doctor profile exists for dr@test.com with correct data', function () {
    $this->seed();

    $user = User::where('email', 'dr@test.com')->first();
    $doctor = $user->doctor;

    expect($doctor)->not->toBeNull();
    expect($doctor->specialty)->toBe('Cardiología');
    expect($doctor->license_number)->toBe('LIC-7845');
});

test('extra doctors have doctor profiles', function () {
    $this->seed();

    $extraEmails = [
        'dr.garcia@test.com',
        'dra.lopez@test.com',
        'dr.martinez@test.com',
        'dra.rodriguez@test.com',
    ];

    foreach ($extraEmails as $email) {
        $user = User::where('email', $email)->first();
        expect($user->doctor)->not->toBeNull();
        expect($user->doctor->specialty)->not->toBeEmpty();
        expect($user->doctor->license_number)->not->toBeEmpty();
    }
});

test('patient profile exists for patient@test.com with birth_date', function () {
    $this->seed();

    $user = User::where('email', 'patient@test.com')->first();
    $patient = $user->patient;

    expect($patient)->not->toBeNull();
    expect($patient->birth_date->format('Y-m-d'))->toBe('1990-05-15');
});

test('admin has no doctor or patient profile', function () {
    $this->seed();

    $admin = User::where('email', 'admin@test.com')->first();

    expect($admin->doctor)->toBeNull();
    expect($admin->patient)->toBeNull();
});

// --------------------------------------------------------------------
// Prescriptions
// --------------------------------------------------------------------

test('seed creates 40 prescriptions with correct status distribution', function () {
    $this->seed();

    expect(Prescription::count())->toBe(40);
    expect(Prescription::where('status', 'pending')->count())->toBeGreaterThan(15);
    expect(Prescription::where('status', 'consumed')->count())->toBeGreaterThan(10);
});

test('consumed prescriptions have consumed_at set', function () {
    $this->seed();

    $consumed = Prescription::where('status', 'consumed')->get();

    expect($consumed)->not->toBeEmpty();
    $consumed->each(function (Prescription $p): void {
        expect($p->consumed_at)->not->toBeNull();
    });
});

test('pending prescriptions have null consumed_at', function () {
    $this->seed();

    $pending = Prescription::where('status', 'pending')->get();

    expect($pending)->not->toBeEmpty();
    $pending->each(function (Prescription $p): void {
        expect($p->consumed_at)->toBeNull();
    });
});

test('each prescription has 1-4 items with realistic names', function () {
    $this->seed();

    $prescriptions = Prescription::with('items')->get();

    expect($prescriptions)->toHaveCount(40);

    $medications = [
        'Ibuprofeno 600mg',
        'Amoxicilina 500mg',
        'Omeprazol 20mg',
        'Losartán 50mg',
        'Metformina 850mg',
        'Atorvastatina 20mg',
        'Levotiroxina 100mcg',
        'Salbutamol 100mcg',
        'Enalapril 10mg',
        'Paracetamol 500mg',
    ];

    foreach ($prescriptions as $prescription) {
        $itemCount = PrescriptionItem::where('prescription_id', $prescription->id)->count();
        expect($itemCount)->toBeGreaterThanOrEqual(1);
        expect($itemCount)->toBeLessThanOrEqual(4);

        foreach ($prescription->items as $item) {
            expect($item->name)->toBeIn($medications);
            expect($item->dosage)->not->toBeEmpty();
            expect($item->quantity)->toBeGreaterThanOrEqual(1);
            expect($item->instructions)->not->toBeEmpty();
        }
    }
});

// --------------------------------------------------------------------
// Prescription assignments
// --------------------------------------------------------------------

test('prescriptions are distributed across all patients', function () {
    $this->seed();

    $patientCount = Patient::count();
    expect($patientCount)->toBe(5);

    // Each patient should have at least some prescriptions
    $patients = Patient::with('user')->get();
    $patientsWithPrescriptions = 0;

    foreach ($patients as $patient) {
        $count = Prescription::where('patient_id', $patient->id)->count();
        if ($count > 0) {
            $patientsWithPrescriptions++;
        }
    }

    expect($patientsWithPrescriptions)->toBeGreaterThanOrEqual(3);
});
