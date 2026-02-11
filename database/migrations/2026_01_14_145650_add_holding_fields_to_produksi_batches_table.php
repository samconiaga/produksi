<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            // flag holding
            $table->boolean('is_holding')->default(false)->index()->after('status_proses');

            // stage asal + tujuan balik
            $table->string('holding_stage', 50)->nullable()->index()->after('is_holding');
            $table->string('holding_return_to', 50)->nullable()->index()->after('holding_stage');

            // alasan & catatan
            $table->string('holding_reason', 191)->nullable()->after('holding_return_to');
            $table->text('holding_note')->nullable()->after('holding_reason');

            // simpan status sebelum di-hold
            $table->string('holding_prev_status', 50)->nullable()->after('holding_note');

            // audit
            $table->timestamp('holding_at')->nullable()->after('holding_prev_status');
            $table->unsignedBigInteger('holding_by')->nullable()->after('holding_at');

            // OPTIONAL FK (aktifkan kalau tabel users kamu pakai bigint id)
            // $table->foreign('holding_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            // OPTIONAL: kalau FK diaktifkan, drop dulu
            // $table->dropForeign(['holding_by']);

            $table->dropColumn([
                'is_holding',
                'holding_stage',
                'holding_return_to',
                'holding_reason',
                'holding_note',
                'holding_prev_status',
                'holding_at',
                'holding_by',
            ]);
        });
    }
};
