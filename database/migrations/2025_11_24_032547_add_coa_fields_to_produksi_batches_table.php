<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {

            // tanggal QC kirim COA ke QA
            if (!Schema::hasColumn('produksi_batches', 'tgl_qc_kirim_coa')) {
                $table->date('tgl_qc_kirim_coa')
                      ->nullable()
                      ->after('tgl_terima_jobsheet');
            }

            // tanggal QA terima COA
            if (!Schema::hasColumn('produksi_batches', 'tgl_qa_terima_coa')) {
                $table->date('tgl_qa_terima_coa')
                      ->nullable()
                      ->after('tgl_qc_kirim_coa');
            }

            // status COA: pending / done
            if (!Schema::hasColumn('produksi_batches', 'status_coa')) {
                $table->string('status_coa', 20)
                      ->default('pending')
                      ->after('tgl_qa_terima_coa');
            }
        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {

            if (Schema::hasColumn('produksi_batches', 'status_coa')) {
                $table->dropColumn('status_coa');
            }
            if (Schema::hasColumn('produksi_batches', 'tgl_qa_terima_coa')) {
                $table->dropColumn('tgl_qa_terima_coa');
            }
            if (Schema::hasColumn('produksi_batches', 'tgl_qc_kirim_coa')) {
                $table->dropColumn('tgl_qc_kirim_coa');
            }
        });
    }
};
