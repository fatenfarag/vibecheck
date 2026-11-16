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
        Schema::create('event_gate_staff', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assigned_by_user_id')->nullable()->index('assigned_by_user_id');
            $table->dateTime('created_at');

            $table->unique(['event_id', 'user_id'], 'event_gate_staff_index_20');
            $table->index(['user_id', 'event_id'], 'event_gate_staff_index_21');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_gate_staff');
    }
};
