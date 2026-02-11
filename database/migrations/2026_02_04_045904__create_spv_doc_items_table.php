<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpvDocItemsTable extends Migration
{
    public function up()
    {
        Schema::create('spv_doc_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('spv_doc_id')->index();
            $table->unsignedBigInteger('produksi_batch_id')->nullable()->index();
            $table->string('nama_produk')->nullable();
            $table->string('batch_no')->nullable();
            $table->string('kode_batch')->nullable();
            $table->date('tgl_release')->nullable();
            $table->date('tgl_expired')->nullable();
            $table->string('kemasan')->nullable();
            $table->string('isi')->nullable();
            $table->integer('jumlah')->nullable();
            $table->string('status_gudang')->nullable();
            $table->timestamps();

            $table->foreign('spv_doc_id')->references('id')->on('spv_docs')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('spv_doc_items');
    }
}
