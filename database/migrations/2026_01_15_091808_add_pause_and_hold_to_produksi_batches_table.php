<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ✅ PAUSE
        if (!Schema::hasColumn('produksi_batches', 'is_paused')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->boolean('is_paused')->default(false)->after('status_proses');
            });
        }

        if (!Schema::hasColumn('produksi_batches', 'paused_stage')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->string('paused_stage', 50)->nullable()->after('is_paused');
            });
        }

        if (!Schema::hasColumn('produksi_batches', 'paused_reason')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->text('paused_reason')->nullable()->after('paused_stage');
            });
        }

        if (!Schema::hasColumn('produksi_batches', 'paused_note')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->text('paused_note')->nullable()->after('paused_reason');
            });
        }

        if (!Schema::hasColumn('produksi_batches', 'paused_prev_status')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->string('paused_prev_status', 50)->nullable()->after('paused_note');
            });
        }

        if (!Schema::hasColumn('produksi_batches', 'paused_at')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->dateTime('paused_at')->nullable()->after('paused_prev_status');
            });
        }

        if (!Schema::hasColumn('produksi_batches', 'paused_by')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->unsignedBigInteger('paused_by')->nullable()->after('paused_at');
            });
        }

        // ✅ HOLD
        if (!Schema::hasColumn('produksi_batches', 'is_holding')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->boolean('is_holding')->default(false)->after('paused_by');
            });
        }

        if (!Schema::hasColumn('produksi_batches', 'holding_stage')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->string('holding_stage', 50)->nullable()->after('is_holding');
            });
        }

        if (!Schema::hasColumn('produksi_batches', 'holding_return_to')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->string('holding_return_to', 50)->nullable()->after('holding_stage');
            });
        }

        if (!Schema::hasColumn('produksi_batches', 'holding_reason')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->text('holding_reason')->nullable()->after('holding_return_to');
            });
        }

        if (!Schema::hasColumn('produksi_batches', 'holding_note')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->text('holding_note')->nullable()->after('holding_reason');
            });
        }

        if (!Schema::hasColumn('produksi_batches', 'holding_prev_status')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->string('holding_prev_status', 50)->nullable()->after('holding_note');
            });
        }

        if (!Schema::hasColumn('produksi_batches', 'holding_at')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->dateTime('holding_at')->nullable()->after('holding_prev_status');
            });
        }

        if (!Schema::hasColumn('produksi_batches', 'holding_by')) {
            Schema::table('produksi_batches', function (Blueprint $table) {
                $table->unsignedBigInteger('holding_by')->nullable()->after('holding_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('produksi_batches', function (Blueprint $table) {
            $cols = [
                'is_paused','paused_stage','paused_reason','paused_note','paused_prev_status','paused_at','paused_by',
                'is_holding','holding_stage','holding_return_to','holding_reason','holding_note','holding_prev_status','holding_at','holding_by',
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('produksi_batches', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
