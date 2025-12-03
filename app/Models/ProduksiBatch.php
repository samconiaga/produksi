<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\QcRelease;

class ProduksiBatch extends Model
{
    use HasFactory;

    protected $table = 'produksi_batches';

    protected $fillable = [

        /* ====================== IDENTITAS BATCH ====================== */
        'produksi_id',
        'nama_produk',
        'no_batch',
        'kode_batch',
        'batch_ke',
        'bulan',
        'tahun',
        'tipe_alur',

        /* ====================== WORK ORDER ====================== */
        'wo_date',
        'expected_date',

        /* ====================== PROSES PRODUKSI ====================== */
        // Weighing
        'tgl_mulai_weighing',
        'tgl_weighing',

        // Realtime Mixing
        'tgl_mulai_mixing',
        'tgl_mixing',

        // Realtime Capsule Filling
        'tgl_mulai_capsule_filling',
        'tgl_capsule_filling',

        // Realtime Tableting
        'tgl_mulai_tableting',
        'tgl_tableting',

        // Realtime Coating (summary)
        'tgl_mulai_coating',
        'tgl_coating',

        // Coating steps (khusus TABLET_SALUT)
        'tgl_mulai_coating_inti',
        'tgl_coating_inti',
        'tgl_mulai_coating_dasar',
        'tgl_coating_dasar',
        'tgl_mulai_coating_warna',
        'tgl_coating_warna',
        'tgl_mulai_coating_polishing',
        'tgl_coating_polishing',

        // Packing
        'tgl_mulai_primary_pack',
        'tgl_primary_pack',

        'tgl_mulai_secondary_pack_1',
        'tgl_secondary_pack_1',

        'tgl_mulai_secondary_pack_2',
        'tgl_secondary_pack_2',

        'hari_kerja',
        'status_proses',

        /* ====================== QC DETAIL (untuk modul QC Release) ====================== */
        // Produk Antara Granul
        'tgl_datang_granul',
        'tgl_analisa_granul',
        'tgl_rilis_granul',

        // Produk Antara Tablet
        'tgl_datang_tablet',
        'tgl_analisa_tablet',
        'tgl_rilis_tablet',

        // Produk Ruahan
        'tgl_datang_ruahan',
        'tgl_analisa_ruahan',
        'tgl_rilis_ruahan',

        // Produk Ruahan Akhir
        'tgl_datang_ruahan_akhir',
        'tgl_analisa_ruahan_akhir',
        'tgl_rilis_ruahan_akhir',

        /* ====================== AFTER SECONDARY PACK ====================== */
        'qty_batch',
        'status_qty_batch',

        /* ==== Job Sheet QC ==== */
        'tgl_konfirmasi_produksi',
        'tgl_terima_jobsheet',
        'status_jobsheet',
        'catatan_jobsheet',

        /* ==== Sampling QC ==== */
        'tgl_sampling',
        'status_sampling',
        'catatan_sampling',

        /* ==== COA QC/QA ==== */
        'tgl_qc_kirim_coa',
        'tgl_qa_terima_coa',
        'status_coa',
        'catatan_coa',

        /* ==== REVIEW QA ==== */
        'status_review',
        'tgl_review',
        'catatan_review',
    ];

