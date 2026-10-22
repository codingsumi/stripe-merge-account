import {
    Elements,
    PaymentElement,
    useElements,
    useStripe,
} from '@stripe/react-stripe-js';
import { loadStripe } from '@stripe/stripe-js';
import { router, useHttp } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { index as billing } from '@/routes/billing';
import { store as storePaymentMethod } from '@/routes/billing/payment-methods';
import { store as createSetupIntent } from '@/routes/billing/setup-intent';

type SetupIntentResponse = {
    client_secret: string;
    publishable_key: string;
};

function PaymentMethodForm({ onCancel }: { onCancel: () => void }) {
    const stripe = useStripe();
    const elements = useElements();
    const [message, setMessage] = useState<string>();
    const paymentMethodRequest = useHttp({ setup_intent_id: '' });

    const savePaymentMethod = async (setupIntentId: string) => {
        paymentMethodRequest.transform(() => ({
            setup_intent_id: setupIntentId,
        }));

        await paymentMethodRequest.post(storePaymentMethod.url(), {
            onSuccess: () => router.visit(billing()),
            onHttpException: () => {
                setMessage(
                    'The card was saved by Stripe, but could not be synchronized. Please try again.',
                );
            },
        });
    };

    const submit = async (event: React.FormEvent) => {
        event.preventDefault();

        if (!stripe || !elements) {
            return;
        }

        setMessage(undefined);
        const result = await stripe.confirmSetup({
            elements,
            confirmParams: {
                return_url: `${window.location.origin}/dashboard`,
            },
            redirect: 'if_required',
        });

        if (result.error) {
            setMessage(result.error.message ?? 'Your card could not be saved.');
            return;
        }

        if (result.setupIntent.status === 'succeeded') {
            await savePaymentMethod(result.setupIntent.id);
        }
    };

    return (
        <form onSubmit={submit} className="grid gap-5">
            <PaymentElement options={{ layout: 'tabs' }} />
            {(message || paymentMethodRequest.errors.setup_intent_id) && (
                <p className="text-sm text-destructive">
                    {message ??
                        String(paymentMethodRequest.errors.setup_intent_id)}
                </p>
            )}
            <DialogFooter>
                <Button type="button" variant="outline" onClick={onCancel}>
                    Not now
                </Button>
                <Button
                    type="submit"
                    disabled={!stripe || paymentMethodRequest.processing}
                >
                    {paymentMethodRequest.processing && <Spinner />}
                    Save card
                </Button>
            </DialogFooter>
        </form>
    );
}

export function PaymentMethodModal({ open }: { open: boolean }) {
    const [dismissed, setDismissed] = useState(false);
    const [setup, setSetup] = useState<SetupIntentResponse>();
    const [message, setMessage] = useState<string>();
    const requested = useRef(false);
    const setupRequest = useHttp({});
    const returnRequest = useHttp({ setup_intent_id: '' });
    const stripePromise = useMemo(
        () => (setup ? loadStripe(setup.publishable_key) : null),
        [setup],
    );

    useEffect(() => {
        if (!open || dismissed || requested.current) {
            return;
        }

        requested.current = true;

        const returnedSetupIntent = new URLSearchParams(
            window.location.search,
        ).get('setup_intent');

        if (returnedSetupIntent) {
            returnRequest.transform(() => ({
                setup_intent_id: returnedSetupIntent,
            }));
            void returnRequest.post(storePaymentMethod.url(), {
                onSuccess: () => router.visit(billing()),
                onHttpException: () => {
                    setMessage(
                        'The authenticated card could not be synchronized. Please try again.',
                    );
                },
            });
            return;
        }

        void setupRequest.post(createSetupIntent.url(), {
            onSuccess: (response) => setSetup(response as SetupIntentResponse),
            onHttpException: () => {
                setMessage(
                    'Billing is temporarily unavailable. Please try again.',
                );
            },
            onNetworkError: () => {
                setMessage(
                    'Could not connect to billing. Check your connection and try again.',
                );
            },
        });
    }, [dismissed, open]);

    return (
        <Dialog
            open={open && !dismissed}
            onOpenChange={(isOpen) => !isOpen && setDismissed(true)}
        >
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Add a payment card</DialogTitle>
                    <DialogDescription>
                        Save a card securely with Stripe before choosing your
                        subscription. Card numbers and CVC never reach this
                        application.
                    </DialogDescription>
                </DialogHeader>

                {setup && stripePromise ? (
                    <Elements
                        stripe={stripePromise}
                        options={{ clientSecret: setup.client_secret }}
                    >
                        <PaymentMethodForm
                            onCancel={() => setDismissed(true)}
                        />
                    </Elements>
                ) : (
                    <div className="flex min-h-28 items-center justify-center">
                        {message ? (
                            <p className="text-sm text-destructive">
                                {message}
                            </p>
                        ) : (
                            <Spinner className="size-6" />
                        )}
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
