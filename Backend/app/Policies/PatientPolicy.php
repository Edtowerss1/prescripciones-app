<?php

namespace App\Policies;

use App\Models\User;

class PatientPolicy
{
    /**
     * Determine if the user can view the patient list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }
}
