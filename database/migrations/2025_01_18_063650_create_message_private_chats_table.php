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
        Schema::create('message_private_chats', function (Blueprint $table) {
            $table->uuid('message_private_chat_id')->primary();
            $table->uuid('user_id')->reference('user_id')->on('users')->onDelete('cascade');;
            $table->uuid('sender_id')->reference('user_id')->on('users')->onDelete('cascade');;
            $table->text('private_chat_text');
            $table->string('media_path')->nullable();  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_private_chats');
    }
};
