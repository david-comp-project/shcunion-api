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
        Schema::create('withdrawal_donations', function (Blueprint $table) {
            $table->uuid('withdrawal_donation_id')->primary();
            $table->uuid('project_id')->reference('project_id')->on('projects')->onDelete('cascade');
            $table->uuid('user_id')->reference('user_id')->on('users')->onDelete('cascade');
            $table->string('full_name');
            $table->string('email');
            $table->string('address')->nullable();
            $table->string('phone_number');
            $table->string('nama_penerima');
            $table->string('channel_bank');
            $table->string('nomor_rekening');
            $table->decimal('jumlah_penarikan');
            $table->string('scan_rekening');
            $table->string('bukti_transfer')->nullable();
            $table->string('status_penarikan');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_donations');
    }
};
