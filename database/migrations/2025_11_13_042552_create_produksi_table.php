<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produksi', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Kode produk: misal "20", "109A", "49A", dll (boleh null+unique)
            $table->string('kode_produk', 50)->nullable()->unique();

            // Nama produk, misal "Coric 300", "Samcodin", dll
            $table->string('nama_produk', 150);

            // Kategori: ETHICAL / OTC / TRADISIONAL
            $table->string('kategori_produk', 50)->nullable();

            // Estimasi qty (Est Qty di sheet)
            $table->unsignedInteger('est_qty')->nullable();

            // Tablet, kapsul, sirup kering, CLO, dll
            $table->string('bentuk_sediaan', 50)->nullable();

            // CLO, CAIRAN_LUAR, DRY_SYRUP, TABLET_NON_SALUT, TABLET_SALUT, KAPSUL, dll
            $table->string('tipe_alur', 50);

            // Total hari target (opsional)
            $table->unsignedInteger('leadtime_target')->nullable();

            // Masa kadaluarsa (shelf life) dalam TAHUN.
            // Nanti dipakai untuk hitung Exp Date:
            // exp_date = tgl_mulai_weighing + expired_years (bulan/tahun yang dipakai di UI).
            $table->unsignedTinyInteger('expired_years')->nullable();

            $table->boolean('is_aktif')->default(true);

            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produksi');
    }
};
