<?php

namespace App\Http\Controllers;

use App\Models\Produksi;
use App\Models\ProduksiBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ===== filter =====
        $bulan = $request->get('bulan', 'all'); // 'all' / 1..12 (backward compat)
        $tahun = $request->get('tahun') ?? date('Y');
        $fromDate = $request->get('from') ?: null; // yyyy-mm-dd
        $toDate   = $request->get('to') ?: null;   // yyyy-mm-dd
        $selectedBatch = $request->get('batch') ?: null;
        $selectedModule = $request->get('module') ?: '_all';

        // base query
        $base = ProduksiBatch::query();

        // detect wo_date column & year/month columns
        $table = (new ProduksiBatch())->getTable();
        $useYearMonth = Schema::hasColumn($table, 'tahun') && Schema::hasColumn($table, 'bulan');

        // apply month/year filter (if present)
        if ($useYearMonth) {
            if ($tahun !== null && $tahun !== '') $base->where('tahun', (int)$tahun);
            if ($bulan !== null && $bulan !== '' && $bulan !== 'all') $base->where('bulan', (int)$bulan);
        } else {
            if (Schema::hasColumn($table, 'wo_date')) {
                if ($tahun !== null && $tahun !== '') $base->whereYear('wo_date', (int)$tahun);
                if ($bulan !== null && $bulan !== '' && $bulan !== 'all') $base->whereMonth('wo_date', (int)$bulan);
            }
        }

        // apply custom date range filter if provided (overrules month/year filtering for more precision)
        if ($fromDate) {
            if (Schema::hasColumn($table, 'wo_date')) {
                $base->whereDate('wo_date','>=', $fromDate);
            } else {
                $base->whereDate('created_at','>=', $fromDate);
            }
        }
        if ($toDate) {
            if (Schema::hasColumn($table, 'wo_date')) {
                $base->whereDate('wo_date','<=', $toDate);
            } else {
                $base->whereDate('created_at','<=', $toDate);
            }
        }

        // if user filters by a specific batch id or WO identifier, apply it
        $woCandidates = ['wo','wo_number','no_wo','no_wo_1','no_wo1','no_wo2'];
        $woCol = null;
        foreach ($woCandidates as $w) {
            if (Schema::hasColumn($table, $w)) { $woCol = $w; break; }
        }
        if ($selectedBatch) {
            if (is_numeric($selectedBatch)) {
                $base->where(function($q) use ($selectedBatch, $woCol) {
                    $q->where('id', (int)$selectedBatch);
                    if ($woCol) $q->orWhere($woCol, (string)$selectedBatch);
                });
            } else {
                if ($woCol) $base->where($woCol, (string)$selectedBatch);
            }
        }

        // ===== KPI PRODUK & BATCH =====
        $masterProdukCount = class_exists(Produksi::class) ? Produksi::count() : 0;
        $totalBatch = (clone $base)->count();
        $batchAktifCount = (clone $base)->whereNull('tgl_secondary_pack_2')->count();
        $batchSelesaiCount = (clone $base)->whereNotNull('tgl_secondary_pack_2')->count();
        $totalQtyBatch = (clone $base)
            ->selectRaw('COALESCE(SUM(COALESCE(qty_batch,0)),0) AS s')
            ->value('s') ?? 0;

        // ============================================================
        // Build monthly bars for the selected year (1..12)
        // ============================================================
        $year = (int) $tahun;
        $months = []; $barDone = []; $barInProgress = []; $barDimusnahkan = [];

        $destroyCandidates = ['is_dimusnahkan','dimusnahkan','is_destroyed','is_destroy','flag_dimusnahkan'];
        $destroyCol = null;
        foreach ($destroyCandidates as $c) if (Schema::hasColumn($table, $c)) { $destroyCol = $c; break; }

        $karCandidates = ['is_karantina','karantina','in_karantina','is_quarantine','in_quarantine'];
        $karCol = null;
        foreach ($karCandidates as $c) if (Schema::hasColumn($table, $c)) { $karCol = $c; break; }

        $buildMonthlyQuery = function($m) use ($base, $useYearMonth, $year, $table) {
            $q = (clone $base);
            if ($useYearMonth) {
                $q->where('tahun', $year)->where('bulan', $m);
            } else {
                if (Schema::hasColumn($table, 'wo_date')) {
                    $q->whereYear('wo_date', $year)->whereMonth('wo_date', $m);
                }
            }
            return $q;
        };

        $startedCols = ['tgl_weighing','tgl_mixing','tgl_tableting','tgl_coating','tgl_capsule_filling','tgl_primary_pack','tgl_secondary_pack_1'];

        for ($m = 1; $m <= 12; $m++) {
            $months[] = Carbon::create()->month($m)->translatedFormat('M');
            $q = $buildMonthlyQuery($m);

            // done (secondary2)
            $done = (clone $q)->whereNotNull('tgl_secondary_pack_2')->count();
            $barDone[] = (int)$done;

            // in progress: not done but at least one started tgl_*
            $inq = (clone $q)->whereNull('tgl_secondary_pack_2');
            $hasStarted = false;
            foreach ($startedCols as $c) if (Schema::hasColumn($table, $c)) { $hasStarted = true; break; }
            if ($hasStarted) {
                $inq = $inq->where(function($qq) use ($startedCols, $table) {
                    $first = true;
                    foreach ($startedCols as $c) {
                        if (Schema::hasColumn($table, $c)) {
                            if ($first) { $qq->whereNotNull($c); $first = false; }
                            else { $qq->orWhereNotNull($c); }
                        }
                    }
                });
                $inprogressCount = $inq->count();
            } else {
                $inprogressCount = $inq->count();
            }
            $barInProgress[] = (int)$inprogressCount;

            // dimusnahkan
            $dim = $destroyCol ? (clone $q)->where($destroyCol, 1)->count() : 0;
            $barDimusnahkan[] = (int)$dim;
        }

        // ============================================================
        // Per-module KPI (jumlah batch yang sudah melewati step)
        // ============================================================
        $moduleCols = [
            'weighing' => 'tgl_weighing',
            'mixing' => 'tgl_mixing',
            'tableting' => 'tgl_tableting',
            'coating' => 'tgl_coating',
            'capsule_filling' => 'tgl_capsule_filling',
            'primary' => 'tgl_primary_pack',
            'secondary1' => 'tgl_secondary_pack_1',
            'secondary2' => 'tgl_secondary_pack_2',
        ];

        $moduleKPI = [];
        foreach ($moduleCols as $k => $col) {
            $moduleKPI[$k] = Schema::hasColumn($table, $col) ? (clone $base)->whereNotNull($col)->count() : 0;
        }

        $batchInKarantina = $karCol ? (clone $base)->whereNotNull('tgl_secondary_pack_2')->where($karCol,1)->count() : (int)$batchSelesaiCount;

        // ============================================================
        // Progress per produk (sampai mana) -- (we keep it but will not show Top Produk UI)
        // ============================================================
        $chartMode = ((int)$totalQtyBatch > 0) ? 'qty' : 'count';
        if ($chartMode === 'qty') {
            $qtyByProdukRows = (clone $base)
                ->selectRaw("COALESCE(NULLIF(TRIM(nama_produk),''),'(Tanpa Nama)') AS produk")
                ->selectRaw("COALESCE(SUM(COALESCE(qty_batch,0)),0) AS total_val")
                ->groupBy('produk')
                ->orderByDesc('total_val')
                ->limit(12)
                ->get();
        } else {
            $qtyByProdukRows = (clone $base)
                ->selectRaw("COALESCE(NULLIF(TRIM(nama_produk),''),'(Tanpa Nama)') AS produk")
                ->selectRaw("COUNT(*) AS total_val")
                ->groupBy('produk')
                ->orderByDesc('total_val')
                ->limit(12)
                ->get();
        }
        $chartProdukLabels = $qtyByProdukRows->pluck('produk')->values();
        $chartProdukData   = $qtyByProdukRows->pluck('total_val')->map(fn($v)=>(int)$v)->values();

        // dateseries (optional)
        if ($chartMode === 'qty') {
            $qtyByDateRows = (clone $base)
                ->whereNotNull('wo_date')
                ->selectRaw("DATE(wo_date) as d")
                ->selectRaw("COALESCE(SUM(COALESCE(qty_batch,0)),0) AS total_val")
                ->groupBy('d')
                ->orderBy('d')
                ->get();
        } else {
            $qtyByDateRows = (clone $base)
                ->whereNotNull('wo_date')
                ->selectRaw("DATE(wo_date) as d")
                ->selectRaw("COUNT(*) AS total_val")
                ->groupBy('d')
                ->orderBy('d')
                ->get();
        }
        $chartDateLabels = $qtyByDateRows->pluck('d')->values();
        $chartDateData   = $qtyByDateRows->pluck('total_val')->map(fn($v)=>(int)$v)->values();

        $progressByProduk = (clone $base)
            ->selectRaw("COALESCE(NULLIF(TRIM(nama_produk),''),'(Tanpa Nama)') AS produk")
            ->selectRaw("COUNT(*) AS total_batch")
            ->selectRaw("SUM(CASE WHEN tgl_weighing IS NOT NULL THEN 1 ELSE 0 END) AS done_weighing")
            ->selectRaw("SUM(CASE WHEN tgl_mixing IS NOT NULL THEN 1 ELSE 0 END) AS done_mixing")
            ->selectRaw("SUM(CASE WHEN tgl_tableting IS NOT NULL THEN 1 ELSE 0 END) AS done_tableting")
            ->selectRaw("SUM(CASE WHEN tgl_coating IS NOT NULL THEN 1 ELSE 0 END) AS done_coating")
            ->selectRaw("SUM(CASE WHEN tgl_primary_pack IS NOT NULL THEN 1 ELSE 0 END) AS done_primary")
            ->selectRaw("SUM(CASE WHEN tgl_secondary_pack_1 IS NOT NULL THEN 1 ELSE 0 END) AS done_secondary1")
            ->selectRaw("SUM(CASE WHEN tgl_secondary_pack_2 IS NOT NULL THEN 1 ELSE 0 END) AS done_secondary2")
            ->groupBy('produk')
            ->orderByDesc('total_batch')
            ->limit(15)
            ->get()
            ->map(function ($r) {
                $r->last_step = $this->lastStepLabelFromRow($r);
                return $r;
            });

        // ===== REKON calculation (overview doughnut & per-module breakdown) =====
        $allBatches = (clone $base)->get([
            'id','tipe_alur','nama_produk','wo_date',
            'tgl_weighing','tgl_mixing','tgl_tableting','tgl_coating','tgl_capsule_filling',
            'tgl_primary_pack','tgl_secondary_pack_1','tgl_secondary_pack_2',
            'is_paused','is_holding','updated_at'
        ]);

        $rekonSes = $rekonMendekati = $rekonTidak = 0;
        $moduleCounters = [];
        foreach (array_keys($moduleCols) as $k) $moduleCounters[$k] = ['done'=>0,'total'=>0];

        $perModuleRekon = [];
        foreach (array_keys($moduleCols) as $k) $perModuleRekon[$k] = ['sesuai'=>0,'mendekati'=>0,'tidak'=>0,'total'=>0];

        foreach ($allBatches as $b) {
            $steps = [];
            $steps['weighing'] = !is_null($b->tgl_weighing);
            $steps['mixing']   = !is_null($b->tgl_mixing);

            if (isset($b->tipe_alur) && strtoupper((string)$b->tipe_alur) === 'KAPSUL') {
                $steps['capsule_filling'] = !is_null($b->tgl_capsule_filling);
                $steps['tableting'] = null; $steps['coating'] = null;
            } else {
                $steps['tableting'] = !is_null($b->tgl_tableting);
                $steps['coating'] = !is_null($b->tgl_coating);
                $steps['capsule_filling'] = null;
            }

            $steps['primary'] = !is_null($b->tgl_primary_pack);
            $steps['secondary1'] = !is_null($b->tgl_secondary_pack_1);
            $steps['secondary2'] = !is_null($b->tgl_secondary_pack_2);

            foreach ($moduleCounters as $k => &$c) {
                if (!array_key_exists($k, $steps) || $steps[$k] === null) continue;
                $c['total']++;
                if ($steps[$k]) $c['done']++;
            }
            unset($c);

            $applicableSteps = array_filter($steps, fn($v) => !is_null($v));
            $totalSteps = count($applicableSteps);
            $doneSteps = count(array_filter($applicableSteps, fn($v) => (bool)$v));
            $pct = $totalSteps ? ($doneSteps / $totalSteps) * 100 : 0;

            if ($pct >= 90) $rekonSes++;
            elseif ($pct >= 50) $rekonMendekati++;
            else $rekonTidak++;

            foreach ($perModuleRekon as $k => &$bucket) {
                if (!array_key_exists($k, $steps) || $steps[$k] === null) continue;
                $bucket['total']++;
                if ($pct >= 90) $bucket['sesuai']++;
                elseif ($pct >= 50) $bucket['mendekati']++;
                else $bucket['tidak']++;
            }
            unset($bucket);
        }

        $rekonLabels = ['Sesuai','Mendekati','Tidak Memenuhi'];
        $rekonData = [$rekonSes, $rekonMendekati, $rekonTidak];

        // ===== BUILD perModule (used by some parts of view) =====
        $perModule = [];
        foreach ($moduleCounters as $k => $c) {
            if ($c['total'] === 0) continue;
            $perModule[] = [
                'key' => $k,
                'label' => ucfirst(str_replace('_',' ',$k)),
                'done' => (int)$c['done'],
                'total'=> (int)$c['total'],
                'pct' => $c['total'] ? round(($c['done']/$c['total'])*100, 1) : 0,
            ];
        }

        // prepare perModule summary and current batches
        $perModuleSummary = [];
        foreach ($moduleCounters as $k => $c) {
            $perModuleSummary[$k] = [
                'key' => $k,
                'label' => ucfirst(str_replace('_',' ',$k)),
                'done' => (int)$c['done'],
                'total' => (int)$c['total'],
                'pct' => $c['total'] ? round(($c['done']/$c['total'])*100, 1) : 0,
                'in_progress' => 0,
                'current_batches' => [],
            ];
        }

        // monthly per-module arrays
        $monthlyDone = []; $monthlyTotal = [];
        for ($m = 1; $m <= 12; $m++) {
            $qMonth = $buildMonthlyQuery($m);
            $totalThisMonth = (clone $qMonth)->count();
            foreach ($moduleCols as $k => $col) {
                if (!isset($monthlyDone[$k])) { $monthlyDone[$k] = []; $monthlyTotal[$k] = []; }
                $doneCount = Schema::hasColumn($table, $col) ? (clone $qMonth)->whereNotNull($col)->count() : 0;
                $monthlyDone[$k][] = (int)$doneCount;
                $monthlyTotal[$k][] = (int)$totalThisMonth;
            }
        }

        // next-step map (best-effort) and in-progress counts
        $nextColMap = [
            'weighing' => 'tgl_mixing',
            'mixing' => (Schema::hasColumn($table,'tgl_tableting') ? 'tgl_tableting' : (Schema::hasColumn($table,'tgl_coating') ? 'tgl_coating' : 'tgl_primary_pack')),
            'tableting' => 'tgl_coating',
            'coating' => 'tgl_primary_pack',
            'capsule_filling' => 'tgl_primary_pack',
            'primary' => 'tgl_secondary_pack_1',
            'secondary1' => 'tgl_secondary_pack_2',
            'secondary2' => null
        ];

        $moduleInProgressCounts = [];
        foreach ($moduleCols as $k => $col) {
            if (!Schema::hasColumn($table, $col)) {
                $moduleInProgressCounts[$k] = 0;
                continue;
            }
            $q = (clone $base)->whereNotNull($col)->whereNull('tgl_secondary_pack_2');
            $next = $nextColMap[$k] ?? null;
            if ($next && Schema::hasColumn($table, $next)) $q = $q->whereNull($next);
            $moduleInProgressCounts[$k] = (int) $q->count();
            if (isset($perModuleSummary[$k])) $perModuleSummary[$k]['in_progress'] = $moduleInProgressCounts[$k];
        }

        // sample current batches per module (max 5) -- include current_step & minutes since module entry
        foreach ($moduleCols as $k => $col) {
            if (!Schema::hasColumn($table, $col)) {
                $perModuleSummary[$k]['current_batches'] = [];
                continue;
            }
            $q = (clone $base)->whereNotNull($col)->whereNull('tgl_secondary_pack_2');
            $next = $nextColMap[$k] ?? null;
            if ($next && Schema::hasColumn($table, $next)) $q = $q->whereNull($next);
            if (Schema::hasColumn($table, $col)) $q = $q->orderByDesc($col);
            else $q = $q->orderByDesc('updated_at');

            $rows = $q->limit(5)
                ->get(['id','nama_produk','wo_date',$col, 'tgl_secondary_pack_1','tgl_secondary_pack_2', $woCol ?: 'id'])
                ->map(function($r) use ($woCol, $col) {
                    $moduleTime = $r->{$col} ?? null;
                    $minutes = null;
                    try {
                        if ($moduleTime) {
                            $minutes = Carbon::parse($moduleTime)->diffInMinutes(Carbon::now());
                        }
                    } catch (\Exception $e) { $minutes = null; }

                    return [
                        'id' => $r->id,
                        'ident' => $woCol ? ($r->{$woCol} ?? $r->id) : $r->id,
                        'produk' => $r->nama_produk ?? '-',
                        'module_time' => $moduleTime,
                        'wo_date' => $r->wo_date ?? null,
                        'current_step' => $this->lastStepLabelFromBatch($r),
                        'minutes' => $minutes,
                    ];
                })->toArray();
            $perModuleSummary[$k]['current_batches'] = $rows;
        }

        // ===== BATCH LIST for selector (limit latest 200) =====
        $batchListQuery = ProduksiBatch::query();
        if ($fromDate) {
            if (Schema::hasColumn($table,'wo_date')) $batchListQuery->whereDate('wo_date','>=',$fromDate);
            else $batchListQuery->whereDate('created_at','>=',$fromDate);
        }
        if ($toDate) {
            if (Schema::hasColumn($table,'wo_date')) $batchListQuery->whereDate('wo_date','<=',$toDate);
            else $batchListQuery->whereDate('created_at','<=',$toDate);
        }
        $batchList = $batchListQuery->orderByDesc('id')->limit(200)
            ->get(['id', $woCol ?: 'id as idcol', 'nama_produk', 'wo_date'])
            ->map(function($r) use ($woCol) {
                return [
                    'value' => $woCol ? ($r->{$woCol} ?? $r->id) : $r->id,
                    'label' => ($r->nama_produk ? substr($r->nama_produk,0,40).' - ' : '') . ($r->wo_date ? Carbon::parse($r->wo_date)->format('Y-m-d') : $r->id)
                ];
            })->toArray();

        // ===== BATCH STATUS TABLE (for visible batch rows filtered by date/batch)
        // We will show: first_step, last_step, duration (from first step -> secondary2 OR now)
        $batchRows = (clone $base)
            ->orderByDesc('id')
            ->limit(200)
            ->get([
                'id','nama_produk','wo_date','tipe_alur',
                'tgl_weighing','tgl_mixing','tgl_tableting','tgl_coating','tgl_capsule_filling',
                'tgl_primary_pack','tgl_secondary_pack_1','tgl_secondary_pack_2','updated_at'
            ]);

        $batchStatus = [];
        foreach ($batchRows as $b) {
            // compute first step label + time (earliest of started columns)
            $firstTime = $this->firstStepTimeFromBatch($b);
            $firstLabel = $this->firstStepLabelFromBatch($b);

            // last step label + time (latest downstream)
            $lastLabel = $this->lastStepLabelFromBatch($b);
            $lastTime = $this->lastStepTimeFromBatch($b);

            // duration: from firstTime -> secondary2 if finished, else now
            $endTime = null;
            if (isset($b->tgl_secondary_pack_2) && !is_null($b->tgl_secondary_pack_2)) {
                $endTime = $b->tgl_secondary_pack_2;
            } elseif (isset($b->tgl_secondary_pack_1) && !is_null($b->tgl_secondary_pack_1) && Schema::hasColumn($table, 'tgl_secondary_pack_2')) {
                // if secondary2 column exists but not filled, still treat end as now
                $endTime = Carbon::now();
            } else {
                $endTime = Carbon::now();
            }

            $durationMinutes = null;
            try {
                if ($firstTime) {
                    $durationMinutes = Carbon::parse($firstTime)->diffInMinutes(Carbon::parse($endTime));
                }
            } catch (\Exception $e) {
                $durationMinutes = null;
            }

            $batchStatus[] = [
                'id' => $b->id,
                'produk' => $b->nama_produk,
                'wo_date' => $b->wo_date,
                'first_step' => $firstLabel,
                'first_time' => $firstTime,
                'last_step' => $lastLabel,
                'last_time' => $lastTime,
                'minutes' => $durationMinutes,
            ];
        }

        // ===== SAFETY GUARDS =====
        $perModule = $perModule ?? [];
        $perModuleRekon = $perModuleRekon ?? [];
        $moduleInProgressCounts = $moduleInProgressCounts ?? [];
        $perModuleSummary = $perModuleSummary ?? [];
        $monthlyDone = $monthlyDone ?? [];
        $monthlyTotal = $monthlyTotal ?? [];
        $batchList = $batchList ?? [];
        $batchStatus = $batchStatus ?? [];

        // return view -- include rekonLabels + rekonData so blade can render overview
        return view('home.dashboard', compact(
            'bulan','tahun','year','fromDate','toDate','selectedBatch','selectedModule',
            'masterProdukCount','totalBatch','batchAktifCount','batchSelesaiCount','totalQtyBatch',
            'months','barDone','barInProgress','barDimusnahkan',
            'moduleKPI','batchInKarantina',
            'chartProdukLabels','chartProdukData','chartDateLabels','chartDateData','chartMode',
            'progressByProduk',
            'rekonLabels','rekonData','perModule','monthlyDone','monthlyTotal','moduleCols',
            'perModuleRekon','moduleInProgressCounts','perModuleSummary',
            'batchList','batchStatus'
        ));
    }

    private function lastStepLabelFromRow($r): string
    {
        if ((int) ($r->done_secondary2 ?? 0) > 0) return 'Selesai (Secondary 2)';
        if ((int) ($r->done_secondary1 ?? 0) > 0) return 'Secondary Pack';
        if ((int) ($r->done_primary ?? 0) > 0)    return 'Primary Pack';
        if ((int) ($r->done_coating ?? 0) > 0)    return 'Coating';
        if ((int) ($r->done_tableting ?? 0) > 0)  return 'Tableting';
        if ((int) ($r->done_mixing ?? 0) > 0)     return 'Mixing';
        if ((int) ($r->done_weighing ?? 0) > 0)   return 'Weighing';
        return 'Belum mulai';
    }

    private function lastStepLabelFromBatch($b): string
    {
        if (!is_null($b->tgl_secondary_pack_2)) return 'Selesai (Secondary 2)';
        if (!is_null($b->tgl_secondary_pack_1)) return 'Secondary Pack';
        if (!is_null($b->tgl_primary_pack)) return 'Primary Pack';
        if (!is_null($b->tgl_coating)) return 'Coating';
        if (!is_null($b->tgl_tableting)) return 'Tableting';
        if (!is_null($b->tgl_mixing)) return 'Mixing';
        if (!is_null($b->tgl_weighing)) return 'Weighing';
        return 'Belum mulai';
    }

    /**
     * Return the timestamp of the last step/time a batch had (best-effort) or null
     */
    private function lastStepTimeFromBatch($b)
    {
        // order: most downstream -> upstream (pick latest non-null)
        $cols = ['tgl_secondary_pack_2','tgl_secondary_pack_1','tgl_primary_pack','tgl_coating','tgl_tableting','tgl_mixing','tgl_weighing'];
        foreach ($cols as $c) {
            if (isset($b->{$c}) && !is_null($b->{$c})) return $b->{$c};
        }
        // fallback: updated_at if available
        if (isset($b->updated_at)) return $b->updated_at;
        return null;
    }

    /**
     * Return the earliest non-null step time from production steps (best-effort) or null
     */
    private function firstStepTimeFromBatch($b)
    {
        $cols = ['tgl_weighing','tgl_mixing','tgl_tableting','tgl_coating','tgl_capsule_filling','tgl_primary_pack','tgl_secondary_pack_1','tgl_secondary_pack_2'];
        $times = [];
        foreach ($cols as $c) {
            if (isset($b->{$c}) && !is_null($b->{$c})) {
                try {
                    $times[] = Carbon::parse($b->{$c});
                } catch (\Exception $e) {
                    // ignore parse error
                }
            }
        }
        if (count($times) === 0) {
            // fallback to updated_at if present
            if (isset($b->updated_at) && !is_null($b->updated_at)) return $b->updated_at;
            return null;
        }
        // find earliest
        usort($times, function($a,$b){ return $a->lt($b) ? -1 : 1; });
        return $times[0]->toDateTimeString();
    }

    /**
     * Return the label (step name) corresponding to the earliest non-null step time, or 'Belum mulai'
     */
    private function firstStepLabelFromBatch($b): string
    {
        // we want to map the earliest timestamp to the step name
        $cols = ['tgl_weighing'=>'Weighing','tgl_mixing'=>'Mixing','tgl_tableting'=>'Tableting','tgl_coating'=>'Coating','tgl_capsule_filling'=>'Capsule Filling','tgl_primary_pack'=>'Primary Pack','tgl_secondary_pack_1'=>'Secondary Pack','tgl_secondary_pack_2'=>'Secondary 2'];
        $found = [];
        foreach ($cols as $col => $label) {
            if (isset($b->{$col}) && !is_null($b->{$col})) {
                try {
                    $found[] = ['col'=>$col,'label'=>$label,'time'=>Carbon::parse($b->{$col})];
                } catch (\Exception $e) {
                    // ignore
                }
            }
        }
        if (count($found) === 0) {
            if (isset($b->updated_at) && !is_null($b->updated_at)) return 'Updated';
            return 'Belum mulai';
        }
        usort($found, function($a,$b){ return $a['time']->lt($b['time']) ? -1 : 1; });
        return $found[0]['label'] ?? 'Belum mulai';
    }
}
