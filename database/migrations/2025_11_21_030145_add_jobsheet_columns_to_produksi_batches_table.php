<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {

            // Tanggal otomatis saat Qty Batch dikonfirmasi
            if (!Schema::hasColumn('produksi_batches', 'tgl_konfirmasi_produksi')) {
                $table->date('tgl_konfirmasi_produksi')
                      ->nullable()
                      ->after('status_qty_batch');
            }

            // Tanggal terima jobsheet (manual input)
            if (!Schema::hasColumn('produksi_batches', 'tgl_terima_jobsheet')) {
                $table->date('tgl_terima_jobsheet')
                      ->nullable()
                      ->after('tgl_konfirmasi_produksi');
            }

            // Status proses Job Sheet
            if (!Schema::hasColumn('produksi_batches', 'status_jobsheet')) {
                $table->enum('status_jobsheet', ['pending', 'done'])
                      ->default('pending')
                      ->after('tgl_terima_jobsheet');
            }

            // Catatan dari QC Produksi untuk Job Sheet
            if (!Schema::hasColumn('produksi_batches', 'catatan_jobsheet')) {
                $table->text('catatan_jobsheet')
                      ->nullable()
                      ->after('status_jobsheet');
            }
        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {

            if (Schema::hasColumn('produksi_batches', 'catatan_jobsheet')) {
                $table->dropColumn('catatan_jobsheet');
            }
            if (Schema::hasColumn('produksi_batches', 'status_jobsheet')) {
                $table->dropColumn('status_jobsheet');
            }
            if (Schema::hasColumn('produksi_batches', 'tgl_terima_jobsheet')) {
                $table->dropColumn('tgl_terima_jobsheet');
            }
            if (Schema::hasColumn('produksi_batches', 'tgl_konfirmasi_produksi')) {
                $table->dropColumn('tgl_konfirmasi_produksi');
            }
        });
    }
};
