<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id('setting_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->enum('theme_mode', ['light', 'dark', 'system'])->default('light');
            $table->string('language', 10);
            $table->enum('allow_friend_requests', ['everyone', 'friends_of_friends', 'contacts_only', 'nobody'])->default('everyone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('user_settings');
    }
};
