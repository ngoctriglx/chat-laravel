<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->unsignedBigInteger('user_id');
            $table->string('reaction_type', 50);
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'reaction_type']);
            
            $table->foreign('message_id')
                  ->references('id')
                  ->on('messages')
                  ->onDelete('cascade');
                  
            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            $table->index(['message_id', 'reaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
    }
}; 