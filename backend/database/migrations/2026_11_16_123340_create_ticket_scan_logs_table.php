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
        Schema::create('ticket_scan_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->unsignedBigInteger('gate_staff_user_id');
            $table->enum('outcome', ['valid', 'invalid', 'used']);
            $table->string('failure_reason', 100)->nullable();
            $table->dateTime('scanned_at')->comment('Timestamp of the scan attempt; valid means this scan successfully changed the ticket from valid to used.');

            $table->index(['event_id', 'scanned_at'], 'ticket_scan_logs_index_22');
            $table->index(['gate_staff_user_id', 'scanned_at'], 'ticket_scan_logs_index_23');
            $table->index(['ticket_id', 'scanned_at'], 'ticket_scan_logs_index_24');
            $table->index(['outcome', 'scanned_at'], 'ticket_scan_logs_index_25');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_scan_logs');
    }
};
