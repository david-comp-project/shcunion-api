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
        Schema::create('volunteer_involvements', function (Blueprint $table) {
            $table->uuid('volunteer_involvement_id')->primary();
            $table->uuid('project_id')->references('project_id')->on('projects')->onDelete('cascade');
            $table->uuid('volunteer_id')->references('user_id')->on('users')->onDelete('cascade')->nullable();
            //For new user
            $table->string('email');
            $table->string('full_name');
            $table->string('address');
            $table->string('phone_number');
            //Criteria and Participation Requirement
            $table->json('criteria_checked');
            $table->decimal('volunteer_hours', 8, 2)->nullable();
            $table->date('involvement_start_date')->nullable();
            $table->date('involvement_end_date')->nullable();
            $table->string('involvement_start_time')->nullable(); //format HH:mm
            $table->string('involvement_end_time')->nullable(); //format HH:mm
            $table->string('role');
            $table->string('status');
            $table->text('note')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_involvements');
    }
};
