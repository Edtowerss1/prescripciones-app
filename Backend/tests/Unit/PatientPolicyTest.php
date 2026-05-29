<?php

use App\Models\User;
use App\Policies\PatientPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// viewAny()
// --------------------------------------------------------------------

test('PatientPolicy::viewAny authorizes correctly', function (string $role, bool $expected) {
    $user = User::factory()->{$role}()->create();

    expect(app(PatientPolicy::class)->viewAny($user))->toBe($expected);
})->with([
    ['admin', true],
    ['doctor', true],
    ['patient', false],
]);
