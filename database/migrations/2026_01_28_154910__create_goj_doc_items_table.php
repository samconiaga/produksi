<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('goj_doc_items', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('goj_doc_id');
      $table->unsignedBigInteger('produksi_batch_id');

      // snapshot saat print (biar data dokumen konsisten)
      $table->string('nama_produk')->nullable();
      $table->string('batch_no')->nullable();     // no_batch
      $table->string('kode_batch')->nullable();
      $table->date('tgl_release')->nullable();
      $table->date('tgl_expired')->nullable();

      $table->string('kemasan')->nullable();
      $table->string('isi')->nullable();
      $table->integer('jumlah')->nullable();

      $table->string('status_gudang', 30)->nullable(); // APPROVED / REJECTED (gudang)
      $table->timestamps();

      $table->foreign('goj_doc_id')->references('id')->on('goj_docs')->onDelete('cascade');
      $table->index(['goj_doc_id']);
      $table->index(['produksi_batch_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('goj_doc_items');
  }
};