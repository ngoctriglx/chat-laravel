<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->unsignedBigInteger('sender_id');
            $table->text('content');
            $table->string('type', 50); // text, image, file, etc.
            $table->json('metadata')->nullable();
            $table->uuid('parent_message_id')->nullable();
            $table->bigInteger('cursor_id')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('conversation_id')
                  ->references('id')
                  ->on('conversations')
                  ->onDelete('cascade');
                  
            $table->foreign('sender_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            $table->foreign('parent_message_id')
                  ->references('id')
                  ->on('messages')
                  ->onDelete('set null');

            // Indexes for better performance
            $table->index('conversation_id');
            $table->index('created_at');
            $table->index('cursor_id');
            $table->index('sender_id');
            $table->index('parent_message_id');
        });

        // Create message_visibility table
        Schema::create('message_visibilities', function (Blueprint $table) {
            $table->uuid('message_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_visible')->default(true);
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->primary(['message_id', 'user_id']);
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_visibilities');
        Schema::dropIfExists('messages');
    }
}; 