<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('gudang_release', function (Blueprint $table) {
      if (!Schema::hasColumn('gudang_release', 'goj_doc_id')) {
        $table->unsignedBigInteger('goj_doc_id')->nullable()->after('id');
      }
      if (!Schema::hasColumn('gudang_release', 'goj_status')) {
        $table->string('goj_status', 20)->nullable()->after('goj_doc_id'); // PENDING/APPROVED/REJECTED
      }
      if (!Schema::hasColumn('gudang_release', 'goj_note')) {
        $table->string('goj_note', 255)->nullable()->after('goj_status');
      }
      if (!Schema::hasColumn('gudang_release', 'goj_approved_by')) {
        $table->unsignedBigInteger('goj_approved_by')->nullable()->after('goj_note');
      }
      if (!Schema::hasColumn('gudang_release', 'goj_approved_at')) {
        $table->timestamp('goj_approved_at')->nullable()->after('goj_approved_by');
      }
      if (!Schema::hasColumn('gudang_release', 'goj_rejected_by')) {
        $table->unsignedBigInteger('goj_rejected_by')->nullable()->after('goj_approved_at');
      }
      if (!Schema::hasColumn('gudang_release', 'goj_rejected_at')) {
        $table->timestamp('goj_rejected_at')->nullable()->after('goj_rejected_by');
      }

      // optional index biar cepat
      $table->index(['goj_status', 'goj_doc_id']);
    });
  }

  public function down(): void
  {
    Schema::table('gudang_release', function (Blueprint $table) {
      $drop = [];
      foreach ([
        'goj_doc_id','goj_status','goj_note',
        'goj_approved_by','goj_approved_at',
        'goj_rejected_by','goj_rejected_at'
      ] as $c) {
        if (Schema::hasColumn('gudang_release', $c)) $drop[] = $c;
      }
      if (!empty($drop)) $table->dropColumn($drop);
    });
  }
};