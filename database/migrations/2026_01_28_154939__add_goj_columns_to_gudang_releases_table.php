<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('gudang_releases', function (Blueprint $table) {
      if (!Schema::hasColumn('gudang_releases', 'goj_doc_id')) {
        $table->unsignedBigInteger('goj_doc_id')->nullable()->after('produksi_batch_id');
        $table->index('goj_doc_id');
      }

      if (!Schema::hasColumn('gudang_releases', 'goj_status')) {
        $table->enum('goj_status', ['PENDING','APPROVED','REJECTED'])->nullable()->after('status');
        $table->index('goj_status');
      }

      if (!Schema::hasColumn('gudang_releases', 'goj_note')) {
        $table->string('goj_note', 255)->nullable()->after('goj_status');
      }

      if (!Schema::hasColumn('gudang_releases', 'goj_approved_by')) {
        $table->unsignedBigInteger('goj_approved_by')->nullable()->after('goj_note');
      }

      if (!Schema::hasColumn('gudang_releases', 'goj_approved_at')) {
        $table->timestamp('goj_approved_at')->nullable()->after('goj_approved_by');
      }

      if (!Schema::hasColumn('gudang_releases', 'goj_rejected_by')) {
        $table->unsignedBigInteger('goj_rejected_by')->nullable()->after('goj_approved_at');
      }

      if (!Schema::hasColumn('gudang_releases', 'goj_rejected_at')) {
        $table->timestamp('goj_rejected_at')->nullable()->after('goj_rejected_by');
      }
    });
  }

  public function down(): void
  {
    Schema::table('gudang_releases', function (Blueprint $table) {
      foreach ([
        'goj_doc_id','goj_status','goj_note',
        'goj_approved_by','goj_approved_at',
        'goj_rejected_by','goj_rejected_at',
      ] as $col) {
        if (Schema::hasColumn('gudang_releases', $col)) {
          $table->dropColumn($col);
        }
      }
    });
  }
};