    /* ================= CASTS ================= */
    protected $casts = [
        'wo_date'       => 'date',
        'expected_date' => 'date',

        'tgl_mulai_weighing' => 'date',
        'tgl_weighing'       => 'date',

        // Realtime: mixing, capsule, tableting, coating
        'tgl_mulai_mixing'          => 'datetime',
        'tgl_mixing'                => 'datetime',
        'tgl_mulai_capsule_filling' => 'datetime',
        'tgl_capsule_filling'       => 'datetime',
        'tgl_mulai_tableting'       => 'datetime',
        'tgl_tableting'             => 'datetime',

        'tgl_mulai_coating'         => 'datetime',
        'tgl_coating'               => 'datetime',

        'tgl_mulai_coating_inti'       => 'datetime',
        'tgl_coating_inti'             => 'datetime',
        'tgl_mulai_coating_dasar'      => 'datetime',
        'tgl_coating_dasar'            => 'datetime',
        'tgl_mulai_coating_warna'      => 'datetime',
        'tgl_coating_warna'            => 'datetime',
        'tgl_mulai_coating_polishing'  => 'datetime',
        'tgl_coating_polishing'        => 'datetime',

        'tgl_mulai_primary_pack'    => 'datetime',
        'tgl_primary_pack'          => 'datetime',

        'tgl_mulai_secondary_pack_1'=> 'datetime',
        'tgl_secondary_pack_1'      => 'datetime',

        'tgl_mulai_secondary_pack_2'=> 'datetime',
        'tgl_secondary_pack_2'      => 'datetime',

        /* ===== QC (DATE, dipakai di QC Release) ===== */
        'tgl_datang_granul'        => 'date',
        'tgl_analisa_granul'       => 'date',
        'tgl_rilis_granul'         => 'date',

        'tgl_datang_tablet'        => 'date',
        'tgl_analisa_tablet'       => 'date',
        'tgl_rilis_tablet'         => 'date',

        'tgl_datang_ruahan'        => 'date',
        'tgl_analisa_ruahan'       => 'date',
        'tgl_rilis_ruahan'         => 'date',

        'tgl_datang_ruahan_akhir'  => 'date',
        'tgl_analisa_ruahan_akhir' => 'date',
        'tgl_rilis_ruahan_akhir'   => 'date',

        'qty_batch'               => 'integer',

        'tgl_konfirmasi_produksi' => 'date',
        'tgl_terima_jobsheet'     => 'date',

        'tgl_sampling'            => 'date',

        'tgl_qc_kirim_coa'        => 'date',
        'tgl_qa_terima_coa'       => 'date',

        'tgl_review'              => 'date',
    ];

    /* ================= RELATION ================= */

    public function produksi()
    {
        return $this->belongsTo(Produksi::class, 'produksi_id');
    }

    // alias
    public function produk()
    {
        return $this->produksi();
    }

    /**
     * Relasi lama ke tabel qc_releases (kalau masih dipakai).
     * Untuk QC Release versi terbaru kita pakai kolom langsung di produksi_batches,
     * jadi relasi ini sifatnya opsional / legacy.
     */
    public function qcRelease()
    {
        return $this->hasOne(QcRelease::class, 'produksi_batch_id');
    }

    /* ================= HELPER ================= */

    /**
     * Cek apakah batch ini termasuk tablet salut (multi-step coating).
     */
    public function isTabletSalut(): bool
    {
        if ($this->tipe_alur === 'TABLET_SALUT') {
            return true;
        }

        // fallback dari relasi master (kalau tipe_alur batch null)
        $prod = $this->produksi;
        if ($prod) {
            if ($prod->tipe_alur === 'TABLET_SALUT') {
                return true;
            }
            if (stripos($prod->bentuk_sediaan ?? '', 'Salut Gula') !== false) {
                return true;
            }
        }

        return false;
    }

    /* ================= SCOPES ================= */

    // Weighing
    public function scopeNeedWeighing($q)
    {
        return $q->whereNull('tgl_weighing');
    }

    // Mixing
    public function scopeNeedMixing($q)
    {
        return $q->whereNotNull('tgl_weighing')
                 ->whereNull('tgl_mixing');
    }

    // Batch yang bisa masuk Qty Batch (setelah Secondary Pack 1 dan qty sudah diisi)
    public function scopeHasQtyAfterSecondary($q)
    {
        return $q->whereNotNull('tgl_secondary_pack_1')
                 ->whereNotNull('qty_batch');
    }

    // Untuk modul Review (complete step)
    public function scopeReadyForReview($q)
    {
        return $q->where('status_qty_batch', 'confirmed')
                 ->where('status_jobsheet', 'done')
                 ->where('status_sampling', 'accepted')
                 ->where('status_coa', 'done');
    }
}
