<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Schema verification — prove tables exist after migration
// --------------------------------------------------------------------

it('has the doctors table with expected columns', function () {
    expect(Schema::hasTable('doctors'))->toBeTrue();
    expect(Schema::hasColumns('doctors', [
        'id', 'user_id', 'specialty', 'license_number', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the patients table with expected columns', function () {
    expect(Schema::hasTable('patients'))->toBeTrue();
    expect(Schema::hasColumns('patients', [
        'id', 'user_id', 'birth_date', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the prescriptions table with expected columns', function () {
    expect(Schema::hasTable('prescriptions'))->toBeTrue();
    expect(Schema::hasColumns('prescriptions', [
        'id', 'code', 'doctor_id', 'patient_id', 'status', 'notes', 'consumed_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('has the prescription_items table with expected columns', function () {
    expect(Schema::hasTable('prescription_items'))->toBeTrue();
    expect(Schema::hasColumns('prescription_items', [
        'id', 'prescription_id', 'name', 'quantity', 'dosage', 'instructions', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

// --------------------------------------------------------------------
// User ↔ Doctor relationship
// --------------------------------------------------------------------

it('resolves doctor from user and backward', function () {
    $user = User::factory()->create();

    $doctor = new Doctor(['specialty' => 'Cardiology', 'license_number' => 'LIC-001']);
    $doctor->user_id = $user->id;
    $doctor->save();

    expect($user->doctor)->not->toBeNull();
    expect($user->doctor->specialty)->toBe('Cardiology');
    expect($doctor->user->id)->toBe($user->id);
});

// --------------------------------------------------------------------
// User ↔ Patient relationship
// --------------------------------------------------------------------

it('resolves patient from user and backward', function () {
    $user = User::factory()->create();

    $patient = new Patient(['birth_date' => '1990-05-10']);
    $patient->user_id = $user->id;
    $patient->save();

    expect($user->patient)->not->toBeNull();
    expect($user->patient->birth_date->toDateString())->toBe('1990-05-10');
    expect($patient->user->id)->toBe($user->id);
});

// --------------------------------------------------------------------
// Doctor ↔ Prescription relationship
// --------------------------------------------------------------------

it('resolves prescriptions from doctor', function () {
    $doctorUser = User::factory()->create();
    $patientUser = User::factory()->create();

    $doctor = Doctor::create(['user_id' => $doctorUser->id, 'specialty' => 'Dermatology', 'license_number' => 'LIC-002']);
    $patient = Patient::create(['user_id' => $patientUser->id, 'birth_date' => '1985-03-15']);

    $prescription = Prescription::create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'pending',
    ]);

    expect($doctor->prescriptions)->toHaveCount(1);
    expect($doctor->prescriptions->first()->id)->toBe($prescription->id);
});

// --------------------------------------------------------------------
// Patient ↔ Prescription relationship
// --------------------------------------------------------------------

it('resolves prescriptions from patient', function () {
    $doctorUser = User::factory()->create();
    $patientUser = User::factory()->create();

    $doctor = Doctor::create(['user_id' => $doctorUser->id, 'specialty' => 'Pediatrics', 'license_number' => 'LIC-003']);
    $patient = Patient::create(['user_id' => $patientUser->id, 'birth_date' => '2000-07-20']);

    Prescription::create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'pending',
    ]);

    expect($patient->prescriptions)->toHaveCount(1);
});

// --------------------------------------------------------------------
// Prescription ↔ Doctor, Patient, Items
// --------------------------------------------------------------------

it('navigates all prescription relationships', function () {
    $doctorUser = User::factory()->create();
    $patientUser = User::factory()->create();

    $doctor = Doctor::create(['user_id' => $doctorUser->id, 'specialty' => 'Neurology', 'license_number' => 'LIC-004']);
    $patient = Patient::create(['user_id' => $patientUser->id, 'birth_date' => '1975-01-01']);

    $prescription = Prescription::create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'pending',
    ]);

    $item1 = PrescriptionItem::create([
        'prescription_id' => $prescription->id,
        'name' => 'Ibuprofen',
        'quantity' => 30,
        'dosage' => '400mg',
        'instructions' => 'Take with food',
    ]);

    $item2 = PrescriptionItem::create([
        'prescription_id' => $prescription->id,
        'name' => 'Paracetamol',
        'quantity' => 20,
        'dosage' => '500mg',
        'instructions' => 'Every 8 hours',
    ]);

    expect($prescription->doctor->id)->toBe($doctor->id);
    expect($prescription->patient->id)->toBe($patient->id);
    expect($prescription->items)->toHaveCount(2);

    expect($item1->prescription->id)->toBe($prescription->id);
    expect($item1->name)->toBe('Ibuprofen');
    expect($item2->prescription->id)->toBe($prescription->id);
});

// --------------------------------------------------------------------
// Prescription UUID code generation
// --------------------------------------------------------------------

it('generates a unique UUID code on prescription creation', function () {
    $doctorUser = User::factory()->create();
    $patientUser = User::factory()->create();

    $doctor = Doctor::create(['user_id' => $doctorUser->id, 'specialty' => 'General', 'license_number' => 'LIC-005']);
    $patient = Patient::create(['user_id' => $patientUser->id, 'birth_date' => '1988-06-15']);

    $prescription = Prescription::create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'pending',
    ]);

    expect($prescription->code)->not->toBeNull();

    $prescription2 = Prescription::create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'pending',
    ]);

    expect($prescription2->code)->not->toBeNull();
    expect($prescription->code)->not->toBe($prescription2->code);
});

// --------------------------------------------------------------------
// FK Deletion behavior
// --------------------------------------------------------------------

it('cascades user deletion to doctor profile', function () {
    $user = User::factory()->create();
    Doctor::create(['user_id' => $user->id, 'specialty' => 'Surgery', 'license_number' => 'LIC-006']);

    expect(Doctor::count())->toBe(1);

    $user->delete();

    expect(Doctor::count())->toBe(0);
});

it('cascades user deletion to patient profile', function () {
    $user = User::factory()->create();
    Patient::create(['user_id' => $user->id, 'birth_date' => '1995-08-20']);

    expect(Patient::count())->toBe(1);

    $user->delete();

    expect(Patient::count())->toBe(0);
});

it('restricts doctor deletion with existing prescriptions', function () {
    $doctorUser = User::factory()->create();
    $patientUser = User::factory()->create();

    $doctor = Doctor::create(['user_id' => $doctorUser->id, 'specialty' => 'Oncology', 'license_number' => 'LIC-007']);
    $patient = Patient::create(['user_id' => $patientUser->id, 'birth_date' => '1992-03-10']);

    Prescription::create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'pending',
    ]);

    expect(fn () => $doctor->delete())->toThrow(QueryException::class);
});
