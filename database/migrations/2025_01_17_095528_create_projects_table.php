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
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('project_id')->primary();
            $table->string('project_title');
            $table->text('project_description');
            $table->dateTimeTz('project_start_date');
            $table->dateTimeTz('project_end_date');
            $table->float('project_target_amount');
            $table->uuid('creator_id')->reference('user_id')->on('users')->onDelete('cascade');
            $table->string('project_status');
            $table->string('project_category');
            $table->string('project_address');
            $table->json('project_role')->nullable();
            $table->json('project_criteria')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();            
            $table->string('kode_desa')->nullable();
            $table->string('project_image_path')->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
