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
        Schema::create('social_medias', function (Blueprint $table) {
            $table->uuid('social_media_id')->primary();
            $table->string('social_media_name', 50);       // Nama platform, misal: Facebook, Twitter
            $table->string('icon', 255);             // Nama icon atau path icon
            $table->string('background_color');   // Warna latar belakang, misal: "#3b5998"
            $table->string('hover_color');        // Warna saat hover, misal: "#2d4373"
            $table->string('label', 100);            // Label untuk tombol share, misal: "Share on Facebook"
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_medias');
    }
};
