<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stripe_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('display_name');
            $table->string('mode');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stripe_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stripe_account_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_customer_id');
            $table->timestamps();

            $table->unique(['user_id', 'stripe_account_id']);
            $table->unique(['stripe_account_id', 'stripe_customer_id']);
        });

        Schema::create('stripe_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stripe_account_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_product_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['stripe_account_id', 'stripe_product_id']);
        });

        Schema::create('stripe_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stripe_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stripe_product_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_price_id');
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('currency', 3);
            $table->string('billing_interval')->nullable();
            $table->unsignedInteger('interval_count')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['stripe_account_id', 'stripe_price_id']);
            $table->index(['stripe_account_id', 'is_active', 'billing_interval']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stripe_account_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_customer_id');
            $table->string('stripe_subscription_id');
            $table->string('stripe_price_id');
            $table->string('status');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('trial_end')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['stripe_account_id', 'stripe_subscription_id']);
            $table->index(['user_id', 'status', 'current_period_end']);
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stripe_account_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_customer_id');
            $table->string('stripe_payment_method_id');
            $table->string('card_brand')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->unsignedTinyInteger('expiration_month')->nullable();
            $table->unsignedSmallInteger('expiration_year')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(
                ['stripe_account_id', 'stripe_payment_method_id'],
                'payment_methods_account_method_unique',
            );
            $table->index(['user_id', 'stripe_account_id', 'is_default']);
        });

        Schema::create('stripe_migration_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_stripe_account_id')->constrained('stripe_accounts')->cascadeOnDelete();
            $table->foreignId('target_stripe_account_id')->constrained('stripe_accounts')->cascadeOnDelete();
            $table->string('entity_type');
            $table->string('source_entity_id');
            $table->string('target_entity_id')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_details')->nullable();
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamps();

            $table->unique([
                'source_stripe_account_id',
                'target_stripe_account_id',
                'entity_type',
                'source_entity_id',
            ], 'stripe_migration_source_unique');
        });

        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stripe_account_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_event_id');
            $table->string('event_type');
            $table->string('status')->default('processing');
            $table->json('payload');
            $table->text('error_details')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['stripe_account_id', 'stripe_event_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
        Schema::dropIfExists('stripe_migration_mappings');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('stripe_prices');
        Schema::dropIfExists('stripe_products');
        Schema::dropIfExists('stripe_customers');
        Schema::dropIfExists('stripe_accounts');
    }
};
