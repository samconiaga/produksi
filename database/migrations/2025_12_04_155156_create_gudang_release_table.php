<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_release', function (Blueprint $table) {
            $table->id();

            // relasi ke PRODUKSI BATCH (karena ReleaseController memakai ProduksiBatch)
            $table->unsignedBigInteger('produksi_batch_id');
            $table->foreign('produksi_batch_id')
                  ->references('id')->on('produksi_batches')
                  ->onDelete('cascade');

            // data cek gudang
            $table->integer('qty_fisik')->nullable();
            $table->date('tanggal_expired')->nullable();
            $table->decimal('berat_fisik', 10, 3)->nullable();  // kg
            $table->string('no_wadah', 100)->nullable();

            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])
                  ->default('PENDING');

            $table->text('catatan')->nullable();

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_release');
    }
};
