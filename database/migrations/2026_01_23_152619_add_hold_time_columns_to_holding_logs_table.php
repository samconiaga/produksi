<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holding_logs', function (Blueprint $table) {

            // kolom relasi batch
            if (!Schema::hasColumn('holding_logs', 'produksi_batch_id')) {
                $table->unsignedBigInteger('produksi_batch_id')->nullable()->after('id');
                $table->index('produksi_batch_id');
            }

            // nomor hold ke berapa
            if (!Schema::hasColumn('holding_logs', 'hold_no')) {
                $table->unsignedInteger('hold_no')->default(1)->after('produksi_batch_id');
            }

            // info hold
            if (!Schema::hasColumn('holding_logs', 'holding_stage')) {
                $table->string('holding_stage', 50)->nullable()->after('hold_no');
            }

            if (!Schema::hasColumn('holding_logs', 'holding_reason')) {
                $table->string('holding_reason', 191)->nullable()->after('holding_stage');
            }

            if (!Schema::hasColumn('holding_logs', 'holding_note')) {
                $table->text('holding_note')->nullable()->after('holding_reason');
            }

            /**
             * Kolom waktu/status
             * (kalau sudah ada, akan di-skip)
             */
            if (!Schema::hasColumn('holding_logs', 'held_at')) {
                $table->dateTime('held_at')->nullable()->after('holding_note');
            }

            if (!Schema::hasColumn('holding_logs', 'held_by')) {
                $table->unsignedBigInteger('held_by')->nullable()->after('held_at');
            }

            if (!Schema::hasColumn('holding_logs', 'outcome')) {
                $table->string('outcome', 20)->nullable()->after('held_by'); // RELEASE/REJECT/OPEN
            }

            if (!Schema::hasColumn('holding_logs', 'return_to')) {
                $table->string('return_to', 50)->nullable()->after('outcome');
            }

            if (!Schema::hasColumn('holding_logs', 'resolve_reason')) {
                $table->string('resolve_reason', 191)->nullable()->after('return_to');
            }

            if (!Schema::hasColumn('holding_logs', 'resolve_note')) {
                $table->text('resolve_note')->nullable()->after('resolve_reason');
            }

            if (!Schema::hasColumn('holding_logs', 'resolved_at')) {
                $table->dateTime('resolved_at')->nullable()->after('resolve_note');
            }

            if (!Schema::hasColumn('holding_logs', 'resolved_by')) {
                $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved_at');
            }

            if (!Schema::hasColumn('holding_logs', 'duration_seconds')) {
                $table->unsignedInteger('duration_seconds')->default(0)->after('resolved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('holding_logs', function (Blueprint $table) {
            // aman: drop kalau ada
            if (Schema::hasColumn('holding_logs', 'duration_seconds')) $table->dropColumn('duration_seconds');
            if (Schema::hasColumn('holding_logs', 'resolved_by')) $table->dropColumn('resolved_by');
            if (Schema::hasColumn('holding_logs', 'resolved_at')) $table->dropColumn('resolved_at');
            if (Schema::hasColumn('holding_logs', 'resolve_note')) $table->dropColumn('resolve_note');
            if (Schema::hasColumn('holding_logs', 'resolve_reason')) $table->dropColumn('resolve_reason');
            if (Schema::hasColumn('holding_logs', 'return_to')) $table->dropColumn('return_to');
            if (Schema::hasColumn('holding_logs', 'outcome')) $table->dropColumn('outcome');
            if (Schema::hasColumn('holding_logs', 'held_by')) $table->dropColumn('held_by');
            if (Schema::hasColumn('holding_logs', 'held_at')) $table->dropColumn('held_at');

            if (Schema::hasColumn('holding_logs', 'holding_note')) $table->dropColumn('holding_note');
            if (Schema::hasColumn('holding_logs', 'holding_reason')) $table->dropColumn('holding_reason');
            if (Schema::hasColumn('holding_logs', 'holding_stage')) $table->dropColumn('holding_stage');
            if (Schema::hasColumn('holding_logs', 'hold_no')) $table->dropColumn('hold_no');

            if (Schema::hasColumn('holding_logs', 'produksi_batch_id')) {
                $table->dropIndex(['produksi_batch_id']);
                $table->dropColumn('produksi_batch_id');
            }
        });
    }
};
