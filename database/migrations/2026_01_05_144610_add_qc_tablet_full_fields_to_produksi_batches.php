<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {

            // STOP analisa (auto today)
            if (!Schema::hasColumn('produksi_batches', 'tgl_selesai_analisa_tablet')) {
                $table->date('tgl_selesai_analisa_tablet')->nullable()->after('tgl_analisa_tablet');
            }

            // Detail tablet saat STOP (disimpan tapi belum release)
            if (!Schema::hasColumn('produksi_batches', 'tablet_exp_date')) {
                $table->date('tablet_exp_date')->nullable()->after('tgl_rilis_tablet');
            }
            if (!Schema::hasColumn('produksi_batches', 'tablet_berat')) {
                $table->decimal('tablet_berat', 12, 3)->nullable()->after('tablet_exp_date');
            }
            if (!Schema::hasColumn('produksi_batches', 'tablet_no_wadah')) {
                $table->string('tablet_no_wadah', 100)->nullable()->after('tablet_berat');
            }

            // TTD digital saat release
            if (!Schema::hasColumn('produksi_batches', 'tablet_sign_code')) {
                $table->string('tablet_sign_code', 255)->nullable()->after('tablet_no_wadah');
            }
            if (!Schema::hasColumn('produksi_batches', 'tablet_signed_at')) {
                $table->timestamp('tablet_signed_at')->nullable()->after('tablet_sign_code');
            }
            if (!Schema::hasColumn('produksi_batches', 'tablet_signed_by')) {
                $table->string('tablet_signed_by', 255)->nullable()->after('tablet_signed_at');
            }
            if (!Schema::hasColumn('produksi_batches', 'tablet_signed_level')) {
                $table->string('tablet_signed_level', 255)->nullable()->after('tablet_signed_by');
            }

        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {

            // drop hanya kolom yang ada (biar aman)
            $cols = [
                'tgl_selesai_analisa_tablet',
                'tablet_exp_date',
                'tablet_berat',
                'tablet_no_wadah',
                'tablet_sign_code',
                'tablet_signed_at',
                'tablet_signed_by',
                'tablet_signed_level',
            ];

            foreach ($cols as $c) {
                if (Schema::hasColumn('produksi_batches', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
