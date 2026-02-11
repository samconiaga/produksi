<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            // kalau kolom basic sudah ada, ini akan error.
            // jadi kita pakai schema guard sederhana dengan hasColumn check manual (di bawah)
        });

        // Guard per kolom biar aman walau sebagian sudah ada
        Schema::table('produksi_batches', function (Blueprint $table) {

            if (!Schema::hasColumn('produksi_batches', 'tgl_datang_ruahan_akhir')) {
                $table->date('tgl_datang_ruahan_akhir')->nullable()->after('tgl_rilis_ruahan');
            }
            if (!Schema::hasColumn('produksi_batches', 'tgl_analisa_ruahan_akhir')) {
                $table->date('tgl_analisa_ruahan_akhir')->nullable()->after('tgl_datang_ruahan_akhir');
            }
            if (!Schema::hasColumn('produksi_batches', 'tgl_selesai_analisa_ruahan_akhir')) {
                $table->date('tgl_selesai_analisa_ruahan_akhir')->nullable()->after('tgl_analisa_ruahan_akhir');
            }
            if (!Schema::hasColumn('produksi_batches', 'tgl_rilis_ruahan_akhir')) {
                $table->date('tgl_rilis_ruahan_akhir')->nullable()->after('tgl_selesai_analisa_ruahan_akhir');
            }

            // FULL fields seperti granul/tablet
            if (!Schema::hasColumn('produksi_batches', 'ruahan_akhir_exp_date')) {
                $table->date('ruahan_akhir_exp_date')->nullable()->after('tgl_rilis_ruahan_akhir');
            }
            if (!Schema::hasColumn('produksi_batches', 'ruahan_akhir_berat')) {
                $table->decimal('ruahan_akhir_berat', 12, 3)->nullable()->after('ruahan_akhir_exp_date');
            }
            if (!Schema::hasColumn('produksi_batches', 'ruahan_akhir_no_wadah')) {
                $table->string('ruahan_akhir_no_wadah', 100)->nullable()->after('ruahan_akhir_berat');
            }

            // TTD digital
            if (!Schema::hasColumn('produksi_batches', 'ruahan_akhir_sign_code')) {
                $table->string('ruahan_akhir_sign_code', 191)->nullable()->after('ruahan_akhir_no_wadah');
            }
            if (!Schema::hasColumn('produksi_batches', 'ruahan_akhir_signed_at')) {
                $table->dateTime('ruahan_akhir_signed_at')->nullable()->after('ruahan_akhir_sign_code');
            }
            if (!Schema::hasColumn('produksi_batches', 'ruahan_akhir_signed_by')) {
                $table->unsignedBigInteger('ruahan_akhir_signed_by')->nullable()->after('ruahan_akhir_signed_at');
            }
            if (!Schema::hasColumn('produksi_batches', 'ruahan_akhir_signed_level')) {
                $table->string('ruahan_akhir_signed_level', 50)->nullable()->after('ruahan_akhir_signed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            $cols = [
                'tgl_datang_ruahan_akhir',
                'tgl_analisa_ruahan_akhir',
                'tgl_selesai_analisa_ruahan_akhir',
                'tgl_rilis_ruahan_akhir',
                'ruahan_akhir_exp_date',
                'ruahan_akhir_berat',
                'ruahan_akhir_no_wadah',
                'ruahan_akhir_sign_code',
                'ruahan_akhir_signed_at',
                'ruahan_akhir_signed_by',
                'ruahan_akhir_signed_level',
            ];

            foreach ($cols as $c) {
                if (Schema::hasColumn('produksi_batches', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
