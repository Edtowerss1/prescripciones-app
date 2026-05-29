<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    /**
     * Determine if the user can create prescriptions.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('doctor');
    }

    /**
     * Determine if the user can view a prescription.
     *
     * Admins can view any. Doctors can view their own. Patients can view their own.
     */
    public function view(User $user, Prescription $prescription): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('doctor') && $prescription->doctor->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('patient') && $prescription->patient->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can consume a prescription.
     *
     * Only the owning patient can consume.
     */
    public function consume(User $user, Prescription $prescription): bool
    {
        return $user->hasRole('patient') && $prescription->patient->user_id === $user->id;
    }
}
