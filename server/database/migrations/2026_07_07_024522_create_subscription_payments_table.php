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
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('subscription_invoice_id')->constrained()->cascadeOnDelete(); // Invoice uses ID because it's table->id()
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            
            $table->string('payment_gateway')->default('PAYDUNYA'); // PAYDUNYA, CINETPAY, etc.
            $table->string('payment_method')->nullable(); // WAVE, ORANGE_MONEY, CARD
            
            $table->string('transaction_reference')->nullable()->unique(); // Reference from gateway
            
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('XOF');
            
            // Enum: PENDING, SUCCESS, FAILED
            $table->string('status')->default('PENDING');
            
            $table->json('payload')->nullable(); // Store the full response from webhook

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
