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
        Schema::create('project_creator_informations', function (Blueprint $table) {
            $table->uuid('project_creator_information_id')->primary();
            $table->uuid('project_id')->reference('project_id')->on('projects')->onDeleted('cascade');
            $table->string('creator_name');
            $table->string('creator_email');
            $table->string('creator_phone');
            $table->string('creator_type');
            $table->string('creator_organization_name')->nullable();
            $table->string('creator_website')->nullable();
            $table->json('creator_social_media')->nullable();
            $table->string('creator_identifier')->nullable();
            $table->string('creator_file_path')->nullable();
            $table->string('creator_file_name')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_creator_informations');
    }
};
