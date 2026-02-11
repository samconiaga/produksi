<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Produksi;
use App\Models\QcRelease;
use App\Models\GudangRelease;
use App\Models\HoldingLog;

class ProduksiBatch extends Model
{
    use HasFactory;

    protected $table = 'produksi_batches';
    public $timestamps = true;

    protected $fillable = [
        'produksi_id','nama_produk','no_batch','kode_batch','batch_ke','bulan','tahun','tipe_alur',
        'wo_date','expected_date',

        'tgl_mulai_weighing','tgl_weighing',
        'tgl_mulai_mixing','tgl_mixing',
        'tgl_mulai_capsule_filling','tgl_capsule_filling',
        'tgl_mulai_tableting','tgl_tableting',
        'tgl_mulai_coating','tgl_coating',

        'tgl_mulai_coating_inti','tgl_coating_inti',
        'tgl_mulai_coating_dasar','tgl_coating_dasar',
        'tgl_mulai_coating_warna','tgl_coating_warna',
        'tgl_mulai_coating_polishing','tgl_coating_polishing',

        'tgl_mulai_primary_pack','tgl_primary_pack',
        'tgl_mulai_secondary_pack_1','tgl_secondary_pack_1',
        'tgl_mulai_secondary_pack_2','tgl_secondary_pack_2',

        'hari_kerja','status_proses',

        'tgl_datang_granul','tgl_analisa_granul','tgl_selesai_analisa_granul','tgl_rilis_granul',
        'granul_exp_date','granul_berat','granul_no_wadah','granul_sign_code','granul_signed_at','granul_signed_by','granul_signed_level',

        'tgl_datang_tablet','tgl_analisa_tablet','tgl_selesai_analisa_tablet','tgl_rilis_tablet',
        'tablet_exp_date','tablet_berat','tablet_no_wadah','tablet_sign_code','tablet_signed_at','tablet_signed_by','tablet_signed_level',

        'tgl_datang_ruahan','tgl_analisa_ruahan','tgl_selesai_analisa_ruahan','tgl_rilis_ruahan',
        'ruahan_exp_date','ruahan_berat','ruahan_no_wadah','ruahan_sign_code','ruahan_signed_at','ruahan_signed_by','ruahan_signed_level',

        'tgl_datang_ruahan_akhir','tgl_analisa_ruahan_akhir','tgl_selesai_analisa_ruahan_akhir','tgl_rilis_ruahan_akhir',
        'ruahan_akhir_exp_date','ruahan_akhir_berat','ruahan_akhir_no_wadah','ruahan_akhir_sign_code','ruahan_akhir_signed_at','ruahan_akhir_signed_by','ruahan_akhir_signed_level',

        'qty_batch','status_qty_batch',
        'tgl_konfirmasi_produksi','tgl_terima_jobsheet','status_jobsheet','catatan_jobsheet',
        'tgl_sampling','status_sampling','catatan_sampling',
        'tgl_qc_kirim_coa','tgl_qa_terima_coa','status_coa','catatan_coa',
        'status_review','tgl_review','catatan_review',

        /* ✅ PAUSE (dipakai semua modul) */
        'is_paused',
        'paused_stage',
        'paused_reason',
        'paused_at',
        'paused_by',

        /* ✅ HOLDING */
        'is_holding',
        'holding_stage',
        'holding_return_to',
        'holding_reason',
        'holding_note',
        'holding_prev_status',
        'holding_at',
        'holding_by',

        /* =========================================================
         * ✅ REKON setelah STOP per modul (biar sekali set, aman selamanya)
         * ======================================================= */

        // WEIGHING
        'weighing_rekon_qty',
        'weighing_rekon_note',
        'weighing_rekon_at',
        'weighing_rekon_by',

        // MIXING
        'mixing_rekon_qty',
        'mixing_rekon_note',
        'mixing_rekon_at',
        'mixing_rekon_by',

        // TABLETING
        'tableting_rekon_qty',
        'tableting_rekon_note',
        'tableting_rekon_at',
        'tableting_rekon_by',

        // CAPSULE FILLING
        'capsule_filling_rekon_qty',
        'capsule_filling_rekon_note',
        'capsule_filling_rekon_at',
        'capsule_filling_rekon_by',

        // COATING
        'coating_rekon_qty',
        'coating_rekon_note',
        'coating_rekon_at',
        'coating_rekon_by',

        // PRIMARY PACK
        'primary_pack_rekon_qty',
        'primary_pack_rekon_note',
        'primary_pack_rekon_at',
        'primary_pack_rekon_by',

        // SECONDARY PACK (menu 1, tapi kamu punya 2 tahap, jadi aku siapkan 2 juga)
        'secondary_pack_1_rekon_qty',
        'secondary_pack_1_rekon_note',
        'secondary_pack_1_rekon_at',
        'secondary_pack_1_rekon_by',

        'secondary_pack_2_rekon_qty',
        'secondary_pack_2_rekon_note',
        'secondary_pack_2_rekon_at',
        'secondary_pack_2_rekon_by',

        // OPTIONAL (kalau mau satu rekapan final secondary pack)
        'secondary_pack_rekon_qty',
        'secondary_pack_rekon_note',
        'secondary_pack_rekon_at',
        'secondary_pack_rekon_by',
    ];

