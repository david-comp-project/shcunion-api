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
        Schema::create('donation_payments', function (Blueprint $table) {
            $table->uuid('donation_payment_id')->primary();
            $table->string('donation_code')->nullable();
            $table->uuid('project_id')->references('project_id')->on('projects')->onDelete('cascade');
            $table->uuid('donatur_id')->references('user_id')->on('users')->onDelete('cascade')->nullable();
            $table->string('email');
            $table->string('full_name');
            $table->string('address')->nullable();
            $table->string('phone_number')->nullable();
            $table->decimal('donation_amount', 15, 2);
            $table->string('channel_payment')->nullable();
            $table->string('channel_name')->nullable();
            $table->uuid('payment_method_id')->references('payment_method_id')->on('payment_methods')->onDelete('cascade')->nullable();
            $table->string('status')->nullable();
            $table->uuid('transaction_id')->nullable();
            $table->datetime('transaction_time')->nullable();
            $table->softDeletes();  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_payments');
    }
};
