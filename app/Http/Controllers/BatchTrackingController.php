<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class BatchTrackingController extends Controller
{
    /**
     * Cache daftar kolom per table untuk menghindari banyak query Schema::getColumnListing()
     * (mengurangi lag saat membuka index / show).
     *
     * @var array<string,array<string>>
     */
    private array $cols = [];

    /**
     * Definisi step tracking (timeline).
     */
    private function steps(): array
    {
        return [
            [
                'key'   => 'WEIGHING',
                'label' => 'Weighing',
                'type'  => 'point',
                'prefix'=> 'weighing',
                'start' => ['tgl_mulai_weighing', 'tgl_weighing', 'weighing_date', 'weighing_start'],
            ],
            [
                'key'   => 'MIXING',
                'label' => 'Mixing (Mulai - Selesai)',
                'type'  => 'range',
                'prefix'=> 'mixing',
                'start' => ['tgl_mulai_mixing', 'mixing_start', 'tgl_mixing', 'mixing_date'],
                'end'   => ['tgl_mixing', 'tgl_selesai_mixing', 'mixing_end', 'mixing_rekon_at'],
            ],
            [
                'key'   => 'TABLETING',
                'label' => 'Tableting (Mulai - Selesai)',
                'type'  => 'range',
                'prefix'=> 'tableting',
                'start' => ['tgl_mulai_tableting', 'tableting_start', 'tgl_tableting'],
                'end'   => ['tgl_tableting', 'tgl_selesai_tableting', 'tableting_end', 'tableting_rekon_at'],
            ],
            [
                'key'   => 'CAPSULE_FILLING',
                'label' => 'Capsule Filling (Mulai - Selesai)',
                'type'  => 'range',
                'prefix'=> 'capsule_filling',
                'start' => ['tgl_mulai_capsule_filling', 'capsule_filling_start', 'tgl_capsule_filling'],
                'end'   => ['tgl_capsule_filling', 'tgl_selesai_capsule_filling', 'capsule_filling_end', 'capsule_filling_rekon_at'],
            ],
            [
                'key'   => 'COATING',
                'label' => 'Coating (Mulai - Selesai)',
                'type'  => 'range',
                'prefix'=> 'coating',
                'start' => ['tgl_mulai_coating', 'coating_start', 'tgl_coating'],
                'end'   => ['tgl_coating', 'tgl_selesai_coating', 'coating_end', 'coating_rekon_at'],
            ],
            [
                'key'   => 'PRIMARY_PACK',
                'label' => 'Primary Pack (Mulai - Selesai)',
                'type'  => 'range',
                'prefix'=> 'primary_pack',
                'start' => ['tgl_mulai_primary_pack', 'primary_pack_start', 'tgl_primary_pack'],
                'end'   => ['tgl_primary_pack', 'tgl_selesai_primary_pack', 'primary_pack_end', 'primary_pack_rekon_at'],
            ],
            [
                'key'   => 'SECONDARY_PACK_1',
                'label' => 'Secondary Pack 1 (Mulai - Selesai)',
                'type'  => 'range',
                'prefix'=> 'secondary_pack_1',
                'start' => ['tgl_mulai_secondary_pack_1', 'secondary_pack_1_start', 'tgl_secondary_pack_1'],
                'end'   => ['tgl_selesai_secondary_pack_1', 'secondary_pack_1_end', 'secondary_pack_1'],
            ],
            [
                'key'   => 'SECONDARY_PACK_2',
                'label' => 'Secondary Pack 2 (Mulai - Selesai)',
                'type'  => 'range',
                'prefix'=> 'secondary_pack_2',
                'start' => ['tgl_mulai_secondary_pack_2', 'secondary_pack_2_start', 'tgl_secondary_pack_2'],
                'end'   => ['tgl_selesai_secondary_pack_2', 'secondary_pack_2_end', 'secondary_pack_2'],
            ],
            [
                'key'   => 'SECONDARY_PACK_FINAL',
                'label' => 'Secondary Pack (Final Rekon)',
                'type'  => 'point',
                'prefix'=> 'secondary_pack',
                'start' => ['secondary_pack_rekon_at', 'tgl_selesai_secondary_pack', 'tgl_selesai_secondary_pack_final'],
            ],
            [
                'key'   => 'QC_ANTARA_GRANUL_RELEASE',
                'label' => 'QC Produk Antara Granul • Release',
                'type'  => 'point',
                'prefix'=> 'rilis_antara_granul',
                'start' => ['tgl_rilis_antara_granul', 'tgl_rilis_granul', 'tgl_release_antara_granul', 'tgl_rilis_granul'],
            ],
            [
                'key'   => 'QC_TABLET_RELEASE',
                'label' => 'QC Produk Antara Tablet • Release',
                'type'  => 'point',
                'prefix'=> 'rilis_tablet',
                'start' => ['tgl_rilis_tablet', 'tgl_release_tablet', 'tgl_rilis_tablet'],
            ],
            [
                'key'   => 'QC_RUAHAN_RELEASE',
                'label' => 'QC Produk Ruahan • Release',
                'type'  => 'point',
                'prefix'=> 'rilis_ruahan',
                'start' => ['tgl_rilis_ruahan', 'tgl_release_ruahan'],
            ],
            [
                'key'   => 'QC_RUAHAN_AKHIR_RELEASE',
                'label' => 'QC Produk Ruahan Akhir • Release',
                'type'  => 'point',
                'prefix'=> 'rilis_ruahan_akhir',
                'start' => ['tgl_rilis_ruahan_akhir', 'tgl_release_ruahan_akhir'],
            ],
            [
                'key'   => 'GUDANG_RELEASE',
                'label' => 'Gudang Release',
                'type'  => 'point',
                'prefix'=> 'gudang_release',
                'start' => ['tgl_gudang_release', 'tgl_release_gudang', 'tgl_release_fg'],
            ],
        ];
    }

    /**
     * Optimized hasCol menggunakan cache kolom per table.
     */
    private function hasCol(string $table, string $col): bool
    {
        if (!isset($this->cols[$table])) {
            try {
                $this->cols[$table] = Schema::getColumnListing($table);
            } catch (\Throwable $e) {
                $this->cols[$table] = [];
            }
        }

        return in_array($col, $this->cols[$table], true);
    }

    /**
     * Ambil tanggal dari kandidat kolom (return Carbon|null).
     */
    private function pickDate(ProduksiBatch $batch, array $candidates): ?Carbon
    {
        $table = $batch->getTable();

        foreach ($candidates as $col) {
            if (!$this->hasCol($table, $col)) continue;

            $val = $batch->{$col};

            if ($val === null || $val === '') continue;

            if (is_string($val) && preg_match('/^0{4}-0{2}-0{2}/', $val)) continue;

            try {
                if ($val instanceof \Carbon\CarbonInterface) {
                    return Carbon::instance($val);
                }
                return Carbon::parse($val);
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private function dmy(?Carbon $dt): string
    {
        return $dt ? $dt->format('d-m-Y H:i') : '-';
    }

    private function humanDiff(?Carbon $from, ?Carbon $to): string
    {
        if (!$from) return '-';
        $to = $to ?: now();

        $mins = $from->diffInMinutes($to);
        $days = intdiv($mins, 1440);
        $hrs  = intdiv($mins % 1440, 60);
        $m    = $mins % 60;

        if ($days > 0) return "{$days} hari {$hrs} jam";
        if ($hrs > 0)  return "{$hrs} jam {$m} mnt";
        return "{$m} mnt";
    }

    /**
     * Resolve nilai 'by' menjadi nama user jika memungkinkan.
     * Menerima: numeric id, numeric string, username, email, atau nama sudah jadi.
     *
     * @param mixed $val
     * @return string|null
     */
    private function resolveUserName($val): ?string
    {
        if ($val === null || $val === '') return null;

        // already looks like a human-readable name (contains space and non-numeric) -> return as-is
        if (is_string($val) && preg_match('/[^\d]+/', $val) && !filter_var($val, FILTER_VALIDATE_INT)) {
            // if it's email or username, try to find full name; otherwise return as-is
            $maybe = trim($val);
        } else {
            $maybe = $val;
        }

        try {
            if (class_exists(User::class)) {
                // numeric id
                if (is_numeric($maybe)) {
                    $u = User::find(intval($maybe));
                    if ($u) return $this->getUserDisplayName($u);
                }

                // string: try username/email/name lookup
                if (is_string($maybe)) {
                    $u = User::where('name', $maybe)
                              ->orWhere('username', $maybe)
                              ->orWhere('email', $maybe)
                              ->first();
                    if ($u) return $this->getUserDisplayName($u);
                }
            }
        } catch (\Throwable $e) {
            // jika sesuatu error saat lookup, fallback ke nilai asli
        }

        // fallback: jika string, bersihkan dan return; jika bukan string, cast ke string
        return is_string($val) ? $val : (string)$val;
    }

    /**
     * Preferred display for a User model instance.
     *
     * @param User $u
     * @return string
     */
    private function getUserDisplayName(User $u): string
    {
        if (!empty($u->name)) return (string)$u->name;
        if (!empty($u->username)) return (string)$u->username;
        if (!empty($u->email)) return (string)$u->email;
        return 'user#' . $u->id;
    }

    /**
     * Kumpulkan rekon untuk sebuah step berdasarkan $prefix (bisa null).
     * Mengembalikan array: ['note'=>..., 'at'=>Carbon|null, 'by'=>..., 'qty'=>...]
     *
     * Perubahan penting: field 'by' akan di-resolve ke nama user jika memungkinkan.
     */
    private function collectRekon(ProduksiBatch $batch, ?string $prefix): array
    {
        $res = [
            'note'   => null,
            'at'     => null,
            'at_raw' => null,
            'by'     => null,
            'qty'    => null,
        ];

        if (!$prefix) return $res;

        $table = $batch->getTable();

        $candidates = [
            'note' => [
                "{$prefix}_rekon_note",
                "{$prefix}_rekon_notes",
                "{$prefix}_rekon_note_text",
                "{$prefix}_rekon_note1",
                "{$prefix}_note_rekon",
                "rekon_{$prefix}_note",
                "{$prefix}_note",
            ],
            'at' => [
                "{$prefix}_rekon_at",
                "{$prefix}_rekon_time",
                "{$prefix}_rekon_timestamp",
                "{$prefix}_rekon_date",
                "{$prefix}_rekon_on",
                "{$prefix}_rekon",
                "{$prefix}_at",
                "{$prefix}_time",
            ],
            'by' => [
                "{$prefix}_rekon_by",
                "{$prefix}_rekon_user",
                "{$prefix}_rekon_person",
                "{$prefix}_rekon_by_user",
                "{$prefix}_rekon_by_id",
                "{$prefix}_user",
                "{$prefix}_by",
            ],
            'qty' => [
                "{$prefix}_rekon_qty",
                "{$prefix}_rekon_quantity",
                "{$prefix}_rekon_berat",
                "{$prefix}_qty_rekon",
            ],
        ];

        foreach ($candidates as $k => $names) {
            foreach ($names as $col) {
                if (!$this->hasCol($table, $col)) continue;
                $val = $batch->{$col};
                if ($val === null || $val === '') continue;

                if ($k === 'at') {
                    if (is_string($val) && preg_match('/^0{4}-0{2}-0{2}/', $val)) {
                        $res['at_raw'] = (string)$val;
                        break;
                    }
                    try {
                        if ($val instanceof \Carbon\CarbonInterface) $res['at'] = Carbon::instance($val);
                        else $res['at'] = Carbon::parse($val);
                    } catch (\Throwable $e) {
                        $res['at_raw'] = (string)$val;
                    }
                } elseif ($k === 'by') {
                    // simpan mentah dulu, resolve name setelah loop (agar prioritas tetap)
                    $res['by'] = $val;
                } else {
                    $res[$k] = $val;
                }
                break;
            }
        }

        // resolve user name for 'by'
        if (!empty($res['by'])) {
            $res['by'] = $this->resolveUserName($res['by']);
        }

        return $res;
    }

    /**
     * Build timeline + status sekarang + lama stage.
     */
    private function buildTracking(ProduksiBatch $batch): array
    {
        $timeline = [];
        $table    = $batch->getTable();

        // WO & Expected
        $woDate       = $this->pickDate($batch, ['wo_date']);
        $expectedDate = $this->pickDate($batch, ['expected_date', 'exp_date']);

        // HOLD detection
        $isHolding = $this->hasCol($table, 'is_holding') ? (bool)$batch->is_holding : false;
        $holdReason = null;
        if ($isHolding) {
            foreach (['holding_reason', 'alasan_holding', 'hold_reason', 'note_holding', 'holding_note'] as $c) {
                if ($this->hasCol($table, $c) && !empty($batch->{$c})) {
                    $holdReason = (string)$batch->{$c};
                    break;
                }
            }
        }

        // lastUpdate: mulai dari WO/Expected
        $lastUpdate = null;
        foreach ([$woDate, $expectedDate] as $cand) {
            if ($cand && (!$lastUpdate || $cand->greaterThan($lastUpdate))) {
                $lastUpdate = $cand->copy();
            }
        }

        foreach ($this->steps() as $s) {
            $start = $this->pickDate($batch, $s['start'] ?? []);
            $end   = null;

            if (($s['type'] ?? 'point') === 'range') {
                $end = $this->pickDate($batch, $s['end'] ?? []);
            } else {
                $end = $start;
            }

            // collect rekon for this step (by will be resolved to name if possible)
            $rekon = $this->collectRekon($batch, $s['prefix'] ?? null);

            if (empty($end) && !empty($rekon['at']) && ($s['type'] ?? 'point') === 'range') {
                $end = $rekon['at'];
            }

            // consider rekon.at as candidate for lastUpdate
            if (!empty($rekon['at']) && $rekon['at'] instanceof Carbon) {
                if (!$lastUpdate || $rekon['at']->greaterThan($lastUpdate)) {
                    $lastUpdate = $rekon['at']->copy();
                }
            }

            // also consider start/end for lastUpdate
            if ($start || $end) {
                $cand = $end ?: $start;
                if ($cand && (!$lastUpdate || $cand->greaterThan($lastUpdate))) {
                    $lastUpdate = $cand->copy();
                }
            }

            $timeline[] = [
                'key'   => $s['key'],
                'label' => $s['label'],
                'type'  => $s['type'] ?? 'point',
                'start' => $start,
                'end'   => $end,
                'rekon' => $rekon,
            ];
        }

        // determine current stage & since
        $currentLabel = 'Belum Mulai';
        $since        = null;

        // HOLD specific: cari hold_since kolom (bila ada)
        $holdSince = null;
        if ($isHolding) {
            foreach (['holding_since', 'tgl_holding', 'hold_at', 'holding_at'] as $c) {
                if ($this->hasCol($table, $c) && !empty($batch->{$c})) {
                    try { $holdSince = Carbon::parse($batch->{$c}); } catch (\Throwable $e) { $holdSince = null; }
                    break;
                }
            }
        }

        if ($isHolding) {
            $currentLabel = 'HOLD';
            $since = $holdSince ?: $lastUpdate;
        } else {
            foreach (array_reverse($timeline) as $t) {
                if (($t['type'] ?? '') === 'range' && $t['start'] && !$t['end']) {
                    $currentLabel = $t['label'];
                    $since = $t['start'];
                    break;
                }
            }

            if (!$since) {
                foreach (array_reverse($timeline) as $t) {
                    if ($t['start']) {
                        $currentLabel = $t['label'];
                        $since = ($t['type'] === 'range') ? ($t['end'] ?: $t['start']) : $t['start'];
                        break;
                    }
                }
            }

            if (!$since && $woDate) {
                $currentLabel = 'Weighing';
                $since = $woDate;
            }
        }

        $hold_since_text = $hold_age_text = null;
        if ($isHolding) {
            $hold_since_text = $holdSince ? $this->dmy($holdSince) : ($lastUpdate ? $this->dmy($lastUpdate) : '-');
            $hold_age_text = $holdSince ? $this->humanDiff($holdSince, now()) : ($lastUpdate ? $this->humanDiff($lastUpdate, now()) : '-');
        }

        //
        // NEW: expired logic (masa simpan 4 bulan)
        //
        $shelfLimitMonths = 4; // ubah bila perlu
        $isFinished = false;

        // treat these keys as final indicators -> jika salah satunya punya end, anggap selesai
        $finalKeys = [
            'GUDANG_RELEASE',
            'SECONDARY_PACK_FINAL',
            'QC_RUAHAN_AKHIR_RELEASE',
            'QC_TABLET_RELEASE',
        ];

        foreach ($timeline as $t) {
            if (in_array($t['key'], $finalKeys, true) && !empty($t['end']) && $t['end'] instanceof Carbon) {
                $isFinished = true;
                break;
            }
        }

        // expired base: prefer WO date, fallback ke created_at if available
        $expiredBase = null;
        if ($woDate) {
            $expiredBase = $woDate;
        } else {
            // try created_at if available
            if ($this->hasCol($table, 'created_at') && !empty($batch->created_at)) {
                try {
                    $expiredBase = $batch->created_at instanceof \Carbon\CarbonInterface ? Carbon::instance($batch->created_at) : Carbon::parse($batch->created_at);
                } catch (\Throwable $e) {
                    $expiredBase = null;
                }
            }
        }

        $isExpired = false;
        $expired_base_text = null;
        $expired_age_text = null;
        if (!$isFinished && $expiredBase instanceof Carbon) {
            $months = $expiredBase->diffInMonths(now());
            if ($months >= $shelfLimitMonths) {
                $isExpired = true;
            }
            $expired_base_text = $this->dmy($expiredBase);
            $expired_age_text = "{$months} bulan";
        }

        return [
            'wo_date'        => $woDate,
            'wo_text'        => $this->dmy($woDate),
            'expected_date'  => $expectedDate,
            'expected_text'  => $this->dmy($expectedDate),

            'is_holding'       => $isHolding,
            'hold_reason'      => $holdReason,
            'hold_since'       => $holdSince,
            'hold_since_text'  => $hold_since_text,
            'hold_age_text'    => $hold_age_text,

            'current'          => $currentLabel,
            'since'            => $since,
            'since_text'       => $this->dmy($since),
            'age_text'         => $this->humanDiff($since, now()),

            'last_update'      => $lastUpdate,
            'last_text'        => $this->dmy($lastUpdate),

            'timeline'         => $timeline,

            // tambahan untuk view: expired / masa simpan terlewati
            'is_expired'         => $isExpired,
            'expired_base_text'  => $expired_base_text,
            'expired_age_text'   => $expired_age_text,
            'expired_limit_months'=> $shelfLimitMonths,
            'is_finished'        => $isFinished,
        ];
    }

    /**
     * Index listing (tracking batch table).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $search  = trim((string)$request->get('q', ''));
        $bulan   = $request->get('bulan', 'all');
        $tahun   = $request->get('tahun', '');
        $perPage = (int)$request->get('per_page', 20);
        if (!in_array($perPage, [10,20,50,100], true)) $perPage = 20;

        $table = (new ProduksiBatch)->getTable();

        $query = ProduksiBatch::with('produksi');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_produk', 'like', "%{$search}%")
                  ->orWhere('no_batch', 'like', "%{$search}%")
                  ->orWhere('kode_batch', 'like', "%{$search}%");
            });
        }

        if ($bulan !== null && $bulan !== '' && $bulan !== 'all' && $this->hasCol($table, 'bulan')) {
            $query->where('bulan', (int)$bulan);
        }

        if ($tahun !== null && $tahun !== '' && $this->hasCol($table, 'tahun')) {
            $query->where('tahun', (int)$tahun);
        }

        $batches = $query
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->orderByDesc('wo_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        // attach tracking info to each batch (transform collection)
        $batches->getCollection()->transform(function ($b) {
            $b->track = $this->buildTracking($b);
            return $b;
        });

        return view('produksi.tracking_batch.index', compact(
            'batches', 'search', 'bulan', 'tahun', 'perPage', 'user'
        ));
    }

    /**
     * Show detail tracking for a single batch.
     */
    public function show(ProduksiBatch $batch)
    {
        $user = Auth::user();
        $batch->load('produksi');

        $track = $this->buildTracking($batch);

        return view('produksi.tracking_batch.show', compact('batch', 'track', 'user'));
    }
}