    protected $casts = [
        'wo_date' => 'date',
        'expected_date' => 'date',

        'tgl_mulai_weighing' => 'date',
        'tgl_weighing' => 'date',

        'tgl_mulai_mixing' => 'datetime',
        'tgl_mixing' => 'datetime',
        'tgl_mulai_capsule_filling' => 'datetime',
        'tgl_capsule_filling' => 'datetime',
        'tgl_mulai_tableting' => 'datetime',
        'tgl_tableting' => 'datetime',

        'tgl_mulai_coating' => 'datetime',
        'tgl_coating' => 'datetime',

        'tgl_mulai_coating_inti' => 'datetime',
        'tgl_coating_inti' => 'datetime',
        'tgl_mulai_coating_dasar' => 'datetime',
        'tgl_coating_dasar' => 'datetime',
        'tgl_mulai_coating_warna' => 'datetime',
        'tgl_coating_warna' => 'datetime',
        'tgl_mulai_coating_polishing' => 'datetime',
        'tgl_coating_polishing' => 'datetime',

        'tgl_mulai_primary_pack' => 'datetime',
        'tgl_primary_pack' => 'datetime',
        'tgl_mulai_secondary_pack_1' => 'datetime',
        'tgl_secondary_pack_1' => 'datetime',
        'tgl_mulai_secondary_pack_2' => 'datetime',
        'tgl_secondary_pack_2' => 'datetime',

        'tgl_datang_granul' => 'date',
        'tgl_analisa_granul' => 'date',
        'tgl_selesai_analisa_granul' => 'date',
        'tgl_rilis_granul' => 'date',
        'granul_exp_date' => 'date',
        'granul_berat' => 'decimal:3',
        'granul_signed_at' => 'datetime',

        'tgl_datang_tablet' => 'date',
        'tgl_analisa_tablet' => 'date',
        'tgl_selesai_analisa_tablet' => 'date',
        'tgl_rilis_tablet' => 'date',
        'tablet_exp_date' => 'date',
        'tablet_berat' => 'decimal:3',
        'tablet_signed_at' => 'datetime',

        'tgl_datang_ruahan' => 'date',
        'tgl_analisa_ruahan' => 'date',
        'tgl_selesai_analisa_ruahan' => 'date',
        'tgl_rilis_ruahan' => 'date',
        'ruahan_exp_date' => 'date',
        'ruahan_berat' => 'decimal:3',
        'ruahan_signed_at' => 'datetime',

        'tgl_datang_ruahan_akhir' => 'date',
        'tgl_analisa_ruahan_akhir' => 'date',
        'tgl_selesai_analisa_ruahan_akhir' => 'date',
        'tgl_rilis_ruahan_akhir' => 'date',
        'ruahan_akhir_exp_date' => 'date',
        'ruahan_akhir_berat' => 'decimal:3',
        'ruahan_akhir_signed_at' => 'datetime',

        'qty_batch' => 'integer',
        'tgl_konfirmasi_produksi' => 'date',
        'tgl_terima_jobsheet' => 'date',
        'tgl_sampling' => 'date',
        'tgl_qc_kirim_coa' => 'date',
        'tgl_qa_terima_coa' => 'date',
        'tgl_review' => 'date',

        /* ✅ PAUSE */
        'is_paused' => 'boolean',
        'paused_at' => 'datetime',

        /* ✅ HOLD */
        'is_holding' => 'boolean',
        'holding_at' => 'datetime',

        /* ✅ REKON casts */
        'weighing_rekon_qty' => 'integer',
        'weighing_rekon_at'  => 'datetime',

        'mixing_rekon_qty'   => 'integer',
        'mixing_rekon_at'    => 'datetime',

        'tableting_rekon_qty'=> 'integer',
        'tableting_rekon_at' => 'datetime',

        'capsule_filling_rekon_qty' => 'integer',
        'capsule_filling_rekon_at'  => 'datetime',

        'coating_rekon_qty'  => 'integer',
        'coating_rekon_at'   => 'datetime',

        'primary_pack_rekon_qty' => 'integer',
        'primary_pack_rekon_at'  => 'datetime',

        'secondary_pack_1_rekon_qty' => 'integer',
        'secondary_pack_1_rekon_at'  => 'datetime',

        'secondary_pack_2_rekon_qty' => 'integer',
        'secondary_pack_2_rekon_at'  => 'datetime',

        'secondary_pack_rekon_qty' => 'integer',
        'secondary_pack_rekon_at'  => 'datetime',
    ];

