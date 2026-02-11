<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class SecondaryPackController extends Controller
{
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

    /**
     * INDEX – batch yang butuh SECONDARY PACK
     */
    public function index(Request $request)
    {
        $this->userMustLogin();

        $q       = trim((string) $request->get('q', ''));
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = max(1, (int) $request->get('per_page', 25));

        $query = ProduksiBatch::with('produksi')
            ->whereNotNull('tgl_primary_pack')
            ->whereNull('tgl_secondary_pack_1')
            ->where(function ($qMain) {
                $qMain
                    ->where(function ($q1) {
                        $q1->whereIn('tipe_alur', ['CLO', 'CAIRAN_LUAR'])
                           ->whereNotNull('tgl_rilis_ruahan_akhir');
                    })
                    ->orWhere(function ($q2) {
                        $q2->where(function ($w) {
                            $w->whereNull('tipe_alur')
                              ->orWhereNotIn('tipe_alur', ['CLO', 'CAIRAN_LUAR']);
                        });
                    });
            });

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
            ->orderByRaw('CASE WHEN tgl_mulai_secondary_pack_1 IS NULL THEN 1 ELSE 0 END ASC')
            ->orderByDesc('tgl_mulai_secondary_pack_1')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('secondary_pack.index', compact('rows', 'q', 'bulan', 'tahun', 'perPage'));
    }

    /**
     * HISTORY – batch yang sudah selesai SECONDARY PACK 1
     */
    public function history(Request $request)
    {
        $this->userMustLogin();

        $q       = trim((string) $request->get('q', ''));
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = max(1, (int) $request->get('per_page', 25));

        $query = ProduksiBatch::with('produksi')
            ->whereNotNull('tgl_secondary_pack_1');

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
            ->orderByDesc('tgl_secondary_pack_1')
            ->orderByDesc('tgl_mulai_secondary_pack_1')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('secondary_pack.history', compact('rows', 'q', 'bulan', 'tahun', 'perPage'));
    }

    /**
     * START – mulai Secondary Pack 1 (realtime datetime)
     */
    public function start(Request $request, ProduksiBatch $batch)
    {
        $this->userMustLogin();
        $kode = $this->batchCode($batch);

        if (!empty($batch->tgl_secondary_pack_1)) {
            return back()->withErrors(['secondary' => "Secondary Pack sudah selesai ({$kode})."]);
        }

        if (!empty($batch->tgl_mulai_secondary_pack_1)) {
            return back()->withErrors(['secondary' => "Secondary Pack sudah dimulai ({$kode})."]);
        }

        if (empty($batch->tgl_primary_pack)) {
            return back()->withErrors(['secondary' => "Secondary Pack tidak bisa dimulai sebelum Primary Pack selesai ({$kode})."]);
        }

        if (($batch->tipe_alur ?? null) === 'CAIRAN_LUAR' && empty($batch->tgl_rilis_ruahan_akhir)) {
            return back()->withErrors(['secondary' => "Batch CAIRAN_LUAR harus lewat QC Ruahan Akhir dulu ({$kode})."]);
        }

        if (($batch->is_holding ?? false) === true) {
            return back()->withErrors(['secondary' => "Batch sedang HOLD ({$kode})."]);
        }

        if (($batch->is_paused ?? false) === true) {
            return back()->withErrors(['secondary' => "Batch sedang PAUSE. RESUME dulu ({$kode})."]);
        }

        $batch->update([
            'tgl_mulai_secondary_pack_1' => now(),
            'status_proses'              => 'SECONDARY_PACK_MULAI',
        ]);

        return back()->with('success', "Secondary Pack dimulai ({$kode}).")->with('success_batch', $kode);
    }

    /**
     * STOP – selesai Secondary Pack 1 + input Rekon (wajib),
     * lanjut ke form Qty Batch.
     */
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

        if (empty($batch->tgl_mulai_secondary_pack_1)) {
            return back()->withErrors(['secondary' => "Tidak bisa Stop sebelum Start ({$kode})."]);
        }

        if (($batch->is_paused ?? false) === true) {
            return back()->withErrors(['secondary' => "Batch sedang PAUSE. RESUME dulu ({$kode})."]);
        }

        if (($batch->is_holding ?? false) === true) {
            return back()->withErrors(['secondary' => "Batch sedang HOLD ({$kode})."]);
        }

        // kalau sudah selesai, tetap arahkan ke qty form
        if (!empty($batch->tgl_secondary_pack_1)) {
            return redirect()
                ->route('secondary-pack.qty.form', $batch->id)
                ->with('success', "Secondary Pack sudah selesai ({$kode}). Silakan cek / lengkapi Qty Batch.")
                ->with('success_batch', $kode);
        }

        $batch->forceFill([
            'tgl_secondary_pack_1'        => now(),
            'status_proses'              => 'SECONDARY_PACK_SELESAI',
            'secondary_pack_rekon_qty'   => (int) $data['rekon_qty'],
            'secondary_pack_rekon_at'    => now(),
            'secondary_pack_rekon_by'    => Auth::id(),
        ])->save();

        return redirect()
            ->route('secondary-pack.qty.form', $batch->id)
            ->with('success', "Secondary Pack selesai ({$kode}). Rekon: {$data['rekon_qty']}. Lanjut input Qty Batch.")
            ->with('success_batch', $kode);
    }

    /**
     * PAUSE – tetap di modul secondary-pack
     */
    public function pause(Request $request, ProduksiBatch $batch)
    {
        $this->userMustLogin();
        $kode = $this->batchCode($batch);

        $data = $request->validate([
            'paused_reason' => ['required', 'string', 'min:3'],
        ]);

        if (!empty($batch->tgl_secondary_pack_1)) {
            return back()->withErrors(['secondary' => "Sudah selesai Secondary Pack, tidak bisa PAUSE ({$kode})."]);
        }

        if (empty($batch->tgl_mulai_secondary_pack_1)) {
            return back()->withErrors(['secondary' => "Secondary Pack belum dimulai, tidak bisa PAUSE ({$kode})."]);
        }

        if (($batch->is_holding ?? false) === true) {
            return back()->withErrors(['secondary' => "Batch sedang HOLD ({$kode})."]);
        }

        if (($batch->is_paused ?? false) === true) {
            return back()->with('success', "Batch sudah PAUSE ({$kode}).")->with('success_batch', $kode);
        }

        $batch->is_paused          = true;
        $batch->paused_stage       = 'SECONDARY_PACK';
        $batch->paused_reason      = $data['paused_reason'];
        $batch->paused_note        = null;
        $batch->paused_prev_status = $batch->status_proses;
        $batch->paused_at          = now();
        $batch->paused_by          = Auth::id();

        $batch->status_proses      = 'SECONDARY_PACK_PAUSED';
        $batch->save();

        return back()->with('success', "Secondary Pack di-PAUSE ({$kode}).")->with('success_batch', $kode);
    }

    /**
     * RESUME
     */
    public function resume(ProduksiBatch $batch)
    {
        $this->userMustLogin();
        $kode = $this->batchCode($batch);

        if (!($batch->is_paused ?? false)) {
            return back()->withErrors(['secondary' => "Batch tidak dalam kondisi PAUSE ({$kode})."]);
        }

        if (($batch->paused_stage ?? null) !== 'SECONDARY_PACK') {
            return back()->withErrors(['secondary' => "PAUSE batch ini bukan di tahap Secondary Pack ({$kode})."]);
        }

        if (($batch->is_holding ?? false) === true) {
            return back()->withErrors(['secondary' => "Batch sedang HOLD ({$kode})."]);
        }

        $prev = $batch->paused_prev_status ?: 'SECONDARY_PACK_MULAI';

        $this->clearPauseFields($batch);
        $batch->status_proses = $prev;
        $batch->save();

        return back()->with('success', "Secondary Pack di-RESUME ({$kode}).")->with('success_batch', $kode);
    }

    /**
     * HOLD – lempar ke modul holding
     */
    public function hold(Request $request, ProduksiBatch $batch)
    {
        $this->userMustLogin();
        $kode = $this->batchCode($batch);

        $data = $request->validate([
            'holding_reason' => ['required', 'string', 'min:3'],
            'holding_note'   => ['nullable', 'string'],
        ]);

        if (!empty($batch->tgl_secondary_pack_1)) {
            return back()->withErrors(['secondary' => "Sudah selesai Secondary Pack, tidak bisa HOLD ({$kode})."]);
        }

        if (($batch->is_holding ?? false) === true) {
            return back()->with('success', "Batch sudah HOLD ({$kode}).")->with('success_batch', $kode);
        }

        // kalau lagi pause, bersihin dulu biar gak nyangkut
        if (($batch->is_paused ?? false) === true) {
            $this->clearPauseFields($batch);
        }

        $batch->is_holding          = true;
        $batch->holding_stage       = 'SECONDARY_PACK';
        $batch->holding_return_to   = 'secondary-pack.index';
        $batch->holding_reason      = $data['holding_reason'];
        $batch->holding_note        = $data['holding_note'] ?? null;
        $batch->holding_prev_status = $batch->status_proses;
        $batch->holding_at          = now();
        $batch->holding_by          = Auth::id();

        $batch->status_proses       = 'HOLDING';
        $batch->save();

        return redirect()
            ->route('holding.index')
            ->with('success', "Batch di-HOLD dari Secondary Pack ({$kode}). Alasan: {$batch->holding_reason}")
            ->with('success_batch', $kode);
    }

    /**
     * FORM QTY BATCH setelah Secondary selesai.
     * Wadah otomatis dari master produk (readonly).
     */
    public function qtyForm(ProduksiBatch $batch)
    {
        $this->userMustLogin();
        $batch->load('produksi');

        $wadah = (string) (optional($batch->produksi)->wadah ?? '');
        if ($wadah === '') $wadah = (string) ($batch->wadah ?? ''); // fallback kalau batch punya kolom

        return view('secondary_pack.qty_form', [
            'batch' => $batch,
            'wadah' => $wadah,
        ]);
    }

    /**
     * SIMPAN QTY BATCH (unit) + simpan wadah otomatis (kalau kolom ada)
     *
     * Revisi: setelah simpan, tetap di halaman qty form (tidak redirect ke halaman lain).
     */
    public function qtySave(Request $request, ProduksiBatch $batch)
    {
        $this->userMustLogin();
        $batch->load('produksi');

        $data = $request->validate([
            'qty_batch' => ['required', 'integer', 'min:0'],
        ]);

        // ambil wadah dari master produk
        $wadah = (string) (optional($batch->produksi)->wadah ?? '');
        if ($wadah === '') $wadah = (string) ($batch->wadah ?? '');

        $batch->qty_batch        = (int) $data['qty_batch'];
        $batch->status_qty_batch = 'confirmed';

        // simpan wadah jika kolom di produksi_batches tersedia
        if (Schema::hasColumn('produksi_batches', 'wadah')) {
            $batch->wadah = $wadah !== '' ? $wadah : $batch->wadah;
        }
        if (Schema::hasColumn('produksi_batches', 'qty_batch_wadah')) {
            $batch->qty_batch_wadah = $batch->qty_batch_wadah ?? null;
        }

        $batch->save();

        return back()
            ->with('success', 'Qty Batch berhasil disimpan.')
            ->with('success_batch', $this->batchCode($batch));
    }
}
