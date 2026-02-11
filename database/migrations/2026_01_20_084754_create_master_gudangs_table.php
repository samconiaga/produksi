<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_gudangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();       // contoh: GDU, GDJ, GQ
            $table->string('nama', 150);                // contoh: Gudang Utama
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_gudangs');
    }
};
