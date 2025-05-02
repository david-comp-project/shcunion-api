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
        Schema::create('agendas', function (Blueprint $table) {
            $table->uuid('agenda_id')->primary();
            $table->uuid('user_id')->reference('user_id')->on('users')->onDelete('cascade');
            $table->date('tanggal_agenda');
            $table->string('waktu_agenda')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->string('category');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
