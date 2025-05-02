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
        Schema::create('user_details', function (Blueprint $table) {
            $table->uuid('user_detail_id')->primary();
            $table->uuid('user_id')->reference('user_id')->on('users')->onDelete('cascade');
            $table->string('full_name');
            $table->string('address');
            $table->json('social_media')->nullable();
            $table->string('phone_number');
            $table->string('nik');
            $table->string('profile_picture')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('job')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
