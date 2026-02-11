<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {

            // tanggal selesai analisa tablet (otomatis saat Stop)
            $table->date('tgl_selesai_analisa_tablet')->nullable()->after('tgl_analisa_tablet');

            // detail saat Stop (belum release)
            $table->date('tablet_exp_date')->nullable()->after('tgl_rilis_tablet');
            $table->decimal('tablet_berat', 12, 3)->nullable()->after('tablet_exp_date');
            $table->string('tablet_no_wadah', 100)->nullable()->after('tablet_berat');

            // ttd digital saat Release
            $table->string('tablet_sign_code')->nullable()->after('tablet_no_wadah');
            $table->timestamp('tablet_signed_at')->nullable()->after('tablet_sign_code');
            $table->string('tablet_signed_by')->nullable()->after('tablet_signed_at');
            $table->string('tablet_signed_level')->nullable()->after('tablet_signed_by');
        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            $table->dropColumn([
                'tgl_selesai_analisa_tablet',
                'tablet_exp_date',
                'tablet_berat',
                'tablet_no_wadah',
                'tablet_sign_code',
                'tablet_signed_at',
                'tablet_signed_by',
                'tablet_signed_level',
            ]);
        });
    }
};
