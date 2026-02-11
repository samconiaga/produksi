<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {

            if (!Schema::hasColumn('produksi_batches', 'mixing_rekon_qty')) {
                $table->unsignedInteger('mixing_rekon_qty')->nullable()->after('tgl_mixing');
            }

            if (!Schema::hasColumn('produksi_batches', 'mixing_rekon_note')) {
                $table->string('mixing_rekon_note', 500)->nullable()->after('mixing_rekon_qty');
            }

            if (!Schema::hasColumn('produksi_batches', 'mixing_rekon_at')) {
                $table->timestamp('mixing_rekon_at')->nullable()->after('mixing_rekon_note');
            }

            if (!Schema::hasColumn('produksi_batches', 'mixing_rekon_by')) {
                $table->unsignedBigInteger('mixing_rekon_by')->nullable()->after('mixing_rekon_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            if (Schema::hasColumn('produksi_batches', 'mixing_rekon_by')) $table->dropColumn('mixing_rekon_by');
            if (Schema::hasColumn('produksi_batches', 'mixing_rekon_at')) $table->dropColumn('mixing_rekon_at');
            if (Schema::hasColumn('produksi_batches', 'mixing_rekon_note')) $table->dropColumn('mixing_rekon_note');
            if (Schema::hasColumn('produksi_batches', 'mixing_rekon_qty')) $table->dropColumn('mixing_rekon_qty');
        });
    }
};