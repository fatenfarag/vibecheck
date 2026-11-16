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
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('organizer_user_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->index('events_index_6');
            $table->dateTime('ends_at')->nullable();
            $table->string('venue');
            $table->string('image_path', 2048)->comment('Storage path or object key for the uploaded event image, not the image binary.');
            $table->enum('status', ['draft', 'published', 'unpublished', 'rejected', 'suspended'])->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['organizer_user_id', 'status'], 'events_index_4');
            $table->index(['status', 'starts_at'], 'events_index_5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