    protected $appends = ['status_label'];

    /* ================= RELATION ================= */

    public function produksi()
    {
        return $this->belongsTo(Produksi::class, 'produksi_id');
    }

    public function produk()
    {
        return $this->produksi();
    }

    public function qcRelease()
    {
        return $this->hasOne(QcRelease::class, 'produksi_batch_id');
    }

    public function gudangRelease()
    {
        return $this->hasOne(GudangRelease::class, 'produksi_batch_id');
    }

    /* ================= HOLDING LOG RELATION ================= */

    public function holdingLogs()
    {
        return $this->hasMany(HoldingLog::class, 'produksi_batch_id')
            ->orderByDesc('held_at');
    }

    public function holdingLogOpen()
    {
        return $this->hasOne(HoldingLog::class, 'produksi_batch_id')
            ->whereNull('resolved_at')
            ->ofMany('held_at', 'max');

        // fallback kalau ofMany() tidak ada:
        // return $this->hasOne(HoldingLog::class, 'produksi_batch_id')
        //     ->whereNull('resolved_at')
        //     ->orderByDesc('held_at');
    }

    /* ================= ACCESSOR ================= */

    public function getStatusLabelAttribute(): string
    {
        if ($this->is_holding) return 'HOLD';
        if ($this->is_paused)  return 'PAUSED';

        $raw = $this->status_proses;
        if (!$raw) return '-';

        $map = [
            // WEIGHING
            'WEIGHING_START'  => 'Weighing Start',
            'WEIGHING_PAUSED' => 'Weighing Paused',
            'WEIGHING_SELESAI'=> 'Weighing Selesai',

            // MIXING
            'MIXING_START'    => 'Mixing Start',
            'MIXING_PAUSED'   => 'Mixing Paused',
            'MIXING_SELESAI'  => 'Mixing Selesai',

            // TABLETING
            'TABLETING_START'   => 'Tableting Start',
            'TABLETING_PAUSED'  => 'Tableting Paused',
            'TABLETING_SELESAI' => 'Tableting Selesai',

            // CAPSULE FILLING
            'CAPSULE_FILLING_START'   => 'Capsule Filling Start',
            'CAPSULE_FILLING_PAUSED'  => 'Capsule Filling Paused',
            'CAPSULE_FILLING_SELESAI' => 'Capsule Filling Selesai',

            // COATING
            'COATING_START'   => 'Coating Start',
            'COATING_PAUSED'  => 'Coating Paused',
            'COATING_SELESAI' => 'Coating Selesai',

            // PRIMARY PACK
            'PRIMARY_PACK_START'   => 'Primary Pack Start',
            'PRIMARY_PACK_PAUSED'  => 'Primary Pack Paused',
            'PRIMARY_PACK_SELESAI' => 'Primary Pack Selesai',

            // SECONDARY PACK
            'SECONDARY_PACK_START'   => 'Secondary Pack Start',
            'SECONDARY_PACK_PAUSED'  => 'Secondary Pack Paused',
            'SECONDARY_PACK_SELESAI' => 'Secondary Pack Selesai',

            // QC (existing)
            'QC_GRANUL_DATANG' => 'QC Granul Datang',
            'QC_GRANUL_ANALISA_START' => 'QC Granul Analisa Start',
            'QC_GRANUL_ANALISA_STOP' => 'QC Granul Analisa Stop',
            'QC_GRANUL_RELEASED' => 'QC Granul Released',

            'QC_TABLET_DATANG' => 'QC Tablet Datang',
            'QC_TABLET_ANALISA_START' => 'QC Tablet Analisa Start',
            'QC_TABLET_ANALISA_STOP' => 'QC Tablet Analisa Stop',
            'QC_TABLET_RELEASED' => 'QC Tablet Released',

            'QC_RUAHAN_DATANG' => 'QC Ruahan Datang',
            'QC_RUAHAN_ANALISA_START' => 'QC Ruahan Analisa Start',
            'QC_RUAHAN_ANALISA_STOP' => 'QC Ruahan Analisa Stop',
            'QC_RUAHAN_RELEASED' => 'QC Ruahan Released',

            'QC_RUAHAN_AKHIR_DATANG' => 'QC Ruahan Akhir Datang',
            'QC_RUAHAN_AKHIR_ANALISA_START' => 'QC Ruahan Akhir Analisa Start',
            'QC_RUAHAN_AKHIR_ANALISA_STOP' => 'QC Ruahan Akhir Analisa Stop',
            'QC_RUAHAN_AKHIR_RELEASED' => 'QC Ruahan Akhir Released',
        ];

        return $map[$raw] ?? ucwords(strtolower(str_replace('_', ' ', $raw)));
    }

