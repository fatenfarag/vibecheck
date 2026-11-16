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
        Schema::table('ticket_scan_logs', function (Blueprint $table) {
            $table->foreign(['event_id'], 'ticket_scan_logs_ibfk_1')->references(['id'])->on('events')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['ticket_id'], 'ticket_scan_logs_ibfk_2')->references(['id'])->on('tickets')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['gate_staff_user_id'], 'ticket_scan_logs_ibfk_3')->references(['id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_scan_logs', function (Blueprint $table) {
            $table->dropForeign('ticket_scan_logs_ibfk_1');
            $table->dropForeign('ticket_scan_logs_ibfk_2');
            $table->dropForeign('ticket_scan_logs_ibfk_3');
        });
    }
};
