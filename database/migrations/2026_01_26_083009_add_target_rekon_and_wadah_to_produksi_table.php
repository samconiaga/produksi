<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi', function (Blueprint $table) {
            if (!Schema::hasColumn('produksi', 'target_rekon')) {
                $table->unsignedInteger('target_rekon')->nullable()->default(0)->after('est_qty');
            }

            if (!Schema::hasColumn('produksi', 'wadah')) {
                $table->string('wadah', 10)->nullable()->after('bentuk_sediaan'); 
                // contoh isi: Dus / Btl / Top
            }
        });
    }

    public function down(): void
    {
        Schema::table('produksi', function (Blueprint $table) {
            if (Schema::hasColumn('produksi', 'target_rekon')) $table->dropColumn('target_rekon');
            if (Schema::hasColumn('produksi', 'wadah')) $table->dropColumn('wadah');
        });
    }
};