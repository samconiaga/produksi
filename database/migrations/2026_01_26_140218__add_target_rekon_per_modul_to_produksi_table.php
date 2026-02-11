<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi', function (Blueprint $table) {

            // Kalau kamu masih punya kolom lama `target_rekon`, biarkan dulu (optional).
            // Kita tambah kolom per-modul.

            if (!Schema::hasColumn('produksi', 'target_rekon_weighing')) {
                $table->unsignedInteger('target_rekon_weighing')->nullable()->after('est_qty');
            }

            if (!Schema::hasColumn('produksi', 'target_rekon_mixing')) {
                $table->unsignedInteger('target_rekon_mixing')->nullable()->after('target_rekon_weighing');
            }

            if (!Schema::hasColumn('produksi', 'target_rekon_tableting')) {
                $table->unsignedInteger('target_rekon_tableting')->nullable()->after('target_rekon_mixing');
            }

            if (!Schema::hasColumn('produksi', 'target_rekon_capsule_filling')) {
                $table->unsignedInteger('target_rekon_capsule_filling')->nullable()->after('target_rekon_tableting');
            }

            if (!Schema::hasColumn('produksi', 'target_rekon_coating')) {
                $table->unsignedInteger('target_rekon_coating')->nullable()->after('target_rekon_capsule_filling');
            }

            if (!Schema::hasColumn('produksi', 'target_rekon_primary_pack')) {
                $table->unsignedInteger('target_rekon_primary_pack')->nullable()->after('target_rekon_coating');
            }

            if (!Schema::hasColumn('produksi', 'target_rekon_secondary_pack')) {
                $table->unsignedInteger('target_rekon_secondary_pack')->nullable()->after('target_rekon_primary_pack');
            }
        });
    }

    public function down(): void
    {
        Schema::table('produksi', function (Blueprint $table) {
            $cols = [
                'target_rekon_secondary_pack',
                'target_rekon_primary_pack',
                'target_rekon_coating',
                'target_rekon_capsule_filling',
                'target_rekon_tableting',
                'target_rekon_mixing',
                'target_rekon_weighing',
            ];

            foreach ($cols as $c) {
                if (Schema::hasColumn('produksi', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};