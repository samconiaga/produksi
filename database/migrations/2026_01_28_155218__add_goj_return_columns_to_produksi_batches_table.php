<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('produksi_batches', function (Blueprint $table) {
      if (!Schema::hasColumn('produksi_batches', 'goj_returned')) {
        $table->boolean('goj_returned')->default(false)->after('status_review');
        $table->index('goj_returned');
      }
      if (!Schema::hasColumn('produksi_batches', 'goj_return_note')) {
        $table->string('goj_return_note', 255)->nullable()->after('goj_returned');
      }
      if (!Schema::hasColumn('produksi_batches', 'goj_returned_at')) {
        $table->timestamp('goj_returned_at')->nullable()->after('goj_return_note');
      }
    });
  }

  public function down(): void
  {
    Schema::table('produksi_batches', function (Blueprint $table) {
      foreach (['goj_returned','goj_return_note','goj_returned_at'] as $col) {
        if (Schema::hasColumn('produksi_batches', $col)) {
          $table->dropColumn($col);
        }
      }
    });
  }
};