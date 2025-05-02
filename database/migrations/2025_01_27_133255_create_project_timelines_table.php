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
        Schema::create('project_timelines', function (Blueprint $table) {
            $table->uuid('project_timeline_id')->primary();
            $table->uuid('project_id')->references('project_id')->on('projects')->onDelete('cascade'); 
            $table->dateTimeTz('timeline_date');
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_timelines');
    }
};
