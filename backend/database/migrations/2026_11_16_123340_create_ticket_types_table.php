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
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('event_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 12)->comment('Price in the single platform-configured currency.');
            $table->unsignedInteger('total_quantity')->comment('Total sellable inventory. Remaining stock is derived from paid tickets and unexpired pending order reservations.');
            $table->dateTime('sales_starts_at')->nullable();
            $table->dateTime('sales_ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['event_id', 'is_active'], 'ticket_types_index_7');
            $table->unique(['event_id', 'name'], 'ticket_types_index_8');
            $table->index(['sales_starts_at', 'sales_ends_at'], 'ticket_types_index_9');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
