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
        Schema::table('user_permissions', function (Blueprint $table) {
            $table->foreign(['permission_id'], 'user_permissions_ibfk_1')->references(['id'])->on('permissions')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['user_id'], 'user_permissions_ibfk_2')->references(['id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            $table->dropForeign('user_permissions_ibfk_1');
            $table->dropForeign('user_permissions_ibfk_2');
        });
    }
};
