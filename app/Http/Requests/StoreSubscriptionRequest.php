<?php

namespace App\Http\Requests;

use App\Models\StripePrice;
use App\Services\Stripe\StripeAccountRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $account = app(StripeAccountRegistry::class)->defaultAccount();

        return [
            'price_id' => [
                'required',
                'integer',
                Rule::exists(StripePrice::class, 'id')->where(fn ($query) => $query
                    ->where('stripe_account_id', $account->id)
                    ->where('is_active', true)
                    ->whereNotNull('billing_interval')),
            ],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}
