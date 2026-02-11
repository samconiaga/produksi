<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            /* =========================
             * TABLETING (after tgl_tableting)
             * ========================= */
            if (!Schema::hasColumn('produksi_batches', 'tableting_rekon_qty')) {
                $table->unsignedInteger('tableting_rekon_qty')->nullable()->after('tgl_tableting');
            }
            if (!Schema::hasColumn('produksi_batches', 'tableting_rekon_note')) {
                $table->string('tableting_rekon_note', 500)->nullable()->after('tableting_rekon_qty');
            }
            if (!Schema::hasColumn('produuksi_batches', 'tableting_rekon_at')) {
                // typo guard: kalau salah table name, jangan dipakai
            }
            if (!Schema::hasColumn('produksi_batches', 'tableting_rekon_at')) {
                $table->timestamp('tableting_rekon_at')->nullable()->after('tableting_rekon_note');
            }
            if (!Schema::hasColumn('produksi_batches', 'tableting_rekon_by')) {
                $table->unsignedBigInteger('tableting_rekon_by')->nullable()->after('tableting_rekon_at');
            }

            /* =========================
             * CAPSULE FILLING (after tgl_capsule_filling)
             * ========================= */
            if (!Schema::hasColumn('produksi_batches', 'capsule_filling_rekon_qty')) {
                $table->unsignedInteger('capsule_filling_rekon_qty')->nullable()->after('tgl_capsule_filling');
            }
            if (!Schema::hasColumn('produksi_batches', 'capsule_filling_rekon_note')) {
                $table->string('capsule_filling_rekon_note', 500)->nullable()->after('capsule_filling_rekon_qty');
            }
            if (!Schema::hasColumn('produksi_batches', 'capsule_filling_rekon_at')) {
                $table->timestamp('capsule_filling_rekon_at')->nullable()->after('capsule_filling_rekon_note');
            }
            if (!Schema::hasColumn('produksi_batches', 'capsule_filling_rekon_by')) {
                $table->unsignedBigInteger('capsule_filling_rekon_by')->nullable()->after('capsule_filling_rekon_at');
            }

            /* =========================
             * COATING (after tgl_coating)
             * ========================= */
            if (!Schema::hasColumn('produksi_batches', 'coating_rekon_qty')) {
                $table->unsignedInteger('coating_rekon_qty')->nullable()->after('tgl_coating');
            }
            if (!Schema::hasColumn('produksi_batches', 'coating_rekon_note')) {
                $table->string('coating_rekon_note', 500)->nullable()->after('coating_rekon_qty');
            }
            if (!Schema::hasColumn('produksi_batches', 'coating_rekon_at')) {
                $table->timestamp('coating_rekon_at')->nullable()->after('coating_rekon_note');
            }
            if (!Schema::hasColumn('produksi_batches', 'coating_rekon_by')) {
                $table->unsignedBigInteger('coating_rekon_by')->nullable()->after('coating_rekon_at');
            }

            /* =========================
             * PRIMARY PACK (after tgl_primary_pack)
             * ========================= */
            if (!Schema::hasColumn('produksi_batches', 'primary_pack_rekon_qty')) {
                $table->unsignedInteger('primary_pack_rekon_qty')->nullable()->after('tgl_primary_pack');
            }
            if (!Schema::hasColumn('produksi_batches', 'primary_pack_rekon_note')) {
                $table->string('primary_pack_rekon_note', 500)->nullable()->after('primary_pack_rekon_qty');
            }
            if (!Schema::hasColumn('produksi_batches', 'primary_pack_rekon_at')) {
                $table->timestamp('primary_pack_rekon_at')->nullable()->after('primary_pack_rekon_note');
            }
            if (!Schema::hasColumn('produksi_batches', 'primary_pack_rekon_by')) {
                $table->unsignedBigInteger('primary_pack_rekon_by')->nullable()->after('primary_pack_rekon_at');
            }

            /* =========================
             * SECONDARY PACK 1 (after tgl_secondary_pack_1)
             * ========================= */
            if (!Schema::hasColumn('produksi_batches', 'secondary_pack_1_rekon_qty')) {
                $table->unsignedInteger('secondary_pack_1_rekon_qty')->nullable()->after('tgl_secondary_pack_1');
            }
            if (!Schema::hasColumn('produksi_batches', 'secondary_pack_1_rekon_note')) {
                $table->string('secondary_pack_1_rekon_note', 500)->nullable()->after('secondary_pack_1_rekon_qty');
            }
            if (!Schema::hasColumn('produksi_batches', 'secondary_pack_1_rekon_at')) {
                $table->timestamp('secondary_pack_1_rekon_at')->nullable()->after('secondary_pack_1_rekon_note');
            }
            if (!Schema::hasColumn('produksi_batches', 'secondary_pack_1_rekon_by')) {
                $table->unsignedBigInteger('secondary_pack_1_rekon_by')->nullable()->after('secondary_pack_1_rekon_at');
            }

            /* =========================
             * SECONDARY PACK 2 (after tgl_secondary_pack_2)
             * ========================= */
            if (!Schema::hasColumn('produksi_batches', 'secondary_pack_2_rekon_qty')) {
                $table->unsignedInteger('secondary_pack_2_rekon_qty')->nullable()->after('tgl_secondary_pack_2');
            }
            if (!Schema::hasColumn('produksi_batches', 'secondary_pack_2_rekon_note')) {
                $table->string('secondary_pack_2_rekon_note', 500)->nullable()->after('secondary_pack_2_rekon_qty');
            }
            if (!Schema::hasColumn('produksi_batches', 'secondary_pack_2_rekon_at')) {
                $table->timestamp('secondary_pack_2_rekon_at')->nullable()->after('secondary_pack_2_rekon_note');
            }
            if (!Schema::hasColumn('produksi_batches', 'secondary_pack_2_rekon_by')) {
                $table->unsignedBigInteger('secondary_pack_2_rekon_by')->nullable()->after('secondary_pack_2_rekon_at');
            }

            /* =========================
             * OPTIONAL: Rekap secondary pack final (kalau mau 1 angka final)
             * Letakkan setelah secondary_pack_2_rekon_by (kalau ada),
             * kalau tidak ada, tetap akan dibuat di bagian akhir tabel.
             * ========================= */
            if (!Schema::hasColumn('produksi_batches', 'secondary_pack_rekon_qty')) {
                $table->unsignedInteger('secondary_pack_rekon_qty')->nullable();
            }
            if (!Schema::hasColumn('produksi_batches', 'secondary_pack_rekon_note')) {
                $table->string('secondary_pack_rekon_note', 500)->nullable()->after('secondary_pack_rekon_qty');
            }
            if (!Schema::hasColumn('produksi_batches', 'secondary_pack_rekon_at')) {
                $table->timestamp('secondary_pack_rekon_at')->nullable()->after('secondary_pack_rekon_note');
            }
            if (!Schema::hasColumn('produksi_batches', 'secondary_pack_rekon_by')) {
                $table->unsignedBigInteger('secondary_pack_rekon_by')->nullable()->after('secondary_pack_rekon_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {

            // OPTIONAL secondary final
            if (Schema::hasColumn('produksi_batches', 'secondary_pack_rekon_by'))   $table->dropColumn('secondary_pack_rekon_by');
            if (Schema::hasColumn('produksi_batches', 'secondary_pack_rekon_at'))   $table->dropColumn('secondary_pack_rekon_at');
            if (Schema::hasColumn('produksi_batches', 'secondary_pack_rekon_note')) $table->dropColumn('secondary_pack_rekon_note');
            if (Schema::hasColumn('produksi_batches', 'secondary_pack_rekon_qty'))  $table->dropColumn('secondary_pack_rekon_qty');

            // Secondary pack 2
            if (Schema::hasColumn('produksi_batches', 'secondary_pack_2_rekon_by'))   $table->dropColumn('secondary_pack_2_rekon_by');
            if (Schema::hasColumn('produksi_batches', 'secondary_pack_2_rekon_at'))   $table->dropColumn('secondary_pack_2_rekon_at');
            if (Schema::hasColumn('produksi_batches', 'secondary_pack_2_rekon_note')) $table->dropColumn('secondary_pack_2_rekon_note');
            if (Schema::hasColumn('produksi_batches', 'secondary_pack_2_rekon_qty'))  $table->dropColumn('secondary_pack_2_rekon_qty');

            // Secondary pack 1
            if (Schema::hasColumn('produksi_batches', 'secondary_pack_1_rekon_by'))   $table->dropColumn('secondary_pack_1_rekon_by');
            if (Schema::hasColumn('produksi_batches', 'secondary_pack_1_rekon_at'))   $table->dropColumn('secondary_pack_1_rekon_at');
            if (Schema::hasColumn('produksi_batches', 'secondary_pack_1_rekon_note')) $table->dropColumn('secondary_pack_1_rekon_note');
            if (Schema::hasColumn('produksi_batches', 'secondary_pack_1_rekon_qty'))  $table->dropColumn('secondary_pack_1_rekon_qty');

            // Primary Pack
            if (Schema::hasColumn('produksi_batches', 'primary_pack_rekon_by'))   $table->dropColumn('primary_pack_rekon_by');
            if (Schema::hasColumn('produksi_batches', 'primary_pack_rekon_at'))   $table->dropColumn('primary_pack_rekon_at');
            if (Schema::hasColumn('produksi_batches', 'primary_pack_rekon_note')) $table->dropColumn('primary_pack_rekon_note');
            if (Schema::hasColumn('produksi_batches', 'primary_pack_rekon_qty'))  $table->dropColumn('primary_pack_rekon_qty');

            // Coating
            if (Schema::hasColumn('produksi_batches', 'coating_rekon_by'))   $table->dropColumn('coating_rekon_by');
            if (Schema::hasColumn('produksi_batches', 'coating_rekon_at'))   $table->dropColumn('coating_rekon_at');
            if (Schema::hasColumn('produksi_batches', 'coating_rekon_note')) $table->dropColumn('coating_rekon_note');
            if (Schema::hasColumn('produksi_batches', 'coating_rekon_qty'))  $table->dropColumn('coating_rekon_qty');

            // Capsule Filling
            if (Schema::hasColumn('produksi_batches', 'capsule_filling_rekon_by'))   $table->dropColumn('capsule_filling_rekon_by');
            if (Schema::hasColumn('produksi_batches', 'capsule_filling_rekon_at'))   $table->dropColumn('capsule_filling_rekon_at');
            if (Schema::hasColumn('produksi_batches', 'capsule_filling_rekon_note')) $table->dropColumn('capsule_filling_rekon_note');
            if (Schema::hasColumn('produksi_batches', 'capsule_filling_rekon_qty'))  $table->dropColumn('capsule_filling_rekon_qty');

            // Tableting
            if (Schema::hasColumn('produksi_batches', 'tableting_rekon_by'))   $table->dropColumn('tableting_rekon_by');
            if (Schema::hasColumn('produksi_batches', 'tableting_rekon_at'))   $table->dropColumn('tableting_rekon_at');
            if (Schema::hasColumn('produksi_batches', 'tableting_rekon_note')) $table->dropColumn('tableting_rekon_note');
            if (Schema::hasColumn('produksi_batches', 'tableting_rekon_qty'))  $table->dropColumn('tableting_rekon_qty');

            // Weighing
            if (Schema::hasColumn('produksi_batches', 'weighing_rekon_by'))   $table->dropColumn('weighing_rekon_by');
            if (Schema::hasColumn('produksi_batches', 'weighing_rekon_at'))   $table->dropColumn('weighing_rekon_at');
            if (Schema::hasColumn('produksi_batches', 'weighing_rekon_note')) $table->dropColumn('weighing_rekon_note');
            if (Schema::hasColumn('produksi_batches', 'weighing_rekon_qty'))  $table->dropColumn('weighing_rekon_qty');
        });
    }
};