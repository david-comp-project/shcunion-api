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
        Schema::create('project_details', function (Blueprint $table) {
            $table->uuid('project_detail_id')->primary();
            $table->uuid('project_id')->references('project_id')->on('projects')->onDelete('cascade');
            $table->uuid('donatur_id')->reference('user_id')->on('users')->onDelete('cascade');;
            $table->float('donation_amount');
            $table->string('description');
            $table->string('channel_payment');
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_details');
    }
};
