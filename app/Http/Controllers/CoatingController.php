<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CoatingController extends Controller
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
     * INDEX – batch BELUM selesai Coating
     * Flow: Tableting -> QC Tablet Release -> Coating
     * =======================================================*/
    public function index(Request $request)
    {
        $search  = trim((string) $request->get('q', ''));
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = max(1, (int) $request->get('per_page', 25));

        $query = ProduksiBatch::with('produksi')
            ->whereNotNull('tgl_tableting')
            ->whereNotNull('tablet_signed_at') // ✅ QC Tablet release
            ->whereNull('tgl_coating');        // ✅ belum selesai

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
            ->orderByRaw('CASE WHEN tgl_mulai_coating IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('tgl_mulai_coating')
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

        return view('produksi.coating.index', compact(
            'batches',
            'search',
            'bulan',
            'tahun',
            'perPage'
        ));
    }

    /* =========================================================
     * HISTORY – batch SUDAH selesai Coating
     * =======================================================*/
    public function history(Request $request)
    {
        $search  = trim((string) $request->get('q', ''));
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = max(1, (int) $request->get('per_page', 25));

        $query = ProduksiBatch::with('produksi')
            ->whereNotNull('tgl_tableting')
            ->whereNotNull('tablet_signed_at')
            ->whereNotNull('tgl_coating');

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
            ->orderByDesc('tgl_coating')
            ->paginate($perPage)
            ->withQueryString();

        // optional: tambahkan paused_by_name di history juga (meskipun history biasanya selesai)
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

        return view('produksi.coating.history', compact(
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

        if (! $batch->tgl_tableting) {
            return back()->withErrors(['coating' => "Tableting belum selesai untuk batch {$kode}."]);
        }

        if (! $batch->tablet_signed_at) {
            return back()->withErrors(['coating' => "QC Tablet belum Release untuk batch {$kode}."]);
        }

        if ($batch->tgl_coating) {
            return back()->withErrors(['coating' => "Batch {$kode} sudah selesai Coating."]);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['coating' => "Batch {$kode} sedang HOLD. Selesaikan dari modul Holding dulu."]);
        }

        if ($batch->is_paused) {
            return back()->withErrors(['coating' => "Batch {$kode} sedang PAUSE. Silakan RESUME dulu."]);
        }

        if ($batch->tgl_mulai_coating) {
            return back()->with('success', "Coating sudah pernah dimulai untuk batch {$kode}.")
                ->with('success_batch', $kode);
        }

        $batch->tgl_mulai_coating = now();
        $batch->status_proses     = 'COATING_MULAI';
        $batch->save();

        return back()->with('success', "Coating dimulai untuk batch {$kode}.")
            ->with('success_batch', $kode);
    }

    /* =========================================================
     * STOP + INPUT REKON (ANGKA BIASA, TANPA CATATAN)
     * =======================================================*/
    public function stop(Request $request, ProduksiBatch $batch)
    {
        $kode = $this->batchCode($batch);

        $data = $request->validate([
            'rekon_qty' => ['required', 'integer', 'min:0'],
        ], [
            'rekon_qty.required' => 'Rekon wajib diisi.',
            'rekon_qty.integer'  => 'Rekon harus angka.',
            'rekon_qty.min'      => 'Rekon minimal 0.',
        ]);

        if (! $batch->tgl_tableting) {
            return back()->withErrors(['coating' => "Tableting belum selesai untuk batch {$kode}."]);
        }

        if (! $batch->tablet_signed_at) {
            return back()->withErrors(['coating' => "QC Tablet belum Release untuk batch {$kode}."]);
        }

        if (! $batch->tgl_mulai_coating) {
            return back()->withErrors(['coating' => "Coating belum dimulai untuk batch {$kode}."]);
        }

        if ($batch->is_paused) {
            return back()->withErrors(['coating' => "Batch {$kode} sedang PAUSE. Silakan RESUME dulu."]);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['coating' => "Batch {$kode} sedang HOLD. Selesaikan dari modul Holding dulu."]);
        }

        if ($batch->tgl_coating) {
            return back()->with('success', "Coating sudah selesai sebelumnya untuk batch {$kode}.")
                ->with('success_batch', $kode);
        }

        $batch->forceFill([
            'tgl_coating'       => now(),
            'status_proses'     => 'COATING_SELESAI',

            // ✅ simpan rekon coating (angka biasa)
            'coating_rekon_qty' => (int) $data['rekon_qty'],
            'coating_rekon_at'  => now(),
            'coating_rekon_by'  => Auth::id(),
        ])->save();

        return back()->with('success', "Coating selesai untuk batch {$kode}. Rekon: {$data['rekon_qty']}.")
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
        ]);

        if ($batch->tgl_coating) {
            return back()->withErrors(['coating' => "Batch {$kode} sudah selesai Coating, tidak bisa PAUSE."]);
        }

        if (! $batch->tgl_mulai_coating) {
            return back()->withErrors(['coating' => "Coating belum dimulai untuk batch {$kode}, tidak bisa PAUSE."]);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['coating' => "Batch {$kode} sedang HOLD."]);
        }

        if ($batch->is_paused) {
            return back()->with('success', "Batch {$kode} sudah PAUSE.")
                ->with('success_batch', $kode);
        }

        $batch->is_paused          = true;
        $batch->paused_stage       = 'COATING';
        $batch->paused_reason      = $data['paused_reason'];
        $batch->paused_prev_status = $batch->status_proses;
        $batch->paused_at          = now();
        $batch->paused_by          = Auth::id();

        $batch->status_proses      = 'COATING_PAUSED';
        $batch->save();

        return back()->with('success', "Coating di-PAUSE untuk batch {$kode}. Alasan: {$batch->paused_reason}")
            ->with('success_batch', $kode);
    }

    /* =========================================================
     * RESUME
     * =======================================================*/
    public function resume(ProduksiBatch $batch)
    {
        $kode = $this->batchCode($batch);

        if (! $batch->is_paused) {
            return back()->withErrors(['coating' => "Batch {$kode} tidak dalam kondisi PAUSE."]);
        }

        if (($batch->paused_stage ?? null) !== 'COATING') {
            return back()->withErrors(['coating' => "PAUSE batch {$kode} bukan di tahap Coating."]);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['coating' => "Batch {$kode} sedang HOLD."]);
        }

        $prev = $batch->paused_prev_status ?: 'COATING_MULAI';

        $this->clearPauseFields($batch);
        $batch->status_proses = $prev;
        $batch->save();

        return back()->with('success', "Coating di-RESUME untuk batch {$kode}. Silakan lanjut proses.")
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

        if ($batch->tgl_coating) {
            return back()->withErrors(['coating' => "Batch {$kode} sudah selesai Coating, tidak bisa HOLD."]);
        }

        if ($batch->is_holding) {
            return back()->with('success', "Batch {$kode} sudah HOLD.")
                ->with('success_batch', $kode);
        }

        if ($batch->is_paused) {
            $this->clearPauseFields($batch);
        }

        $batch->is_holding          = true;
        $batch->holding_stage       = 'COATING';
        $batch->holding_return_to   = 'coating.index';
        $batch->holding_reason      = $data['holding_reason'];
        $batch->holding_note        = $data['holding_note'] ?? null;
        $batch->holding_prev_status = $batch->status_proses;
        $batch->holding_at          = now();
        $batch->holding_by          = Auth::id();

        $batch->status_proses       = 'HOLDING';
        $batch->save();

        return redirect()
            ->route('holding.index')
            ->with('success', "Batch {$kode} di-HOLD dari Coating. Alasan: {$batch->holding_reason}")
            ->with('success_batch', $kode);
    }

    /* =========================================================
     * SPLIT
     * =======================================================*/
    public function split(Request $request, ProduksiBatch $batch)
    {
        $kode = $this->batchCode($batch);

        // validasi minimum
        $produksi = $batch->produksi;
        if (! $produksi) {
            return back()->withErrors(['coating' => "Produk untuk batch {$kode} tidak ditemukan."]);
        }

        if (! ($produksi->is_split ?? false)) {
            return back()->withErrors(['coating' => "Produk {$produksi->nama_produk} tidak diizinkan untuk split."]);
        }

        if ($batch->tgl_mulai_coating) {
            return back()->withErrors(['coating' => "Batch {$kode} sudah mulai Coating, tidak bisa di-split."]);
        }

        if ($batch->tgl_coating) {
            return back()->withErrors(['coating' => "Batch {$kode} sudah selesai Coating, tidak bisa di-split."]);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['coating' => "Batch {$kode} sedang HOLD. Selesaikan dulu."]);
        }

        if ($batch->is_paused) {
            return back()->withErrors(['coating' => "Batch {$kode} sedang PAUSE. Resume dulu."]);
        }

        // optional: allow override suffix via request
        $suffix = (string) $request->input('suffix', '');
        $suffix = trim($suffix);
        if ($suffix === '') {
            $suffix = (string) ($produksi->split_suffix ?? 'Z');
        }
        $suffix = strtoupper($suffix);

        // create duplicate
        try {
            DB::beginTransaction();

            // use replicate but clear process timestamps that would mark as started/finished
            $new = $batch->replicate();

            // set kode/no with suffix if present
            if (!empty($batch->kode_batch)) {
                $new->kode_batch = $batch->kode_batch . $suffix;
            } else {
                $new->kode_batch = null;
            }

            if (!empty($batch->no_batch)) {
                $new->no_batch = $batch->no_batch . $suffix;
            } else {
                $new->no_batch = $new->no_batch ?? null;
            }

            // copy relevant fields so new batch appears in Coating queue:
            $new->tgl_tableting = $batch->tgl_tableting;
            $new->tablet_signed_at = $batch->tablet_signed_at;

            // reset coating related & holding/pause fields
            $new->tgl_mulai_coating = null;
            $new->tgl_coating = null;

            $new->is_paused = false;
            $new->paused_stage = null;
            $new->paused_reason = null;
            $new->paused_note = null;
            $new->paused_prev_status = null;
            $new->paused_at = null;
            $new->paused_by = null;

            $new->is_holding = false;
            $new->holding_stage = null;
            $new->holding_reason = null;
            $new->holding_note = null;
            $new->holding_prev_status = null;
            $new->holding_at = null;
            $new->holding_by = null;

            // set logical status for a replicated batch (ready for coating)
            $new->status_proses = 'QC_TABLET_RELEASE';

            // timestamps: let Eloquent set created_at/updated_at
            $new->created_at = now();
            $new->updated_at = now();

            $new->save();

            DB::commit();

            $newCode = $this->batchCode($new);
            return back()->with('success', "Batch {$kode} berhasil di-split. Batch baru: {$newCode}.")
                ->with('success_batch', $newCode);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['coating' => "Gagal melakukan split untuk batch {$kode}: " . $e->getMessage()]);
        }
    }
}
