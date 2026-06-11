<?php

use App\Http\Requests\Doctor\DoctorFilterRequest;
use App\Http\Requests\Metrics\AdminMetricsFilterRequest;
use App\Http\Requests\Patient\PatientFilterRequest;
use App\Http\Requests\Prescriptions\AdminPrescriptionFilterRequest;
use App\Http\Requests\Users\UserFilterRequest;
use Illuminate\Support\Facades\Validator;

// --------------------------------------------------------------------
// AdminMetricsFilterRequest validation rules
// --------------------------------------------------------------------

test('AdminMetricsFilterRequest rejects invalid from date', function () {
    $request = new AdminMetricsFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['from' => 'not-a-date'], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('from'))->toBeTrue();
});

test('AdminMetricsFilterRequest rejects from after to', function () {
    $request = new AdminMetricsFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make([
        'from' => '2026-06-01',
        'to' => '2026-01-01',
    ], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('to'))->toBeTrue();
});

test('AdminMetricsFilterRequest passes with valid dates', function () {
    $request = new AdminMetricsFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make([
        'from' => '2026-01-01',
        'to' => '2026-06-01',
    ], $rules);

    expect($validator->passes())->toBeTrue();
});

test('AdminMetricsFilterRequest passes with no dates', function () {
    $request = new AdminMetricsFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make([], $rules);

    expect($validator->passes())->toBeTrue();
});

test('AdminMetricsFilterRequest authorizes all users', function () {
    $request = new AdminMetricsFilterRequest;
    expect($request->authorize())->toBeTrue();
});

// --------------------------------------------------------------------
// AdminPrescriptionFilterRequest validation rules
// --------------------------------------------------------------------

test('AdminPrescriptionFilterRequest rejects invalid status', function () {
    $request = new AdminPrescriptionFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['status' => 'invalid_status'], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('status'))->toBeTrue();
});

test('AdminPrescriptionFilterRequest passes with valid status', function () {
    $request = new AdminPrescriptionFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['status' => 'pending'], $rules);

    expect($validator->passes())->toBeTrue();
});

test('AdminPrescriptionFilterRequest rejects negative page', function () {
    $request = new AdminPrescriptionFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['page' => -1], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('page'))->toBeTrue();
});

test('AdminPrescriptionFilterRequest rejects limit over 100', function () {
    $request = new AdminPrescriptionFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['limit' => 200], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('limit'))->toBeTrue();
});

test('AdminPrescriptionFilterRequest passes with valid filters', function () {
    $request = new AdminPrescriptionFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make([
        'status' => 'consumed',
        'from' => '2026-01-01',
        'to' => '2026-06-01',
        'page' => 1,
        'limit' => 50,
    ], $rules);

    expect($validator->passes())->toBeTrue();
});

test('AdminPrescriptionFilterRequest authorizes all users', function () {
    $request = new AdminPrescriptionFilterRequest;
    expect($request->authorize())->toBeTrue();
});

// --------------------------------------------------------------------
// UserFilterRequest validation rules
// --------------------------------------------------------------------

test('UserFilterRequest rejects invalid role', function () {
    $request = new UserFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['role' => 'supervisor'], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('role'))->toBeTrue();
});

test('UserFilterRequest passes with valid roles', function () {
    $request = new UserFilterRequest;
    $rules = $request->rules();

    foreach (['admin', 'doctor', 'patient'] as $role) {
        $validator = Validator::make(['role' => $role], $rules);
        expect($validator->passes())->toBeTrue("Role '{$role}' should pass validation");
    }
});

test('UserFilterRequest passes with query string', function () {
    $request = new UserFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['query' => 'John'], $rules);

    expect($validator->passes())->toBeTrue();
});

test('UserFilterRequest rejects overly long query', function () {
    $request = new UserFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['query' => str_repeat('a', 256)], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('query'))->toBeTrue();
});

test('UserFilterRequest passes with no filters', function () {
    $request = new UserFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make([], $rules);

    expect($validator->passes())->toBeTrue();
});

test('UserFilterRequest authorizes all users', function () {
    $request = new UserFilterRequest;
    expect($request->authorize())->toBeTrue();
});

// --------------------------------------------------------------------
// DoctorFilterRequest validation rules
// --------------------------------------------------------------------

test('DoctorFilterRequest rejects negative page', function () {
    $request = new DoctorFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['page' => -1], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('page'))->toBeTrue();
});

test('DoctorFilterRequest rejects limit over 100', function () {
    $request = new DoctorFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['limit' => 101], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('limit'))->toBeTrue();
});

test('DoctorFilterRequest passes with valid filters', function () {
    $request = new DoctorFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make([
        'query' => 'Dr. Smith',
        'page' => 2,
        'limit' => 25,
    ], $rules);

    expect($validator->passes())->toBeTrue();
});

test('DoctorFilterRequest passes with no filters', function () {
    $request = new DoctorFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make([], $rules);

    expect($validator->passes())->toBeTrue();
});

test('DoctorFilterRequest rejects overly long query', function () {
    $request = new DoctorFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['query' => str_repeat('a', 256)], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('query'))->toBeTrue();
});

test('DoctorFilterRequest authorizes all users', function () {
    $request = new DoctorFilterRequest;
    expect($request->authorize())->toBeTrue();
});

// --------------------------------------------------------------------
// PatientFilterRequest validation rules
// --------------------------------------------------------------------

test('PatientFilterRequest rejects negative page', function () {
    $request = new PatientFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['page' => -1], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('page'))->toBeTrue();
});

test('PatientFilterRequest rejects limit over 100', function () {
    $request = new PatientFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['limit' => 200], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('limit'))->toBeTrue();
});

test('PatientFilterRequest passes with valid filters', function () {
    $request = new PatientFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make([
        'query' => 'John',
        'page' => 1,
        'limit' => 15,
    ], $rules);

    expect($validator->passes())->toBeTrue();
});

test('PatientFilterRequest passes with no filters', function () {
    $request = new PatientFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make([], $rules);

    expect($validator->passes())->toBeTrue();
});

test('PatientFilterRequest rejects overly long query', function () {
    $request = new PatientFilterRequest;
    $rules = $request->rules();

    $validator = Validator::make(['query' => str_repeat('a', 256)], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('query'))->toBeTrue();
});

test('PatientFilterRequest authorizes all users', function () {
    $request = new PatientFilterRequest;
    expect($request->authorize())->toBeTrue();
});
