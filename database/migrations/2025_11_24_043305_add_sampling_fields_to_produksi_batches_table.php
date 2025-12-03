<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {

            // Pastikan kolom tgl_sampling belum ada sebelum membuat
            if (!Schema::hasColumn('produksi_batches', 'tgl_sampling')) {
                $table->date('tgl_sampling')
                      ->nullable()
                      ->after('status_jobsheet');
            }

            // Kalau kolom status_sampling BELUM ada -> buat baru
            if (!Schema::hasColumn('produksi_batches', 'status_sampling')) {
                $table->enum('status_sampling', ['pending', 'accepted', 'rejected', 'confirmed'])
                      ->default('pending')
                      ->after('tgl_sampling');
            }
        });

        // Kalau kolom status_sampling SUDAH ada, ubah set ENUM-nya via raw SQL
        if (Schema::hasColumn('produksi_batches', 'status_sampling')) {
            DB::statement("
                ALTER TABLE `produksi_batches`
                MODIFY `status_sampling`
                ENUM('pending', 'accepted', 'rejected', 'confirmed')
                DEFAULT 'pending'
            ");
        }
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {

            if (Schema::hasColumn('produksi_batches', 'status_sampling')) {
                $table->dropColumn('status_sampling');
            }

            if (Schema::hasColumn('produksi_batches', 'tgl_sampling')) {
                $table->dropColumn('tgl_sampling');
            }
        });
    }
};
