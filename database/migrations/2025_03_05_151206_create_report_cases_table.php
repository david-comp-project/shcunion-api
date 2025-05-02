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
        Schema::create('report_cases', function (Blueprint $table) {
            $table->uuid('report_case_id')->primary();
            $table->uuid('reporter_id')->reference('user_id')->on('users')->onDelete('cascade')->nullable();
            $table->uuid('reported_id')->reference('user_id')->on('users')->onDelete('cascade')->nullable();
            $table->uuid('project_id')->reference('project_id')->on('projects')->onDelete('cascade')->nullable();
            $table->string('reported_case')->nullable();
            $table->text('reported_comment')->nullable();
            $table->string('reported_path_file')->nullable();
            $table->string('reported_segment')->nullable();
            $table->boolean('checked')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_cases');
    }
};
