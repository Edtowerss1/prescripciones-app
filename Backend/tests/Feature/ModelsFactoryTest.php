<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// DoctorFactory
// --------------------------------------------------------------------

test('doctor factory creates a doctor', function () {
    $doctor = Doctor::factory()->create();

    expect($doctor)->not->toBeNull();
    expect($doctor->specialty)->not->toBeEmpty();
    expect($doctor->license_number)->not->toBeEmpty();
    expect($doctor->user_id)->not->toBeNull();
});

test('doctor factory creates a doctor with a valid user relationship', function () {
    $doctor = Doctor::factory()->create();

    expect($doctor->user)->not->toBeNull();
    expect($doctor->user->exists)->toBeTrue();
});

// --------------------------------------------------------------------
// PatientFactory
// --------------------------------------------------------------------

test('patient factory creates a patient', function () {
    $patient = Patient::factory()->create();

    expect($patient)->not->toBeNull();
    expect($patient->user_id)->not->toBeNull();
});

test('patient factory creates a patient with a valid user relationship', function () {
    $patient = Patient::factory()->create();

    expect($patient->user)->not->toBeNull();
    expect($patient->user->exists)->toBeTrue();
});

test('patient factory creates with nullable birth_date', function () {
    $patient = Patient::factory()->create(['birth_date' => null]);

    expect($patient->birth_date)->toBeNull();
});

// --------------------------------------------------------------------
// PrescriptionFactory
// --------------------------------------------------------------------

test('prescription factory creates a prescription', function () {
    $prescription = Prescription::factory()->create();

    expect($prescription)->not->toBeNull();
    expect($prescription->code)->not->toBeNull();
    expect($prescription->status)->toBe('pending');
    expect($prescription->doctor_id)->not->toBeNull();
    expect($prescription->patient_id)->not->toBeNull();
});

test('prescription factory generates a unique UUID', function () {
    $p1 = Prescription::factory()->create();
    $p2 = Prescription::factory()->create();

    expect($p1->code)->not->toBe($p2->code);
});

test('prescription factory resolves doctor and patient relationships', function () {
    $prescription = Prescription::factory()->create();

    expect($prescription->doctor)->not->toBeNull();
    expect($prescription->patient)->not->toBeNull();
    expect($prescription->doctor->user)->not->toBeNull();
    expect($prescription->patient->user)->not->toBeNull();
});

// --------------------------------------------------------------------
// PrescriptionItemFactory
// --------------------------------------------------------------------

test('prescription item factory creates an item', function () {
    $item = PrescriptionItem::factory()->create();

    expect($item)->not->toBeNull();
    expect($item->name)->not->toBeEmpty();
    expect($item->quantity)->toBeGreaterThanOrEqual(1);
    expect($item->prescription_id)->not->toBeNull();
});

test('prescription item factory resolves prescription relationship', function () {
    $item = PrescriptionItem::factory()->create();

    expect($item->prescription)->not->toBeNull();
    expect($item->prescription->code)->not->toBeNull();
});

test('prescription item factory creates with nullable fields', function () {
    $item = PrescriptionItem::factory()->create([
        'dosage' => null,
        'instructions' => null,
    ]);

    expect($item->dosage)->toBeNull();
    expect($item->instructions)->toBeNull();
});
