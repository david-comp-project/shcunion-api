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
        Schema::create('project_shares', function (Blueprint $table) {
            $table->uuid('project_share_id')->primary();
            $table->uuid('project_id')->references('project_id')->on('projects')->onDelete('cascaded');
            $table->uuid('user_id')->nullable();
            $table->uuid('social_media_id')->reference('social_media_id')->on('social_medias')->onDelete('cascade');
            $table->string('url');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_shares');
    }
};
