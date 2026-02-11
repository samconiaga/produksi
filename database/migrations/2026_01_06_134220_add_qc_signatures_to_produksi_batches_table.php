<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            $steps = ['granul', 'tablet', 'ruahan', 'ruahan_akhir'];

            foreach ($steps as $s) {
                if (!Schema::hasColumn('produksi_batches', "{$s}_sign_code")) {
                    $table->string("{$s}_sign_code")->nullable();
                }
                if (!Schema::hasColumn('produksi_batches', "{$s}_signed_at")) {
                    $table->timestamp("{$s}_signed_at")->nullable();
                }
                if (!Schema::hasColumn('produksi_batches', "{$s}_signed_by")) {
                    $table->string("{$s}_signed_by")->nullable();
                }
                if (!Schema::hasColumn('produksi_batches', "{$s}_signed_level")) {
                    $table->string("{$s}_signed_level")->nullable();
                }
                if (!Schema::hasColumn('produksi_batches', "{$s}_sign_url")) {
                    $table->text("{$s}_sign_url")->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            $cols = [];
            foreach (['granul','tablet','ruahan','ruahan_akhir'] as $s) {
                $cols[] = "{$s}_sign_code";
                $cols[] = "{$s}_signed_at";
                $cols[] = "{$s}_signed_by";
                $cols[] = "{$s}_signed_level";
                $cols[] = "{$s}_sign_url";
            }
            $table->dropColumn($cols);
        });
    }
};
