<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            // kalau belum ada, tambahkan setelah tgl_secondary_pack_2
            if (!Schema::hasColumn('produksi_batches', 'qty_batch')) {
                $table->unsignedInteger('qty_batch')->nullable()
                      ->after('tgl_secondary_pack_2');
            }

            if (!Schema::hasColumn('produksi_batches', 'status_qty_batch')) {
                $table->string('status_qty_batch', 20)->nullable()
                      ->after('qty_batch'); // pending | confirmed | rejected
            }
        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            if (Schema::hasColumn('produksi_batches', 'qty_batch')) {
                $table->dropColumn('qty_batch');
            }
            if (Schema::hasColumn('produksi_batches', 'status_qty_batch')) {
                $table->dropColumn('status_qty_batch');
            }
        });
    }
};
