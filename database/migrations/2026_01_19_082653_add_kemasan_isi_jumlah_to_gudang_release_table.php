<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gudang_release', function (Blueprint $table) {
            if (!Schema::hasColumn('gudang_release', 'kemasan')) {
                $table->string('kemasan', 100)->nullable()->after('produksi_batch_id');
            }
            if (!Schema::hasColumn('gudang_release', 'isi')) {
                $table->string('isi', 255)->nullable()->after('kemasan');
            }
            if (!Schema::hasColumn('gudang_release', 'jumlah_release')) {
                $table->unsignedInteger('jumlah_release')->nullable()->after('isi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gudang_release', function (Blueprint $table) {
            if (Schema::hasColumn('gudang_release', 'jumlah_release')) $table->dropColumn('jumlah_release');
            if (Schema::hasColumn('gudang_release', 'isi')) $table->dropColumn('isi');
            if (Schema::hasColumn('gudang_release', 'kemasan')) $table->dropColumn('kemasan');
        });
    }
};
