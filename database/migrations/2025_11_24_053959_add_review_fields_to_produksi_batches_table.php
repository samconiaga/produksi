<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {

            // status_review: pending | hold | released | rejected
            if (!Schema::hasColumn('produksi_batches', 'status_review')) {
                $table->string('status_review')
                      ->default('pending')
                      ->after('status_coa');
            }

            if (!Schema::hasColumn('produksi_batches', 'tgl_review')) {
                $table->date('tgl_review')
                      ->nullable()
                      ->after('status_review');
            }

            if (!Schema::hasColumn('produksi_batches', 'catatan_review')) {
                $table->text('catatan_review')
                      ->nullable()
                      ->after('tgl_review');
            }
        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {

            if (Schema::hasColumn('produksi_batches', 'catatan_review')) {
                $table->dropColumn('catatan_review');
            }
            if (Schema::hasColumn('produksi_batches', 'tgl_review')) {
                $table->dropColumn('tgl_review');
            }
            if (Schema::hasColumn('produksi_batches', 'status_review')) {
                $table->dropColumn('status_review');
            }
        });
    }
};
