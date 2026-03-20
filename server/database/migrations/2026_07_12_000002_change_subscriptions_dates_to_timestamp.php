<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * subscriptions.starts_at/ends_at were created as DATE columns but the
 * Subscription model casts them as 'datetime'. On PostgreSQL, comparing a
 * DATE column to a bound timestamp parameter (e.g. `where('ends_at', '<',
 * now())`) makes Postgres infer the parameter's type as DATE too, silently
 * truncating the time-of-day — so a subscription expiring earlier today is
 * never matched by CheckSubscriptionExpiryCommand until the next day.
 * SQLite has no real column-type enforcement, so this never surfaced there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::create('subscriptions_new', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                $table->boolean('auto_renew')->default(true);
                $table->timestamp('cancelled_at')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->foreignUuid('billing_cycle_id')->nullable()->constrained('billing_cycles')->nullOnDelete();
                $table->string('status');
                $table->timestamp('grace_ends_at')->nullable();
                $table->string('plan_name_snapshot')->nullable();
                $table->decimal('plan_price_snapshot', 10, 2)->nullable();
                $table->string('billing_cycle_snapshot')->nullable();
                $table->decimal('discount_snapshot', 5, 2)->nullable();
                $table->timestamps();
            });

            DB::statement('
                INSERT INTO subscriptions_new (
                    id, school_id, subscription_plan_id, starts_at, ends_at, auto_renew,
                    cancelled_at, cancellation_reason, billing_cycle_id, status, grace_ends_at,
                    plan_name_snapshot, plan_price_snapshot, billing_cycle_snapshot, discount_snapshot,
                    created_at, updated_at
                )
                SELECT
                    id, school_id, subscription_plan_id, starts_at, ends_at, auto_renew,
                    cancelled_at, cancellation_reason, billing_cycle_id, status, grace_ends_at,
                    plan_name_snapshot, plan_price_snapshot, billing_cycle_snapshot, discount_snapshot,
                    created_at, updated_at
                FROM subscriptions
            ');

            Schema::drop('subscriptions');
            Schema::rename('subscriptions_new', 'subscriptions');

            return;
        }

        DB::statement('ALTER TABLE subscriptions ALTER COLUMN starts_at TYPE timestamp(0) without time zone USING starts_at::timestamp');
        DB::statement('ALTER TABLE subscriptions ALTER COLUMN ends_at TYPE timestamp(0) without time zone USING ends_at::timestamp');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Irreversible on SQLite: rebuilding back to DATE would truncate
            // any time-of-day precision already stored.
            return;
        }

        DB::statement('ALTER TABLE subscriptions ALTER COLUMN starts_at TYPE date USING starts_at::date');
        DB::statement('ALTER TABLE subscriptions ALTER COLUMN ends_at TYPE date USING ends_at::date');
    }
};
