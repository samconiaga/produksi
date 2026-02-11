<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            $table->unsignedBigInteger('granul_signed_user_id')->nullable()->after('granul_signed_level');
            $table->unsignedBigInteger('tablet_signed_user_id')->nullable()->after('tablet_signed_level');
            $table->unsignedBigInteger('ruahan_signed_user_id')->nullable()->after('ruahan_signed_level');
            $table->unsignedBigInteger('ruahan_akhir_signed_user_id')->nullable()->after('ruahan_akhir_signed_level');

            // optional FK (kalau mau aman)
            // $table->foreign('granul_signed_user_id')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('tablet_signed_user_id')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('ruahan_signed_user_id')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('ruahan_akhir_signed_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            // kalau pakai foreign key, drop dulu FK-nya
            // $table->dropForeign(['granul_signed_user_id']);
            // $table->dropForeign(['tablet_signed_user_id']);
            // $table->dropForeign(['ruahan_signed_user_id']);
            // $table->dropForeign(['ruahan_akhir_signed_user_id']);

            $table->dropColumn([
                'granul_signed_user_id',
                'tablet_signed_user_id',
                'ruahan_signed_user_id',
                'ruahan_akhir_signed_user_id',
            ]);
        });
    }
};
