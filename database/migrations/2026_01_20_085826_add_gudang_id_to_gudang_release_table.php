<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ tabel kamu memang gudang_release (tanpa s)
        Schema::table('gudang_release', function (Blueprint $table) {
            if (!Schema::hasColumn('gudang_release', 'gudang_id')) {
                $table->unsignedBigInteger('gudang_id')->nullable()->after('qty_fisik');
                $table->index('gudang_id');

                $table->foreign('gudang_id')
                    ->references('id')
                    ->on('master_gudangs')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('gudang_release', function (Blueprint $table) {
            if (Schema::hasColumn('gudang_release', 'gudang_id')) {
                $table->dropForeign(['gudang_id']);
                $table->dropIndex(['gudang_id']);
                $table->dropColumn('gudang_id');
            }
        });
    }
};
