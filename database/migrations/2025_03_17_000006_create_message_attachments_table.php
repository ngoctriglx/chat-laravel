<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->string('file_name');
            $table->string('file_type', 100);
            $table->bigInteger('file_size');
            $table->string('file_path');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('message_id')
                  ->references('id')
                  ->on('messages')
                  ->onDelete('cascade');
                  
            $table->index('message_id');
            $table->index('file_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
}; 