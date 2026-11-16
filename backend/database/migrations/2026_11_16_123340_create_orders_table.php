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
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('attendee_user_id');
            $table->string('order_number', 40)->unique('order_number');
            $table->enum('status', ['pending', 'paid', 'failed', 'expired'])->default('pending');
            $table->decimal('total_amount', 12)->comment('Server-calculated order total using unit-price snapshots; no tax, discount, or fee engine is included.');
            $table->dateTime('reservation_expires_at')->nullable()->comment('While status is pending, this is the inventory hold expiry. Expired holds no longer count against availability.');
            $table->string('idempotency_key', 100)->nullable()->comment('Client-supplied retry key; unique per attendee when present.');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['attendee_user_id', 'status', 'created_at'], 'orders_index_10');
            $table->index(['status', 'reservation_expires_at'], 'orders_index_11');
            $table->unique(['attendee_user_id', 'idempotency_key'], 'orders_index_12');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
