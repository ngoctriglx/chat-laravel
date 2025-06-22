<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->uuid('last_message_id')->nullable()->after('metadata');
            
            $table->foreign('last_message_id')
                  ->references('id')
                  ->on('messages')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['last_message_id']);
            $table->dropColumn('last_message_id');
        });
    }
}; 