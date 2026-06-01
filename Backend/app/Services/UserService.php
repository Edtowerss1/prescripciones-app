<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserService
{
    /**
     * Create a new user with role assignment and optional profile.
     *
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $role = Role::findOrCreate($data['role'], 'api');
        $user->roles()->attach($role->id);

        if ($data['role'] === 'doctor') {
            Doctor::create([
                'user_id' => $user->id,
                'specialty' => null,
                'license_number' => null,
            ]);
        }

        if ($data['role'] === 'patient') {
            Patient::create([
                'user_id' => $user->id,
            ]);
        }

        return $user;
    }
}
