<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_cycles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();    
            $table->string('name');
            $table->integer('duration_days');
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); // BOOLEAN, QUOTA, METERED (uppercase per feedback) from enums folder
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->foreignUuid('feature_id')->constrained('features')->cascadeOnDelete();
            $table->integer('limit_value')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->unique(['subscription_plan_id', 'feature_id']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'status', 'end_date']);
            $table->renameColumn('start_date', 'starts_at');
            $table->renameColumn('end_date', 'ends_at');
            $table->renameColumn('cancel_reason', 'cancellation_reason');
            $table->dropColumn('trial_ends_at');
            $table->dropColumn('status');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignUuid('billing_cycle_id')->nullable()->after('subscription_plan_id')->constrained('billing_cycles')->nullOnDelete();
            $table->string('status')->after('billing_cycle_id');
            $table->timestamp('grace_ends_at')->nullable()->after('ends_at');
            
            // Snapshots
            $table->string('plan_name_snapshot')->nullable()->after('cancellation_reason');
            $table->decimal('plan_price_snapshot', 10, 2)->nullable()->after('plan_name_snapshot');
            $table->string('billing_cycle_snapshot')->nullable()->after('plan_price_snapshot');
            $table->decimal('discount_snapshot', 5, 2)->nullable()->after('billing_cycle_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['billing_cycle_id']);
            $table->dropColumn([
                'billing_cycle_id',
                'grace_ends_at',
                'plan_name_snapshot',
                'plan_price_snapshot',
                'billing_cycle_snapshot',
                'discount_snapshot',
            ]);
            $table->enum('status', ['ACTIVE', 'EXPIRED', 'SUSPENDED', 'CANCELLED'])->default('ACTIVE');
            $table->timestamp('trial_ends_at')->nullable();
            $table->renameColumn('starts_at', 'start_date');
            $table->renameColumn('ends_at', 'end_date');
            $table->renameColumn('cancellation_reason', 'cancel_reason');
        });

        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('features');
        Schema::dropIfExists('billing_cycles');
    }
};
