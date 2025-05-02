<?php

use App\Enums\UserStatusEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            
            // Informasi dasar pengguna
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name')->nullable(); // Diambil dari user_details
        
            // Data kontak dan autentikasi
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
        
            // Data profil dan detail tambahan
            $table->string('jenis_kelamin')->nullable();
            $table->string('profile_picture')->nullable(); // Menggabungkan profile_picture dari kedua tabel
            $table->string('profile_cover')->nullable();
            $table->string('scan_ktp')->nullable();
            $table->string('address')->nullable();
            $table->json('social_media')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('nik')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('organization_name')->nullable('tidak ada');
            $table->string('jabatan')->nullable('tidak ada');
            $table->string('job')->nullable();
            // $table->string('badge')->default('pemula');
            $table->integer('total_points')->default(0);
            $table->string('status')->default(UserStatusEnum::ACTIVE);
            $table->boolean('user_verified')->default(false);
            $table->date('status_date')->nullable();
            $table->date('suspended_date')->nullable();
            $table->string('suspended_reason')->nullable();
            
        
            $table->timestamps();
            $table->softDeletes();
        });
        
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
