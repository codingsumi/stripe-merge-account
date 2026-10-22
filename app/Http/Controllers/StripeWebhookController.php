<?php

namespace App\Http\Controllers;

use App\Actions\Stripe\ProcessStripeWebhook;
use App\Contracts\StripeGateway;
use App\Exceptions\StripeOperationException;
use App\Models\StripeWebhookEvent;
use App\Services\Stripe\StripeAccountRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $accountKey,
        StripeAccountRegistry $accounts,
        StripeGateway $stripe,
        ProcessStripeWebhook $processor,
    ): Response {
        try {
            $account = $accounts->account($accountKey);
            $event = $stripe->constructWebhookEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $accounts->webhookSecret($account),
            );
        } catch (StripeOperationException) {
            return response('Invalid webhook signature.', 400);
        }

        $storedEvent = StripeWebhookEvent::query()->firstOrCreate(
            ['stripe_account_id' => $account->id, 'stripe_event_id' => $event['id']],
            [
                'event_type' => $event['type'],
                'status' => 'processing',
                'payload' => $event,
            ],
        );

        if (! $storedEvent->wasRecentlyCreated && $storedEvent->status === 'processed') {
            return response('Already processed.');
        }

        try {
            $storedEvent->update(['status' => 'processing', 'error_details' => null]);
            $processor->handle($account, $event);
            $storedEvent->update(['status' => 'processed', 'processed_at' => now()]);
        } catch (Throwable $exception) {
            $storedEvent->update([
                'status' => 'failed',
                'error_details' => $exception->getMessage(),
            ]);

            report($exception);

            return response('Webhook processing failed.', 500);
        }

        return response('Processed.');
    }
}
