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
        Schema::create('project_beneficial_informations', function (Blueprint $table) {
            $table->uuid('project_beneficial_information_id')->primary();
            $table->uuid('project_id')->reference('project_id')->on('projects')->onDeleted('cascade');
            $table->string('beneficiary_type'); // Jenis penerima manfaat (individu/organisasi)
            $table->string('beneficiary_name')->nullable(); // Nama penerima manfaat
            $table->string('beneficiary_nik')->nullable(); // NIK penerima manfaat
            $table->text('beneficiary_address')->nullable(); // Alamat penerima manfaat
            $table->string('beneficiary_phone')->nullable(); // No. telepon penerima manfaat
            $table->text('beneficiary_needs')->nullable(); // Kebutuhan penerima manfaat
            $table->string('organization_name')->nullable(); // Nama organisasi
            $table->string('organization_reg_number')->nullable(); // No. registrasi organisasi
            $table->text('organization_address')->nullable(); // Alamat organisasi
            $table->string('organization_pic')->nullable(); // Nama PIC organisasi
            $table->string('organization_phone')->nullable(); // No. telepon organisasi
            $table->string('beneficiary_relation')->nullable(); // Hubungan penerima manfaat
            $table->string('beneficiary_relation_other')->nullable(); // Hubungan lainnya (jika ada)
            $table->string('beneficiary_file_path')->nullable(); // File terkait (misalnya dokumen verifikasi)
            $table->string('beneficiary_file_name')->nullable(); // File terkait (misalnya dokumen verifikasi)
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_beneficial_informations');
    }
};
