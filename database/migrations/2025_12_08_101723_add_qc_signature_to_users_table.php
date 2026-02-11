<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom khusus QC:
     * - qc_level          : MANAGER / SPV
     * - qc_signature_path : path gambar barcode/QR ttd digital
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // setelah role biar rapi
            $table->string('qc_level', 20)
                  ->nullable()
                  ->after('role');

            $table->string('qc_signature_path')
                  ->nullable()
                  ->after('qc_level');
        });
    }

    /**
     * Rollback perubahan.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['qc_level', 'qc_signature_path']);
        });
    }
};
