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
        Schema::create('message_group_chats', function (Blueprint $table) {
            $table->uuid('message_group_chat_id')->primary();
            $table->uuid('sender_id')->reference('user_id')->on('users')->onDelete('cascade');;
            $table->uuid('group_chat_id')->reference('group_chat_is')->on('group_chats')->onDelete('cascade');;
            $table->text('group_chat_text');
            $table->string('media_path')->nullable();            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_group_chats');
    }
};
