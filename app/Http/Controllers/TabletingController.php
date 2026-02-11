<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TabletingController extends Controller
{
    /* =========================================================
     * HELPERS
     * =======================================================*/
    private function batchCode(ProduksiBatch $batch): string
    {
        $kode = trim((string) ($batch->kode_batch ?? ''));
        if ($kode !== '') return $kode;

        $no = trim((string) ($batch->no_batch ?? ''));
        return $no !== '' ? $no : ('#' . $batch->id);
    }

    private function applyNotHolding($query)
    {
        if (method_exists(new ProduksiBatch, 'scopeNotHolding')) {
            return $query->notHolding();
        }

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

    /* =========================================================
     * INDEX (AKTIF)
     * =======================================================*/
    public function index(Request $request)
    {
        $search  = trim((string) $request->get('q', ''));
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = max(1, (int) $request->get('per_page', 25));

        $alurTablet = ['TABLET_NON_SALUT', 'TABLET_SALUT'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurTablet)
            ->whereNotNull('tgl_mixing')
            ->whereNotNull('tgl_rilis_granul')
            ->whereNull('tgl_tableting');

        $query = $this->applyNotHolding($query);

        if ($search !== '') {
            $query->where(function ($q2) use ($search) {
                $q2->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('no_batch', 'like', "%{$search}%")
                    ->orWhere('kode_batch', 'like', "%{$search}%");
            });
        }

        if ($bulan !== null && $bulan !== '' && $bulan !== 'all') {
            $query->where('bulan', (int) $bulan);
        }

        if ($tahun !== null && $tahun !== '') {
            $query->where('tahun', (int) $tahun);
        }

        $batches = $query
            ->orderByRaw('CASE WHEN tgl_mulai_tableting IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('tgl_mulai_tableting')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        // --- tambah informasi paused_by_name agar view menampilkan NAMA (bukan id) ---
        $pausedByIds = $batches->pluck('paused_by')->filter()->unique()->values()->all();
        $pausers = [];
        if (count($pausedByIds)) {
            $pausers = User::whereIn('id', $pausedByIds)->get()->keyBy('id');
        }

        $batches->getCollection()->transform(function ($b) use ($pausers) {
            $b->paused_by_name = null;
            if (!empty($b->paused_by) && isset($pausers[$b->paused_by])) {
                $b->paused_by_name = $pausers[$b->paused_by]->name;
            }
            return $b;
        });

        return view('produksi.tableting.index', compact(
            'batches',
            'search',
            'bulan',
            'tahun',
            'perPage'
        ));
    }

    /* =========================================================
     * HISTORY
     * =======================================================*/
    public function history(Request $request)
    {
        $search  = trim((string) $request->get('q', ''));
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = max(1, (int) $request->get('per_page', 25));

        $alurTablet = ['TABLET_NON_SALUT', 'TABLET_SALUT'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurTablet)
            ->whereNotNull('tgl_tableting');

        if ($search !== '') {
            $query->where(function ($q2) use ($search) {
                $q2->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('no_batch', 'like', "%{$search}%")
                    ->orWhere('kode_batch', 'like', "%{$search}%");
            });
        }

        if ($bulan !== null && $bulan !== '' && $bulan !== 'all') {
            $query->where('bulan', (int) $bulan);
        }

        if ($tahun !== null && $tahun !== '') {
            $query->where('tahun', (int) $tahun);
        }

        $batches = $query
            ->orderByDesc('tgl_tableting')
            ->paginate($perPage)
            ->withQueryString();

        // (optional) jika Anda ingin menampilkan nama paused_by juga di history yang punya paused (biasanya history = selesai, jadi umumnya tidak paused)
        $pausedByIds = $batches->pluck('paused_by')->filter()->unique()->values()->all();
        $pausers = [];
        if (count($pausedByIds)) {
            $pausers = User::whereIn('id', $pausedByIds)->get()->keyBy('id');
        }
        $batches->getCollection()->transform(function ($b) use ($pausers) {
            $b->paused_by_name = null;
            if (!empty($b->paused_by) && isset($pausers[$b->paused_by])) {
                $b->paused_by_name = $pausers[$b->paused_by]->name;
            }
            return $b;
        });

        return view('produksi.tableting.history', compact(
            'batches',
            'search',
            'bulan',
            'tahun',
            'perPage'
        ));
    }

    /* =========================================================
     * START
     * =======================================================*/
    public function start(ProduksiBatch $batch)
    {
        $kode = $this->batchCode($batch);

        $alurTablet = ['TABLET_NON_SALUT', 'TABLET_SALUT'];
        if (!in_array($batch->tipe_alur, $alurTablet, true)) {
            return back()->withErrors(['tableting' => "Batch {$kode} bukan alur TABLET."]);
        }

        if (! $batch->tgl_mixing) {
            return back()->withErrors(['tableting' => "Mixing belum selesai untuk batch {$kode}."]);
        }

        if (! $batch->tgl_rilis_granul) {
            return back()->withErrors(['tableting' => "QC Granul belum Release untuk batch {$kode}."]);
        }

        if ($batch->tgl_tableting) {
            return back()->withErrors(['tableting' => "Batch {$kode} sudah selesai Tableting."]);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['tableting' => "Batch {$kode} sedang HOLD. Selesaikan dari modul Holding dulu."]);
        }

        if ($batch->is_paused) {
            return back()->withErrors(['tableting' => "Batch {$kode} sedang PAUSE. Silakan RESUME dulu."]);
        }

        if ($batch->tgl_mulai_tableting) {
            return back()->with('success', "Tableting sudah pernah dimulai untuk batch {$kode}.")
                ->with('success_batch', $kode);
        }

        $batch->tgl_mulai_tableting = now();
        $batch->status_proses       = 'TABLETING_MULAI';
        $batch->save();

        return back()->with('success', "Tableting dimulai untuk batch {$kode}.")
            ->with('success_batch', $kode);
    }

    /* =========================================================
     * STOP + INPUT REKON (ANGKA BIASA, WAJIB)
     * =======================================================*/
    public function stop(Request $request, ProduksiBatch $batch)
    {
        $kode = $this->batchCode($batch);

        $data = $request->validate([
            'rekon_qty'  => ['required', 'integer', 'min:0'],
            'rekon_note' => ['nullable', 'string', 'max:500'],
        ], [
            'rekon_qty.required' => 'Rekon wajib diisi.',
            'rekon_qty.integer'  => 'Rekon harus angka.',
            'rekon_qty.min'      => 'Rekon minimal 0.',
        ]);

        if (! $batch->tgl_mulai_tableting) {
            return back()->withErrors(['tableting' => "Tableting belum dimulai untuk batch {$kode}."]);
        }

        if ($batch->is_paused) {
            return back()->withErrors(['tableting' => "Batch {$kode} sedang PAUSE. Silakan RESUME dulu."]);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['tableting' => "Batch {$kode} sedang HOLD. Selesaikan dari modul Holding dulu."]);
        }

        if ($batch->tgl_tableting) {
            return back()->with('success', "Tableting sudah selesai sebelumnya untuk batch {$kode}.")
                ->with('success_batch', $kode);
        }

        $batch->forceFill([
            'tgl_tableting'         => now(),
            'status_proses'         => 'TABLETING_SELESAI',

            // simpan rekon tableting (angka biasa)
            'tableting_rekon_qty'   => (int) $data['rekon_qty'],
            'tableting_rekon_note'  => $data['rekon_note'] ?? null,
            'tableting_rekon_at'    => now(),
            'tableting_rekon_by'    => Auth::id(),
        ])->save();

        return back()->with('success', "Tableting selesai untuk batch {$kode}. Rekon: {$data['rekon_qty']}.")
            ->with('success_batch', $kode);
    }

    /* =========================================================
     * PAUSE
     * =======================================================*/
    public function pause(Request $request, ProduksiBatch $batch)
    {
        $kode = $this->batchCode($batch);

        $data = $request->validate([
            'paused_reason' => ['required', 'string', 'min:3'],
            'paused_note'   => ['nullable', 'string'],
        ]);

        if ($batch->tgl_tableting) {
            return back()->withErrors(['tableting' => "Batch {$kode} sudah selesai Tableting, tidak bisa PAUSE."]);
        }

        if (! $batch->tgl_mulai_tableting) {
            return back()->withErrors(['tableting' => "Tableting belum dimulai untuk batch {$kode}, tidak bisa PAUSE."]);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['tableting' => "Batch {$kode} sedang HOLD."]);
        }

        if ($batch->is_paused) {
            return back()->with('success', "Batch {$kode} sudah PAUSE.")
                ->with('success_batch', $kode);
        }

        $batch->is_paused          = true;
        $batch->paused_stage       = 'TABLETING';
        $batch->paused_reason      = $data['paused_reason'];
        $batch->paused_note        = $data['paused_note'] ?? null;
        $batch->paused_prev_status = $batch->status_proses;
        $batch->paused_at          = now();
        $batch->paused_by          = Auth::id();

        $batch->status_proses      = 'TABLETING_PAUSED';
        $batch->save();

        return back()->with('success', "Tableting di-PAUSE untuk batch {$kode}. Alasan: {$batch->paused_reason}")
            ->with('success_batch', $kode);
    }

    /* =========================================================
     * RESUME
     * =======================================================*/
    public function resume(ProduksiBatch $batch)
    {
        $kode = $this->batchCode($batch);

        if (! $batch->is_paused) {
            return back()->withErrors(['tableting' => "Batch {$kode} tidak dalam kondisi PAUSE."]);
        }

        if (($batch->paused_stage ?? null) !== 'TABLETING') {
            return back()->withErrors(['tableting' => "PAUSE batch {$kode} bukan di tahap Tableting."]);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['tableting' => "Batch {$kode} sedang HOLD."]);
        }

        $prev = $batch->paused_prev_status ?: 'TABLETING_MULAI';

        $this->clearPauseFields($batch);
        $batch->status_proses = $prev;
        $batch->save();

        return back()->with('success', "Tableting di-RESUME untuk batch {$kode}. Silakan lanjut proses.")
            ->with('success_batch', $kode);
    }

    /* =========================================================
     * HOLD
     * =======================================================*/
    public function hold(Request $request, ProduksiBatch $batch)
    {
        $kode = $this->batchCode($batch);

        $data = $request->validate([
            'holding_reason' => ['required', 'string', 'min:3'],
            'holding_note'   => ['nullable', 'string'],
        ]);

        if ($batch->tgl_tableting) {
            return back()->withErrors(['tableting' => "Batch {$kode} sudah selesai Tableting, tidak bisa HOLD."]);
        }

        if ($batch->is_holding) {
            return back()->with('success', "Batch {$kode} sudah HOLD.")
                ->with('success_batch', $kode);
        }

        if ($batch->is_paused) {
            $this->clearPauseFields($batch);
        }

        $batch->is_holding          = true;
        $batch->holding_stage       = 'TABLETING';
        $batch->holding_return_to   = 'tableting.index';
        $batch->holding_reason      = $data['holding_reason'];
        $batch->holding_note        = $data['holding_note'] ?? null;
        $batch->holding_prev_status = $batch->status_proses;
        $batch->holding_at          = now();
        $batch->holding_by          = Auth::id();

        $batch->status_proses       = 'HOLDING';
        $batch->save();

        return redirect()
            ->route('holding.index')
            ->with('success', "Batch {$kode} di-HOLD dari Tableting. Alasan: {$batch->holding_reason}")
            ->with('success_batch', $kode);
    }

    /* =========================================================
     * CONFIRM (OPSIONAL)
     * =======================================================*/
    public function confirm(Request $request, ProduksiBatch $batch)
    {
        $kode = $this->batchCode($batch);

        $data = $request->validate([
            'tgl_mulai_tableting' => ['nullable', 'date'],
            'tgl_tableting'       => ['required', 'date'],
        ]);

        $start = $data['tgl_mulai_tableting'] ?? $data['tgl_tableting'];

        if ($batch->is_paused) {
            $this->clearPauseFields($batch);
        }

        $batch->tgl_mulai_tableting = $start;
        $batch->tgl_tableting       = $data['tgl_tableting'];
        $batch->status_proses       = 'TABLETING_SELESAI';
        $batch->save();

        return redirect()
            ->route('tableting.index')
            ->with('success', "Tableting berhasil dikonfirmasi untuk batch {$kode}.")
            ->with('success_batch', $kode);
    }
}
