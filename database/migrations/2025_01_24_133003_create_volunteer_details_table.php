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
        Schema::create('volunteer_details', function (Blueprint $table) {
            $table->uuid('volunteer_detail_id')->primary();
            $table->uuid('project_id')->reference('project_id')->on('projects')->onDelete('cascade');
            $table->uuid('volunteer_id')->reference('user_id')->on('users')->onDelete('cascade');
            $table->string('volunteer_role');
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_details');
    }
};
