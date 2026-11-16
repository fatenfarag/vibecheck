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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('actor_user_id')->nullable()->comment('Null for system-generated activity.');
            $table->string('action', 150);
            $table->string('subject_type')->nullable()->comment('Polymorphic model class for the affected record. event or user');
            $table->unsignedBigInteger('subject_id')->nullable()->comment('ID of the affected record; polymorphic and therefore has no direct foreign key.');
            $table->json('metadata')->nullable()->comment('Sanitized audit metadata only; never store passwords, tokens, QR secrets, card data, or provider secrets.');
            $table->dateTime('created_at');

            $table->index(['actor_user_id', 'created_at'], 'audit_logs_index_30');
            $table->index(['subject_type', 'subject_id', 'created_at'], 'audit_logs_index_31');
            $table->index(['action', 'created_at'], 'audit_logs_index_32');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
