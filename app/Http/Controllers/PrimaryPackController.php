<?php
// app/Http/Controllers/PrimaryPackController.php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrimaryPackController extends Controller
{
    /**
     * Alur:
     * - CLO: Weighing -> Primary Pack
     * - CAIRAN_LUAR/DRY_SYRUP: Mixing -> QC Ruahan (release) -> Primary Pack
     * - TABLET_NON_SALUT: Tableting -> QC Ruahan (release) -> Primary Pack
     * - TABLET_SALUT: Coating -> QC Ruahan (release) -> Primary Pack
     * - KAPSUL: Capsule Filling -> QC Ruahan (release) -> Primary Pack
     */
    private array $alurRuahanToPrimary = [
        'CAIRAN_LUAR',
        'DRY_SYRUP',
        'TABLET_NON_SALUT',
        'TABLET_SALUT',
        'KAPSUL',
    ];

    /* =========================================================
     * HELPERS
     * =======================================================*/
    private function userMustLogin(): void
    {
        if (!Auth::check()) abort(403, 'Silakan login.');
    }

    private function batchCode(ProduksiBatch $batch): string
    {
        $kode = trim((string) ($batch->kode_batch ?? ''));
        if ($kode !== '') return $kode;

        $no = trim((string) ($batch->no_batch ?? ''));
        return $no !== '' ? $no : ('#' . $batch->id);
    }

    private function applyNotHolding($query)
    {
        // kalau scope notHolding ada, pakai itu
        if (method_exists(new ProduksiBatch, 'scopeNotHolding')) {
            return $query->notHolding();
        }

        // fallback
        return $query->where(function ($q) {
            $q->whereNull('is_holding')->orWhere('is_holding', false);
        });
    }

    private function clearPauseFields(ProduksiBatch $batch): void
    {
        $batch->is_paused          = false;
        $batch->paused_stage       = null;
        $batch->paused_reason      = null;
        $batch->paused_note        = null;
        $batch->paused_prev_status = null;
        $batch->paused_at          = null;
        $batch->paused_by          = null;
    }

    private function isEligibleForPrimary(ProduksiBatch $batch): bool
    {
        $alur = (string) ($batch->tipe_alur ?? '');

        if (!empty($batch->tgl_primary_pack)) return false;

        // CLO: cukup weighing
        if ($alur === 'CLO') {
            return !empty($batch->tgl_weighing);
        }

        // selain CLO: wajib ruahan release
        if (in_array($alur, $this->alurRuahanToPrimary, true)) {
            if (empty($batch->tgl_rilis_ruahan)) return false;

            if ($alur === 'CAIRAN_LUAR' || $alur === 'DRY_SYRUP') {
                return !empty($batch->tgl_mixing);
            }

            if ($alur === 'TABLET_NON_SALUT') {
                return !empty($batch->tgl_tableting);
            }

            if ($alur === 'TABLET_SALUT') {
                return !empty($batch->tgl_coating);
            }

            if ($alur === 'KAPSUL') {
                return !empty($batch->tgl_capsule_filling);
            }
        }

        return false;
    }

    /* =========================================================
     * INDEX – data aktif Primary Pack (belum selesai)
     * =======================================================*/
    public function index(Request $request)
    {
        $this->userMustLogin();

        $q       = trim((string) $request->get('q', ''));
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = max(1, (int) $request->get('per_page', 25));

        $query = ProduksiBatch::with('produksi')
            ->whereNull('tgl_primary_pack')
            ->where(function ($p) {
                // CLO: weighing -> primary
                $p->where(function ($q) {
                    $q->where('tipe_alur', 'CLO')
                      ->whereNotNull('tgl_weighing');
                })

                // CAIRAN_LUAR / DRY_SYRUP: mixing + ruahan released
                ->orWhere(function ($q) {
                    $q->whereIn('tipe_alur', ['CAIRAN_LUAR', 'DRY_SYRUP'])
                      ->whereNotNull('tgl_mixing')
                      ->whereNotNull('tgl_rilis_ruahan');
                })

                // TABLET NON SALUT: tableting + ruahan released
                ->orWhere(function ($q) {
                    $q->where('tipe_alur', 'TABLET_NON_SALUT')
                      ->whereNotNull('tgl_tableting')
                      ->whereNotNull('tgl_rilis_ruahan');
                })

                // TABLET SALUT: coating + ruahan released
                ->orWhere(function ($q) {
                    $q->where('tipe_alur', 'TABLET_SALUT')
                      ->whereNotNull('tgl_coating')
                      ->whereNotNull('tgl_rilis_ruahan');
                })

                // KAPSUL: capsule filling + ruahan released
                ->orWhere(function ($q) {
                    $q->where('tipe_alur', 'KAPSUL')
                      ->whereNotNull('tgl_capsule_filling')
                      ->whereNotNull('tgl_rilis_ruahan');
                });
            });

        // sembunyikan yang HOLD
        $query = $this->applyNotHolding($query);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_produk', 'like', "%{$q}%")
                    ->orWhere('no_batch', 'like', "%{$q}%")
                    ->orWhere('kode_batch', 'like', "%{$q}%");
            });
        }

        if ($bulan !== null && $bulan !== '' && $bulan !== 'all') {
            $query->where('bulan', (int) $bulan);
        }

        if ($tahun !== null && $tahun !== '') {
            $query->where('tahun', (int) $tahun);
        }

        $rows = $query
            ->orderByRaw('CASE WHEN tgl_mulai_primary_pack IS NULL THEN 1 ELSE 0 END ASC')
            ->orderByDesc('tgl_mulai_primary_pack')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        // --- tambahkan paused_by_name agar view bisa menampilkan nama user (bukan id) ---
        $pausedByIds = $rows->pluck('paused_by')->filter()->unique()->values()->all();
        $pausers = [];
        if (count($pausedByIds)) {
            $pausers = User::whereIn('id', $pausedByIds)->get()->keyBy('id');
        }

        $rows->getCollection()->transform(function ($r) use ($pausers) {
            $r->paused_by_name = null;
            if (!empty($r->paused_by) && isset($pausers[$r->paused_by])) {
                $r->paused_by_name = $pausers[$r->paused_by]->name;
            }
            return $r;
        });

        return view('primary_pack.index', compact('rows', 'q', 'bulan', 'tahun', 'perPage'));
    }

    /* =========================================================
     * HISTORY – yang sudah selesai primary
     * =======================================================*/
    public function history(Request $request)
    {
        $this->userMustLogin();

        $q       = trim((string) $request->get('q', ''));
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = max(1, (int) $request->get('per_page', 25));

        $query = ProduksiBatch::with('produksi')
            ->whereNotNull('tgl_primary_pack');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_produk', 'like', "%{$q}%")
                    ->orWhere('no_batch', 'like', "%{$q}%")
                    ->orWhere('kode_batch', 'like', "%{$q}%");
            });
        }

        if ($bulan !== null && $bulan !== '' && $bulan !== 'all') {
            $query->where('bulan', (int) $bulan);
        }

        if ($tahun !== null && $tahun !== '') {
            $query->where('tahun', (int) $tahun);
        }

        $rows = $query
            ->orderByDesc('tgl_primary_pack')
            ->paginate($perPage)
            ->withQueryString();

        // tambahkan paused_by_name di history (opsional)
        $pausedByIds = $rows->pluck('paused_by')->filter()->unique()->values()->all();
        $pausers = [];
        if (count($pausedByIds)) {
            $pausers = User::whereIn('id', $pausedByIds)->get()->keyBy('id');
        }
        $rows->getCollection()->transform(function ($r) use ($pausers) {
            $r->paused_by_name = null;
            if (!empty($r->paused_by) && isset($pausers[$r->paused_by])) {
                $r->paused_by_name = $pausers[$r->paused_by]->name;
            }
            return $r;
        });

        return view('primary_pack.history', compact('rows', 'q', 'bulan', 'tahun', 'perPage'));
    }

    /* =========================================================
     * START
     * =======================================================*/
    public function start(Request $request, ProduksiBatch $batch)
    {
        $this->userMustLogin();
        $kode = $this->batchCode($batch);

        if (!empty($batch->tgl_primary_pack)) {
            return back()->withErrors(['primary' => "Batch {$kode} sudah selesai Primary Pack."]);
        }

        if (!empty($batch->tgl_mulai_primary_pack)) {
            return back()->withErrors(['primary' => "Primary Pack batch {$kode} sudah dimulai."]);
        }

        if (($batch->is_holding ?? false) === true) {
            return back()->withErrors(['primary' => "Batch {$kode} sedang HOLD. Selesaikan dari modul Holding."]);
        }

        if (($batch->is_paused ?? false) === true) {
            return back()->withErrors(['primary' => "Batch {$kode} sedang PAUSE. Silakan RESUME dulu."]);
        }

        if (!$this->isEligibleForPrimary($batch)) {
            return back()->withErrors(['primary' => "Batch {$kode} belum memenuhi urutan proses untuk masuk Primary Pack."]);
        }

        $batch->tgl_mulai_primary_pack = now();
        $batch->status_proses          = 'PRIMARY_PACK_MULAI';
        $batch->save();

        return back()->with('success', "Primary Pack dimulai untuk batch {$kode}.")
                     ->with('success_batch', $kode);
    }

    /* =========================================================
     * STOP + INPUT REKON (WAJIB, TANPA CATATAN)
     * =======================================================*/
    public function stop(Request $request, ProduksiBatch $batch)
    {
        $this->userMustLogin();
        $kode = $this->batchCode($batch);

        $data = $request->validate([
            'rekon_qty' => ['required', 'integer', 'min:0'],
        ], [
            'rekon_qty.required' => 'Rekon wajib diisi.',
            'rekon_qty.integer'  => 'Rekon harus angka (integer).',
            'rekon_qty.min'      => 'Rekon minimal 0.',
        ]);

        if (empty($batch->tgl_mulai_primary_pack)) {
            return back()->withErrors(['primary' => "Tidak bisa STOP. Primary Pack belum dimulai untuk batch {$kode}."]);
        }

        if (!empty($batch->tgl_primary_pack)) {
            return back()->withErrors(['primary' => "Primary Pack batch {$kode} sudah selesai."]);
        }

        if (($batch->is_paused ?? false) === true) {
            return back()->withErrors(['primary' => "Batch {$kode} sedang PAUSE. Silakan RESUME dulu."]);
        }

        if (($batch->is_holding ?? false) === true) {
            return back()->withErrors(['primary' => "Batch {$kode} sedang HOLD. Selesaikan dari modul Holding."]);
        }

        $batch->forceFill([
            'tgl_primary_pack'        => now(),
            'status_proses'           => 'PRIMARY_PACK_SELESAI',

            // simpan rekon primary pack (angka biasa)
            'primary_pack_rekon_qty'  => (int) $data['rekon_qty'],
            'primary_pack_rekon_at'   => now(),
            'primary_pack_rekon_by'   => Auth::id(),
        ])->save();

        return back()->with('success', "Primary Pack selesai untuk batch {$kode}. Rekon: {$data['rekon_qty']}.")
                     ->with('success_batch', $kode);
    }

    /* =========================================================
     * PAUSE
     * =======================================================*/
    public function pause(Request $request, ProduksiBatch $batch)
    {
        $this->userMustLogin();
        $kode = $this->batchCode($batch);

        $data = $request->validate([
            'paused_reason' => ['required', 'string', 'min:3'],
        ]);

        if (!empty($batch->tgl_primary_pack)) {
            return back()->withErrors(['primary' => "Batch {$kode} sudah selesai Primary Pack, tidak bisa PAUSE."]);
        }

        if (empty($batch->tgl_mulai_primary_pack)) {
            return back()->withErrors(['primary' => "Primary Pack belum dimulai untuk batch {$kode}, tidak bisa PAUSE."]);
        }

        if (($batch->is_holding ?? false) === true) {
            return back()->withErrors(['primary' => "Batch {$kode} sedang HOLD."]);
        }

        if (($batch->is_paused ?? false) === true) {
            return back()->with('success', "Batch {$kode} sudah PAUSE.")
                         ->with('success_batch', $kode);
        }

        $batch->is_paused          = true;
        $batch->paused_stage       = 'PRIMARY_PACK';
        $batch->paused_reason      = $data['paused_reason'];
        $batch->paused_note        = null;
        $batch->paused_prev_status = $batch->status_proses;
        $batch->paused_at          = now();
        $batch->paused_by          = Auth::id();

        $batch->status_proses      = 'PRIMARY_PACK_PAUSED';
        $batch->save();

        return back()->with('success', "Primary Pack di-PAUSE untuk batch {$kode}. Alasan: {$batch->paused_reason}")
                     ->with('success_batch', $kode);
    }

    /* =========================================================
     * RESUME
     * =======================================================*/
    public function resume(ProduksiBatch $batch)
    {
        $this->userMustLogin();
        $kode = $this->batchCode($batch);

        if (!($batch->is_paused ?? false)) {
            return back()->withErrors(['primary' => "Batch {$kode} tidak dalam kondisi PAUSE."]);
        }

        if (($batch->paused_stage ?? null) !== 'PRIMARY_PACK') {
            return back()->withErrors(['primary' => "PAUSE batch {$kode} bukan di tahap Primary Pack."]);
        }

        if (($batch->is_holding ?? false) === true) {
            return back()->withErrors(['primary' => "Batch {$kode} sedang HOLD."]);
        }

        $prev = $batch->paused_prev_status ?: 'PRIMARY_PACK_MULAI';

        $this->clearPauseFields($batch);
        $batch->status_proses = $prev;
        $batch->save();

        return back()->with('success', "Primary Pack di-RESUME untuk batch {$kode}. Silakan lanjut proses.")
                     ->with('success_batch', $kode);
    }

    /* =========================================================
     * HOLD
     * =======================================================*/
    public function hold(Request $request, ProduksiBatch $batch)
    {
        $this->userMustLogin();
        $kode = $this->batchCode($batch);

        $data = $request->validate([
            'holding_reason' => ['required', 'string', 'min:3'],
            'holding_note'   => ['nullable', 'string'],
        ]);

        if (!empty($batch->tgl_primary_pack)) {
            return back()->withErrors(['primary' => "Batch {$kode} sudah selesai Primary Pack, tidak bisa HOLD."]);
        }

        if (($batch->is_holding ?? false) === true) {
            return back()->with('success', "Batch {$kode} sudah HOLD.")
                         ->with('success_batch', $kode);
        }

        // kalau lagi pause, bersihin dulu biar gak nyangkut
        if (($batch->is_paused ?? false) === true) {
            $this->clearPauseFields($batch);
        }

        $batch->is_holding          = true;
        $batch->holding_stage       = 'PRIMARY_PACK';
        $batch->holding_return_to   = 'primary-pack.index';
        $batch->holding_reason      = $data['holding_reason'];
        $batch->holding_note        = $data['holding_note'] ?? null;
        $batch->holding_prev_status = $batch->status_proses;
        $batch->holding_at          = now();
        $batch->holding_by          = Auth::id();

        $batch->status_proses       = 'HOLDING';
        $batch->save();

        return redirect()
            ->route('holding.index')
            ->with('success', "Batch {$kode} di-HOLD dari Primary Pack. Alasan: {$batch->holding_reason}")
            ->with('success_batch', $kode);
    }
}
