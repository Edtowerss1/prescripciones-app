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

test('seed creates 5 users with correct roles', function () {
    $this->seed();

    expect(User::count())->toBe(5);

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

    expect(User::where('email', 'patient@test.com')->first())
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

    foreach (['dr.garcia@test.com', 'dra.lopez@test.com'] as $email) {
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

test('seed creates 8 prescriptions with correct status distribution', function () {
    $this->seed();

    expect(Prescription::count())->toBe(8);
    expect(Prescription::where('status', 'pending')->count())->toBe(4);
    expect(Prescription::where('status', 'consumed')->count())->toBe(4);
});

test('consumed prescriptions have consumed_at set', function () {
    $this->seed();

    $consumed = Prescription::where('status', 'consumed')->get();

    expect($consumed)->toHaveCount(4);
    $consumed->each(function (Prescription $p): void {
        expect($p->consumed_at)->not->toBeNull();
    });
});

test('pending prescriptions have null consumed_at', function () {
    $this->seed();

    $pending = Prescription::where('status', 'pending')->get();

    expect($pending)->toHaveCount(4);
    $pending->each(function (Prescription $p): void {
        expect($p->consumed_at)->toBeNull();
    });
});

test('each prescription has 2-3 items with realistic names', function () {
    $this->seed();

    $prescriptions = Prescription::with('items')->get();

    expect($prescriptions)->toHaveCount(8);

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
        expect($itemCount)->toBeGreaterThanOrEqual(2);
        expect($itemCount)->toBeLessThanOrEqual(3);

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

test('all prescriptions are assigned to patient@test.com', function () {
    $this->seed();

    $patient = Patient::whereHas('user', fn ($q) => $q->where('email', 'patient@test.com'))->first();

    expect(Prescription::where('patient_id', $patient->id)->count())->toBe(8);
});
