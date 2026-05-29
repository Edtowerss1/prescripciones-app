<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Policies\PrescriptionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// create()
// --------------------------------------------------------------------

test('PrescriptionPolicy::create authorizes correctly', function (string $role, bool $expected) {
    $user = User::factory()->{$role}()->create();

    expect(app(PrescriptionPolicy::class)->create($user))->toBe($expected);
})->with([
    ['doctor', true],
    ['admin', false],
    ['patient', false],
]);

// --------------------------------------------------------------------
// view()
// --------------------------------------------------------------------

test('PrescriptionPolicy::view authorizes correctly', function (string $role, bool $isOwner, bool $expected) {
    $ownerDoctor = User::factory()->doctor()->create();
    $ownerPatient = User::factory()->patient()->create();

    $prescription = new Prescription;
    $prescription->setRelation('doctor', new Doctor(['user_id' => $ownerDoctor->id]));
    $prescription->setRelation('patient', new Patient(['user_id' => $ownerPatient->id]));

    $user = match (true) {
        $role === 'admin' => User::factory()->admin()->create(),
        $role === 'doctor' && $isOwner => $ownerDoctor,
        $role === 'doctor' && ! $isOwner => User::factory()->doctor()->create(),
        $role === 'patient' && $isOwner => $ownerPatient,
        $role === 'patient' && ! $isOwner => User::factory()->patient()->create(),
    };

    expect(app(PrescriptionPolicy::class)->view($user, $prescription))->toBe($expected);
})->with([
    ['admin', false, true],
    ['doctor', true, true],
    ['doctor', false, false],
    ['patient', true, true],
    ['patient', false, false],
]);

// --------------------------------------------------------------------
// consume()
// --------------------------------------------------------------------

test('PrescriptionPolicy::consume authorizes correctly', function (string $role, bool $isOwner, bool $expected) {
    $ownerDoctor = User::factory()->doctor()->create();
    $ownerPatient = User::factory()->patient()->create();

    $prescription = new Prescription;
    $prescription->setRelation('doctor', new Doctor(['user_id' => $ownerDoctor->id]));
    $prescription->setRelation('patient', new Patient(['user_id' => $ownerPatient->id]));

    $user = match (true) {
        $role === 'admin' => User::factory()->admin()->create(),
        $role === 'doctor' => User::factory()->doctor()->create(),
        $role === 'patient' && $isOwner => $ownerPatient,
        $role === 'patient' && ! $isOwner => User::factory()->patient()->create(),
    };

    expect(app(PrescriptionPolicy::class)->consume($user, $prescription))->toBe($expected);
})->with([
    ['doctor', false, false],
    ['admin', false, false],
    ['patient', true, true],
    ['patient', false, false],
]);
