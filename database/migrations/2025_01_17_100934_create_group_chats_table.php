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
        Schema::create('group_chats', function (Blueprint $table) {
            $table->uuid('group_chat_id')->primary();
            $table->string('group_chat_name');
            $table->uuid('initiator_user_id')->reference('user_id')->on('users')->onDelete('cascade');
            $table->uuid('project_id')->reference('project_id')->on('projects')->onDelete('cascade');;
            $table->string('group_avatar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_chats');
    }
};
