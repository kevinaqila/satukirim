<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'terms' => ['nullable', 'accepted'],
        ])->validate();

        $phone = null;
        if (! empty($input['phone'])) {
            $cleaned = preg_replace('/\D/', '', (string) $input['phone']);
            if (str_starts_with($cleaned, '62')) {
                $phone = '+'.$cleaned;
            } elseif (str_starts_with($cleaned, '0')) {
                $phone = '+62'.substr($cleaned, 1);
            } else {
                $phone = '+62'.$cleaned;
            }
        }

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $phone,
            'role' => 'umkm',
            'is_active' => true,
            'password' => $input['password'],
        ]);
    }
}
