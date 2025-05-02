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
        Schema::create('project_evaluasis', function (Blueprint $table) {
            $table->uuid('project_evaluasi_id')->primary();
            $table->uuid('project_id')->references('project_id')->on('projects')->onDelete('cascade');
            $table->uuid('evaluator_id')->reference('user_id')->on('users')->onDelete('cascade');;
            $table->string('task_comment');
            $table->string('tag_component');
            $table->boolean('checked');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_evaluasis');
    }
};
