g<?php

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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->uuid('payment_method_id')->primary();
            $table->string('payment_code')->unique();
            $table->string('payment_type');
            $table->string('payment_name');
            $table->string('icon')->nullable();
            $table->decimal('fee', 10, 2);
            $table->string('payment_description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
