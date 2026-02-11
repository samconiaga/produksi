<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produksi_batches', function (Blueprint $table) {
            $table->id();

            // Relasi ke master produksi
            $table->foreignId('produksi_id')
                ->nullable()
                ->constrained('produksi')   // pastikan nama tabel master: 'produksi'
                ->nullOnDelete();

            /* ====================== IDENTITAS BATCH ====================== */
            $table->string('nama_produk');
            $table->string('no_batch')->nullable();
            $table->string('kode_batch')->nullable();
            $table->unsignedInteger('batch_ke')->default(1);

            $table->unsignedTinyInteger('bulan')->nullable();
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->string('tipe_alur', 50)->nullable();

            /* ====================== WORK ORDER ====================== */
            $table->date('wo_date')->nullable();
            $table->date('expected_date')->nullable();

            /* ====================== PROSES PRODUKSI ====================== */
            // Weighing
            $table->date('tgl_mulai_weighing')->nullable();
            $table->date('tgl_weighing')->nullable();

            // Mixing (realtime)
            $table->dateTime('tgl_mulai_mixing')->nullable();
            $table->dateTime('tgl_mixing')->nullable();

            // Capsule Filling (realtime)
            $table->dateTime('tgl_mulai_capsule_filling')->nullable();
            $table->dateTime('tgl_capsule_filling')->nullable();

            // Tableting (realtime)
            $table->dateTime('tgl_mulai_tableting')->nullable();
            $table->dateTime('tgl_tableting')->nullable();

            // Coating (summary, untuk semua tipe alur)
            $table->dateTime('tgl_mulai_coating')->nullable();
            $table->dateTime('tgl_coating')->nullable();

            // Coating per-step (khusus produk salut / TABLET_SALUT)
            // Step 1: Salut Inti
            $table->dateTime('tgl_mulai_coating_inti')->nullable();
            $table->dateTime('tgl_coating_inti')->nullable();

            // Step 2: Salut Dasar
            $table->dateTime('tgl_mulai_coating_dasar')->nullable();
            $table->dateTime('tgl_coating_dasar')->nullable();

            // Step 3: Salut Warna
            $table->dateTime('tgl_mulai_coating_warna')->nullable();
            $table->dateTime('tgl_coating_warna')->nullable();

            // Step 4: Polishing
            $table->dateTime('tgl_mulai_coating_polishing')->nullable();
            $table->dateTime('tgl_coating_polishing')->nullable();

            // Primary Pack
            $table->dateTime('tgl_mulai_primary_pack')->nullable();
            $table->dateTime('tgl_primary_pack')->nullable();

            // Secondary Pack
            $table->dateTime('tgl_mulai_secondary_pack_1')->nullable();
            $table->dateTime('tgl_secondary_pack_1')->nullable();
            $table->dateTime('tgl_mulai_secondary_pack_2')->nullable();
            $table->dateTime('tgl_secondary_pack_2')->nullable();

            /* ===================== QC — PRODUK ANTARA GRANUL ===================== */
            $table->date('tgl_datang_granul')->nullable();
            $table->date('tgl_analisa_granul')->nullable();
            $table->date('tgl_selesai_analisa_granul')->nullable();  // NEW
            $table->date('tgl_rilis_granul')->nullable();

            // Detail kartu pelulusan produk antara
            $table->date('granul_exp_date')->nullable();             // NEW
            $table->decimal('granul_berat', 10, 3)->nullable();      // NEW, kg
            $table->string('granul_no_wadah', 100)->nullable();      // NEW
            $table->string('granul_sign_code', 191)->nullable();     // NEW
            $table->dateTime('granul_signed_at')->nullable();        // NEW

            /* ===================== QC — PRODUK ANTARA TABLET ===================== */
            $table->date('tgl_datang_tablet')->nullable();
            $table->date('tgl_analisa_tablet')->nullable();
            $table->date('tgl_rilis_tablet')->nullable();

            /* ===================== QC — PRODUK RUAHAN ===================== */
            $table->date('tgl_datang_ruahan')->nullable();
            $table->date('tgl_analisa_ruahan')->nullable();
            $table->date('tgl_rilis_ruahan')->nullable();

            /* ===================== QC — PRODUK RUAHAN AKHIR ===================== */
            $table->date('tgl_datang_ruahan_akhir')->nullable();
            $table->date('tgl_analisa_ruahan_akhir')->nullable();
            $table->date('tgl_rilis_ruahan_akhir')->nullable();

            /* ===================== AFTER SECONDARY PACK ===================== */
            $table->unsignedInteger('qty_batch')->nullable();
            $table->string('status_qty_batch', 20)->nullable(); // pending | confirmed | rejected

            /* ===================== JOB SHEET QC ===================== */
            $table->date('tgl_konfirmasi_produksi')->nullable();
            $table->date('tgl_terima_jobsheet')->nullable();
            $table->string('status_jobsheet', 20)->nullable(); // pending | done
            $table->text('catatan_jobsheet')->nullable();

            /* ===================== SAMPLING QC ===================== */
            $table->date('tgl_sampling')->nullable();
            $table->string('status_sampling', 20)->nullable();  // pending | accepted | rejected
            $table->text('catatan_sampling')->nullable();

            /* ===================== COA QC/QA ===================== */
            $table->date('tgl_qc_kirim_coa')->nullable();
            $table->date('tgl_qa_terima_coa')->nullable();
            $table->string('status_coa', 20)->nullable();       // pending | done | rejected
            $table->text('catatan_coa')->nullable();

            /* ===================== REVIEW QA ===================== */
            $table->string('status_review', 20)->nullable();    // pending | hold | released | rejected
            $table->date('tgl_review')->nullable();
            $table->text('catatan_review')->nullable();

            /* ===================== LAINNYA ===================== */
            $table->unsignedInteger('hari_kerja')->nullable();
            $table->string('status_proses', 50)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produksi_batches');
    }
};
