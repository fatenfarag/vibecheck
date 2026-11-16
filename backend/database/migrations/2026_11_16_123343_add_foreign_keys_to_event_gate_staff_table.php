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
        Schema::table('event_gate_staff', function (Blueprint $table) {
            $table->foreign(['event_id'], 'event_gate_staff_ibfk_1')->references(['id'])->on('events')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['user_id'], 'event_gate_staff_ibfk_2')->references(['id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['assigned_by_user_id'], 'event_gate_staff_ibfk_3')->references(['id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_gate_staff', function (Blueprint $table) {
            $table->dropForeign('event_gate_staff_ibfk_1');
            $table->dropForeign('event_gate_staff_ibfk_2');
            $table->dropForeign('event_gate_staff_ibfk_3');
        });
    }
};
