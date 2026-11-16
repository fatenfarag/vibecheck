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
        Schema::create('tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->index('tickets_index_17');
            $table->unsignedBigInteger('order_item_id')->index('tickets_index_18');
            $table->unsignedBigInteger('ticket_type_id');
            $table->unsignedBigInteger('attendee_user_id');
            $table->string('ticket_number', 40)->unique('ticket_number');
            $table->char('qr_token_hash', 64)->unique('qr_token_hash')->comment('SHA-256 hash of a high-entropy opaque QR token. Store the hash only; never persist the raw QR token.');
            $table->enum('status', ['valid', 'used', 'cancelled'])->default('valid')->comment('Valid means paid and not yet redeemed; only paid orders may receive issued tickets.');
            $table->dateTime('issued_at');
            $table->dateTime('redeemed_at')->nullable();
            $table->unsignedBigInteger('redeemed_by_user_id')->nullable()->comment('Gate staff user who completed the first successful redemption.');
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['attendee_user_id', 'status'], 'tickets_index_15');
            $table->index(['ticket_type_id', 'status'], 'tickets_index_16');
            $table->index(['redeemed_by_user_id', 'redeemed_at'], 'tickets_index_19');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
