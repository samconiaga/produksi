<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            // GRANUL
            if (!Schema::hasColumn('produksi_batches', 'granul_sign_code')) {
                $table->string('granul_sign_code')->nullable()->after('tgl_rilis_granul');
            }
            if (!Schema::hasColumn('produksi_batches', 'granul_signed_at')) {
                $table->timestamp('granul_signed_at')->nullable()->after('granul_sign_code');
            }
            if (!Schema::hasColumn('produksi_batches', 'granul_signed_by')) {
                $table->string('granul_signed_by')->nullable()->after('granul_signed_at');
            }
            if (!Schema::hasColumn('produksi_batches', 'granul_signed_level')) {
                $table->string('granul_signed_level')->nullable()->after('granul_signed_by');
            }

            // (Kalau nanti tablet/ruahan/akhir juga pakai pola sama, bisa tambah di sini juga)
        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            $cols = ['granul_sign_code','granul_signed_at','granul_signed_by','granul_signed_level'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('produksi_batches', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
