<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpvFieldsToGudangReleaseTable extends Migration
{
    public function up()
    {
        Schema::table('gudang_releases', function (Blueprint $table) {
            if (!Schema::hasColumn('gudang_releases', 'spv_doc_id')) {
                $table->unsignedBigInteger('spv_doc_id')->nullable()->after('goj_doc_id')->index();
            }
            if (!Schema::hasColumn('gudang_releases', 'spv_status')) {
                $table->string('spv_status', 32)->nullable()->after('goj_status')->index(); // PENDING/APPROVED/REJECTED
            }
            if (!Schema::hasColumn('gudang_releases', 'spv_note')) {
                $table->text('spv_note')->nullable()->after('goj_note');
            }
            if (!Schema::hasColumn('gudang_releases', 'spv_approved_by')) {
                $table->unsignedBigInteger('spv_approved_by')->nullable()->after('spv_status');
            }
            if (!Schema::hasColumn('gudang_releases', 'spv_approved_at')) {
                $table->timestamp('spv_approved_at')->nullable()->after('spv_approved_by');
            }
            if (!Schema::hasColumn('gudang_releases', 'spv_rejected_by')) {
                $table->unsignedBigInteger('spv_rejected_by')->nullable()->after('spv_approved_at');
            }
            if (!Schema::hasColumn('gudang_releases', 'spv_rejected_at')) {
                $table->timestamp('spv_rejected_at')->nullable()->after('spv_rejected_by');
            }
        });
    }

    public function down()
    {
        Schema::table('gudang_releases', function (Blueprint $table) {
            if (Schema::hasColumn('gudang_releases', 'spv_doc_id')) $table->dropColumn('spv_doc_id');
            if (Schema::hasColumn('gudang_releases', 'spv_status')) $table->dropColumn('spv_status');
            if (Schema::hasColumn('gudang_releases', 'spv_note')) $table->dropColumn('spv_note');
            if (Schema::hasColumn('gudang_releases', 'spv_approved_by')) $table->dropColumn('spv_approved_by');
            if (Schema::hasColumn('gudang_releases', 'spv_approved_at')) $table->dropColumn('spv_approved_at');
            if (Schema::hasColumn('gudang_releases', 'spv_rejected_by')) $table->dropColumn('spv_rejected_by');
            if (Schema::hasColumn('gudang_releases', 'spv_rejected_at')) $table->dropColumn('spv_rejected_at');
        });
    }
}
