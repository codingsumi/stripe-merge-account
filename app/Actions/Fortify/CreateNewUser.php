<?php

namespace App\Actions\Fortify;

use App\Actions\Stripe\EnsureStripeCustomer;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Exceptions\StripeOperationException;
use App\Models\User;
use App\Services\Stripe\StripeAccountRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(
        private EnsureStripeCustomer $ensureStripeCustomer,
        private StripeAccountRegistry $accounts,
    ) {}

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
        ])->validate();

        try {
            return DB::transaction(function () use ($input): User {
                $user = User::create([
                    'name' => $input['name'],
                    'email' => $input['email'],
                    'password' => $input['password'],
                ]);

                $this->ensureStripeCustomer->handle($user, $this->accounts->defaultAccount());

                return $user;
            });
        } catch (StripeOperationException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'email' => 'We could not finish setting up billing. Please try again.',
            ]);
        }
    }
}
