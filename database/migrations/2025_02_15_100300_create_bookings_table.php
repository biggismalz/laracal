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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending_payment', 'payment_failed', 'confirmed', 'cancelled', 'completed'])->default('pending_payment');
            $table->enum('payment_option', ['full', 'deposit']);
            $table->timestampTz('scheduled_start');
            $table->timestampTz('scheduled_end');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->text('customer_notes')->nullable();
            $table->unsignedInteger('list_price_cents');
            $table->unsignedInteger('amount_charged_cents');
            $table->unsignedInteger('amount_paid_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('stripe_checkout_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
