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
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign(['order_id'], 'tickets_ibfk_1')->references(['id'])->on('orders')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['order_item_id'], 'tickets_ibfk_2')->references(['id'])->on('order_items')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['ticket_type_id'], 'tickets_ibfk_3')->references(['id'])->on('ticket_types')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['attendee_user_id'], 'tickets_ibfk_4')->references(['id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['redeemed_by_user_id'], 'tickets_ibfk_5')->references(['id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign('tickets_ibfk_1');
            $table->dropForeign('tickets_ibfk_2');
            $table->dropForeign('tickets_ibfk_3');
            $table->dropForeign('tickets_ibfk_4');
            $table->dropForeign('tickets_ibfk_5');
        });
    }
};
