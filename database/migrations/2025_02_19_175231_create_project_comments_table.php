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
        Schema::create('project_comments', function (Blueprint $table) {
            $table->uuid('project_comment_id')->primary();
            $table->uuid('project_id')->references('project_id')->on('projects')->onDelete('cascade'); // Definisikan sebelum foreign key
            $table->uuid('user_id')->references('user_id')->on('users')->onDelete('set null')->nullable(); // Bisa NULL karena opsional
            $table->uuid('project_comment_parent_id')->nullable(); // Balasan bisa NULL
            $table->text('comment');
            $table->softDeletes();
            $table->timestamps();
        });

    }
        

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_comments');
    }
};
