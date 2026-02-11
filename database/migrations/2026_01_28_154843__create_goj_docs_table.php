<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('goj_docs', function (Blueprint $table) {
      $table->id();
      $table->string('doc_no', 50)->unique(); // contoh: GOJ-20260128-0001
      $table->date('doc_date')->nullable();

      $table->enum('status', ['PENDING','APPROVED','REJECTED'])->default('PENDING');

      $table->unsignedBigInteger('created_by')->nullable();
      $table->timestamp('created_at')->nullable();
      $table->timestamp('updated_at')->nullable();

      $table->unsignedBigInteger('approved_by')->nullable();
      $table->timestamp('approved_at')->nullable();

      $table->unsignedBigInteger('rejected_by')->nullable();
      $table->timestamp('rejected_at')->nullable();
      $table->string('reject_reason', 255)->nullable();

      $table->index(['status', 'doc_date']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('goj_docs');
  }
};