<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            // STOP analisa
            $table->date('tgl_selesai_analisa_ruahan')->nullable()->after('tgl_analisa_ruahan');

            // detail ruahan saat STOP
            $table->date('ruahan_exp_date')->nullable()->after('tgl_rilis_ruahan');
            $table->decimal('ruahan_berat', 12, 3)->nullable()->after('ruahan_exp_date');
            $table->string('ruahan_no_wadah', 100)->nullable()->after('ruahan_berat');

            // ttd digital saat release
            $table->string('ruahan_sign_code')->nullable()->after('ruahan_no_wadah');
            $table->timestamp('ruahan_signed_at')->nullable()->after('ruahan_sign_code');
            $table->string('ruahan_signed_by')->nullable()->after('ruahan_signed_at');
            $table->string('ruahan_signed_level')->nullable()->after('ruahan_signed_by');
        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            $table->dropColumn([
                'tgl_selesai_analisa_ruahan',
                'ruahan_exp_date',
                'ruahan_berat',
                'ruahan_no_wadah',
                'ruahan_sign_code',
                'ruahan_signed_at',
                'ruahan_signed_by',
                'ruahan_signed_level',
            ]);
        });
    }
};
