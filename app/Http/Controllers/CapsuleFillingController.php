<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CapsuleFillingController extends Controller
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

        $query = ProduksiBatch::with('produksi')
            ->where('tipe_alur', 'KAPSUL')
            ->whereNotNull('tgl_mixing')
            ->whereNotNull('tgl_rilis_granul')
            ->whereNull('tgl_capsule_filling');

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
            ->orderByRaw('CASE WHEN tgl_mulai_capsule_filling IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('tgl_mulai_capsule_filling')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('produksi.capsule_filling.index', compact(
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

        $query = ProduksiBatch::with('produksi')
            ->where('tipe_alur', 'KAPSUL')
            ->whereNotNull('tgl_capsule_filling');

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
            ->orderByDesc('tgl_capsule_filling')
            ->paginate($perPage)
            ->withQueryString();

        return view('produksi.capsule_filling.history', compact(
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

        if ($batch->tipe_alur !== 'KAPSUL') {
            return back()->withErrors(['capsule_filling' => "Batch {$kode} bukan alur KAPSUL."]);
        }

        if (! $batch->tgl_mixing) {
            return back()->withErrors(['capsule_filling' => "Mixing belum selesai untuk batch {$kode}."]);
        }

        if (! $batch->tgl_rilis_granul) {
            return back()->withErrors(['capsule_filling' => "QC Granul belum Release untuk batch {$kode}."]);
        }

        if ($batch->tgl_capsule_filling) {
            return back()->withErrors(['capsule_filling' => "Batch {$kode} sudah selesai Capsule Filling."]);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['capsule_filling' => "Batch {$kode} sedang HOLD. Selesaikan dari modul Holding dulu."]);
        }

        if ($batch->is_paused) {
            return back()->withErrors(['capsule_filling' => "Batch {$kode} sedang PAUSE. Silakan RESUME dulu."]);
        }

        if ($batch->tgl_mulai_capsule_filling) {
            return back()->with('success', "Capsule Filling sudah pernah dimulai untuk batch {$kode}.")
                         ->with('success_batch', $kode);
        }

        $batch->tgl_mulai_capsule_filling = now();
        $batch->status_proses             = 'CAPSULE_FILLING_MULAI';
        $batch->save();

        return back()->with('success', "Capsule Filling dimulai untuk batch {$kode}.")
                     ->with('success_batch', $kode);
    }

    /* =========================================================
     * STOP + INPUT REKON
     * =======================================================*/
    public function stop(Request $request, ProduksiBatch $batch)
    {
        $kode = $this->batchCode($batch);

        $data = $request->validate([
            'rekon_qty'  => ['required', 'integer', 'min:0'],
            'rekon_note' => ['nullable', 'string', 'max:500'], // boleh dipakai / boleh dihapus
        ], [
            'rekon_qty.required' => 'Rekon wajib diisi.',
            'rekon_qty.integer'  => 'Rekon harus angka.',
            'rekon_qty.min'      => 'Rekon minimal 0.',
        ]);

        if (! $batch->tgl_mulai_capsule_filling) {
            return back()->withErrors(['capsule_filling' => "Capsule Filling belum dimulai untuk batch {$kode}."]);
        }

        if ($batch->is_paused) {
            return back()->withErrors(['capsule_filling' => "Batch {$kode} sedang PAUSE. Silakan RESUME dulu."]);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['capsule_filling' => "Batch {$kode} sedang HOLD. Selesaikan dari modul Holding dulu."]);
        }

        if ($batch->tgl_capsule_filling) {
            return back()->with('success', "Capsule Filling sudah selesai sebelumnya untuk batch {$kode}.")
                         ->with('success_batch', $kode);
        }

        $batch->forceFill([
            'tgl_capsule_filling'        => now(),
            'status_proses'              => 'CAPSULE_FILLING_SELESAI',

            'capsule_filling_rekon_qty'  => (int) $data['rekon_qty'],
            'capsule_filling_rekon_note' => $data['rekon_note'] ?? null,
            'capsule_filling_rekon_at'   => now(),
            'capsule_filling_rekon_by'   => Auth::id(),
        ])->save();

        return back()->with('success', "Capsule Filling selesai untuk batch {$kode}. Rekon: {$data['rekon_qty']}.")
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

        if ($batch->tgl_capsule_filling) {
            return back()->withErrors(['capsule_filling' => "Batch {$kode} sudah selesai Capsule Filling, tidak bisa PAUSE."]);
        }

        if (! $batch->tgl_mulai_capsule_filling) {
            return back()->withErrors(['capsule_filling' => "Capsule Filling belum dimulai untuk batch {$kode}, tidak bisa PAUSE."]);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['capsule_filling' => "Batch {$kode} sedang HOLD."]);
        }

        if ($batch->is_paused) {
            return back()->with('success', "Batch {$kode} sudah PAUSE.")
                         ->with('success_batch', $kode);
        }

        $batch->is_paused          = true;
        $batch->paused_stage       = 'CAPSULE_FILLING';
        $batch->paused_reason      = $data['paused_reason'];
        $batch->paused_note        = $data['paused_note'] ?? null;
        $batch->paused_prev_status = $batch->status_proses;
        $batch->paused_at          = now();
        $batch->paused_by          = Auth::id();

        $batch->status_proses      = 'CAPSULE_FILLING_PAUSED';
        $batch->save();

        return back()->with('success', "Capsule Filling di-PAUSE untuk batch {$kode}. Alasan: {$batch->paused_reason}")
                     ->with('success_batch', $kode);
    }

    /* =========================================================
     * RESUME
     * =======================================================*/
    public function resume(ProduksiBatch $batch)
    {
        $kode = $this->batchCode($batch);

        if (! $batch->is_paused) {
            return back()->withErrors(['capsule_filling' => "Batch {$kode} tidak dalam kondisi PAUSE."]);
        }

        if (($batch->paused_stage ?? null) !== 'CAPSULE_FILLING') {
            return back()->withErrors(['capsule_filling' => "PAUSE batch {$kode} bukan di tahap Capsule Filling."]);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['capsule_filling' => "Batch {$kode} sedang HOLD."]);
        }

        $prev = $batch->paused_prev_status ?: 'CAPSULE_FILLING_MULAI';

        $this->clearPauseFields($batch);
        $batch->status_proses = $prev;
        $batch->save();

        return back()->with('success', "Capsule Filling di-RESUME untuk batch {$kode}. Silakan lanjut proses.")
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

        if ($batch->tgl_capsule_filling) {
            return back()->withErrors(['capsule_filling' => "Batch {$kode} sudah selesai Capsule Filling, tidak bisa HOLD."]);
        }

        if ($batch->is_holding) {
            return back()->with('success', "Batch {$kode} sudah HOLD.")
                         ->with('success_batch', $kode);
        }

        if ($batch->is_paused) {
            $this->clearPauseFields($batch);
        }

        $batch->is_holding          = true;
        $batch->holding_stage       = 'CAPSULE_FILLING';
        $batch->holding_return_to   = 'capsule-filling.index';
        $batch->holding_reason      = $data['holding_reason'];
        $batch->holding_note        = $data['holding_note'] ?? null;
        $batch->holding_prev_status = $batch->status_proses;
        $batch->holding_at          = now();
        $batch->holding_by          = Auth::id();

        $batch->status_proses       = 'HOLDING';
        $batch->save();

        return redirect()
            ->route('holding.index')
            ->with('success', "Batch {$kode} di-HOLD dari Capsule Filling. Alasan: {$batch->holding_reason}")
            ->with('success_batch', $kode);
    }
}