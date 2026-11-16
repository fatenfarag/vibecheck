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
        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('ticket_type_id');
            $table->unsignedInteger('quantity')->comment('Number of tickets requested for this ticket type; must be a positive integer.');
            $table->decimal('unit_price', 12)->comment('Price snapshot taken when the order is created; do not recalculate from the current ticket type price.');
            $table->decimal('line_total', 12)->comment('Server-calculated unit_price multiplied by quantity.');

            $table->unique(['order_id', 'ticket_type_id'], 'order_items_index_13');
            $table->index(['ticket_type_id', 'order_id'], 'order_items_index_14');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
