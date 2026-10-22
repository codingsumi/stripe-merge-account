import { loadStripe } from '@stripe/stripe-js';
import { Head, router, useHttp } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { index as billing } from '@/routes/billing';
import { store as storeSubscription } from '@/routes/billing/subscriptions';

type Price = {
    id: number;
    name: string;
    description: string | null;
    amount: number | null;
    currency: string;
    interval: string;
    interval_count: number;
};

type Subscription = {
    status: string;
    current_period_end: string | null;
    cancel_at_period_end: boolean;
} | null;

type SubscriptionResponse = {
    status: string;
    client_secret: string | null;
};

function formatPrice(price: Price): string {
    if (price.amount === null) {
        return 'Custom';
    }

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: price.currency.toUpperCase(),
    }).format(price.amount / 100);
}

export default function Billing({
    prices,
    subscription,
    publishableKey,
}: {
    prices: Price[];
    subscription: Subscription;
    publishableKey: string;
}) {
    const [selectedPrice, setSelectedPrice] = useState<number>();
    const [result, setResult] = useState<string>();
    const stripePromise = useMemo(
        () => loadStripe(publishableKey),
        [publishableKey],
    );
    const request = useHttp({ price_id: 0, idempotency_key: '' });
    const subscriptionError = (
        request.errors as typeof request.errors & { subscription?: string }
    ).subscription;

    const subscribe = async (priceId: number) => {
        setSelectedPrice(priceId);
        setResult(undefined);
        request.transform(() => ({
            price_id: priceId,
            idempotency_key: crypto.randomUUID(),
        }));

        await request.post(storeSubscription.url(), {
            onSuccess: async (response) => {
                const subscriptionResponse = response as SubscriptionResponse;

                if (subscriptionResponse.client_secret) {
                    const stripe = await stripePromise;
                    const confirmation = await stripe?.confirmPayment({
                        clientSecret: subscriptionResponse.client_secret,
                        confirmParams: {
                            return_url: `${window.location.origin}${billing.url()}`,
                        },
                        redirect: 'if_required',
                    });

                    if (confirmation?.error) {
                        setResult(
                            confirmation.error.message ??
                                'Payment confirmation failed.',
                        );
                        return;
                    }
                }

                setResult(
                    subscriptionResponse.status === 'active' ||
                        subscriptionResponse.status === 'trialing'
                        ? 'Your Pro subscription is active.'
                        : 'Payment submitted. Your access will update after Stripe confirms it.',
                );
                router.reload({ only: ['subscription', 'auth'] });
            },
            onHttpException: () =>
                setResult(
                    'The subscription could not be created. Please try again.',
                ),
        });
    };

    return (
        <>
            <Head title="Choose a subscription" />
            <div className="flex flex-1 flex-col gap-8 p-4 md:p-8">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Choose your plan"
                        description="All payments are processed securely by Stripe."
                    />
                    {subscription && (
                        <Badge variant="secondary" className="capitalize">
                            {subscription.status.replace('_', ' ')}
                        </Badge>
                    )}
                </div>

                {result && (
                    <div className="rounded-lg border bg-muted/50 p-4 text-sm">
                        {result}
                    </div>
                )}

                {prices.length === 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>No plans available</CardTitle>
                            <CardDescription>
                                Ask an administrator to synchronize the Stripe
                                catalog.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                ) : (
                    <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        {prices.map((price) => (
                            <Card key={price.id} className="flex flex-col">
                                <CardHeader>
                                    <CardTitle>{price.name}</CardTitle>
                                    <CardDescription>
                                        {price.description}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="flex-1 space-y-4">
                                    <div>
                                        <span className="text-3xl font-semibold">
                                            {formatPrice(price)}
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            {' '}
                                            /{' '}
                                            {price.interval_count > 1
                                                ? `${price.interval_count} `
                                                : ''}
                                            {price.interval}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Check className="size-4 text-green-600" />
                                        Pro access while the subscription is
                                        eligible
                                    </div>
                                </CardContent>
                                <CardFooter>
                                    <Button
                                        className="w-full"
                                        disabled={
                                            request.processing ||
                                            subscription !== null
                                        }
                                        onClick={() => void subscribe(price.id)}
                                    >
                                        {request.processing &&
                                            selectedPrice === price.id && (
                                                <Spinner />
                                            )}
                                        {subscription
                                            ? 'Subscription exists'
                                            : 'Subscribe'}
                                    </Button>
                                </CardFooter>
                            </Card>
                        ))}
                    </div>
                )}

                {subscriptionError && (
                    <p className="text-sm text-destructive">
                        {String(subscriptionError)}
                    </p>
                )}
            </div>
        </>
    );
}

Billing.layout = {
    breadcrumbs: [
        {
            title: 'Billing',
            href: billing(),
        },
    ],
};
