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
        Schema::create('project_timeline_details', function (Blueprint $table) {
            $table->uuid('project_timeline_detail_id')->primary();
            $table->uuid('project_timeline_id')->references('project_timeline_id')->on('project_timelines')->onDelete('cascade'); 
            $table->string('description'); // Target aksi
            $table->time('time'); // Waktu aktivitas
            $table->uuid('icon_id')->reference('icon_id')->on('icons')->onDelete('cascade'); // Ikon aktivitas
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_timeline_details');
    }
};