    /* ================= HELPERS ================= */

    public function isTabletSalut(): bool
    {
        if (($this->tipe_alur ?? null) === 'TABLET_SALUT') return true;

        $prod = $this->produksi;
        if ($prod) {
            if (($prod->tipe_alur ?? null) === 'TABLET_SALUT') return true;
            if (stripos($prod->bentuk_sediaan ?? '', 'Salut Gula') !== false) return true;
        }
        return false;
    }

    public function pauseText(): string
    {
        if (!$this->is_paused) return '';
        $reason = trim((string) ($this->paused_reason ?? ''));
        return $reason === '' ? '-' : $reason;
    }

    public function holdText(): string
    {
        if (!$this->is_holding) return '';
        $reason = trim((string) ($this->holding_reason ?? ''));
        return $reason === '' ? '-' : $reason;
    }

    /* ================= SCOPES ================= */

    public function scopeNeedWeighing($q)
    {
        return $q->whereNull('tgl_weighing');
    }

    public function scopeNeedMixing($q)
    {
        return $q->whereNotNull('tgl_weighing')->whereNull('tgl_mixing');
    }

    public function scopeHasQtyAfterSecondary($q)
    {
        return $q->whereNotNull('tgl_secondary_pack_1')->whereNotNull('qty_batch');
    }

    public function scopeReadyForReview($q)
    {
        return $q->where('status_qty_batch', 'confirmed')
            ->where('status_jobsheet', 'done')
            ->where('status_sampling', 'accepted')
            ->where('status_coa', 'done');
    }

    public function scopeNotHolding($q)
    {
        return $q->where(function ($w) {
            $w->whereNull('is_holding')->orWhere('is_holding', false);
        });
    }
}