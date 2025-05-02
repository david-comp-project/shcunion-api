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
        Schema::create('project_lampirans', function (Blueprint $table) {
            $table->uuid('project_lampiran_id')->primary();
            $table->uuid('project_id')->references('project_id')->on('projects')->onDelete('cascade');
            $table->uuid('uploader_id')->references('user')->on('users')->onDelete('cascade');
            $table->string('nama_lampiran');
            $table->string('path_lampiran');
            $table->string('tipe_lampiran');
            $table->string('size_lampiran');
            $table->string('tag')->nullable();
            $table->string('section')->nullable();
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
        Schema::dropIfExists('project_lampirans');
    }
};
