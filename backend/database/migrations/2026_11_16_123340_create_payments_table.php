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
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->string('gateway', 100)->comment('Configured payment provider name; the application supports one provider for this project.');
            $table->enum('environment', ['test'])->default('test');
            $table->string('gateway_payment_id')->nullable()->comment('Payment/transaction identifier returned by the gateway.');
            $table->string('gateway_event_id')->nullable()->comment('Webhook event identifier used to make callback processing idempotent.');
            $table->enum('status', ['pending', 'succeeded', 'failed', 'expired'])->default('pending');
            $table->decimal('amount', 12);
            $table->char('currency', 3)->comment('Single configured platform currency code');
            $table->string('idempotency_key', 100)->nullable();
            $table->string('failure_code', 100)->nullable();
            $table->string('failure_message', 500)->nullable()->comment('Sanitized provider failure message; must not contain card data, credentials, or secrets.');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['order_id', 'status'], 'payments_index_26');
            $table->unique(['gateway', 'gateway_payment_id'], 'payments_index_27');
            $table->unique(['gateway', 'gateway_event_id'], 'payments_index_28');
            $table->unique(['order_id', 'idempotency_key'], 'payments_index_29');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
