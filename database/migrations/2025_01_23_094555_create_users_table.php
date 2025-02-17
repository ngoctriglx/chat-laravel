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
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('user_name', 50)->unique()->nullable();
            $table->string('user_email', 50)->unique()->nullable();
            $table->string('user_phone', 50)->unique()->nullable();
            $table->string('user_password');
            $table->enum('user_account_status', ['active', 'pending', 'suspended', 'banned', 'deactivated'])->default('pending');
            $table->string('user_banned_reason')->nullable();
            $table->timestamp('user_registered');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
