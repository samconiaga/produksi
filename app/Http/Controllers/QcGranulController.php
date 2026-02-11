<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class QcGranulController extends Controller
{
    /* =========================
     * HELPERS
     * =======================*/
    private function isAdmin($user): bool
    {
        $roleLower = strtolower((string)($user->role ?? ''));
        return in_array($roleLower, ['admin', 'administrator', 'superadmin'], true);
    }

    /** QC Granul Stage Key (buat holding) */
    private function holdStageKey(): string
    {
        return 'QC_GRANUL';
    }

    /** Anti error: isi holding_by sesuai tipe kolom DB */
    private function setByColumn(ProduksiBatch $batch, string $col, $user): void
    {
        try {
            if (!Schema::hasColumn($batch->getTable(), $col)) return;

            $type = Schema::getColumnType($batch->getTable(), $col);
            $intTypes = ['integer', 'bigint', 'smallint', 'mediumint', 'tinyint'];

            if (in_array($type, $intTypes, true)) {
                $batch->{$col} = (int)($user->id ?? 0);
            } else {
                $batch->{$col} = (string)($user->name ?? '-');
            }
        } catch (\Throwable $e) {
            // fallback diam
            try {
                $batch->{$col} = (int)($user->id ?? 0);
            } catch (\Throwable $e2) {}
        }
    }

    /** Anti error: set kolom hanya kalau exist */
    private function setIfExists(ProduksiBatch $batch, string $col, $value): void
    {
        try {
            if (Schema::hasColumn($batch->getTable(), $col)) {
                $batch->{$col} = $value;
            }
        } catch (\Throwable $e) {}
    }

    /* =========================================================
     * DATA AKTIF (BELUM RELEASE GRANUL)
     * =======================================================*/
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) abort(403, 'Silakan login terlebih dahulu.');

        $search = trim((string)$request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');
        $perPage = max(1, (int)$request->get('per_page', 20));

        // alur granul
        $alurGranul = ['TABLET_NON_SALUT', 'TABLET_SALUT', 'KAPSUL'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurGranul)
            ->whereNotNull('tgl_mixing')
            ->whereNull('granul_signed_at')  // indikator "sudah release"
            ->whereNull('ruahan_signed_at')  // tetap seperti sebelumnya
            ->where(function ($w) {
                $w->whereNull('is_holding')->orWhere('is_holding', false);
            });

        if ($search !== '') {
            $query->where(function ($q2) use ($search) {
                $q2->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('no_batch', 'like', "%{$search}%")
                    ->orWhere('kode_batch', 'like', "%{$search}%");
            });
        }

        if ($bulan !== null && $bulan !== '' && $bulan !== 'all') {
            $query->where('bulan', (int)$bulan);
        }

        if ($tahun !== null && $tahun !== '') {
            $query->where('tahun', (int)$tahun);
        }

        $batches = $query
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('produksi.qc_granul.index', compact('batches', 'search', 'bulan', 'tahun', 'perPage'));
    }

    /* =========================================================
     * RIWAYAT (SUDAH RELEASE GRANUL)
     * =======================================================*/
    public function history(Request $request)
    {
        $user = Auth::user();
        if (!$user) abort(403, 'Silakan login terlebih dahulu.');

        $search = trim((string)$request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');
        $perPage = max(1, (int)$request->get('per_page', 20));

        $alurGranul = ['TABLET_NON_SALUT', 'TABLET_SALUT', 'KAPSUL'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurGranul)
            ->whereNotNull('tgl_mixing')
            ->whereNotNull('granul_signed_at'); // sudah release

        if ($search !== '') {
            $query->where(function ($q2) use ($search) {
                $q2->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('no_batch', 'like', "%{$search}%")
                    ->orWhere('kode_batch', 'like', "%{$search}%");
            });
        }

        if ($bulan !== null && $bulan !== '' && $bulan !== 'all') {
            $query->where('bulan', (int)$bulan);
        }

        if ($tahun !== null && $tahun !== '') {
            $query->where('tahun', (int)$tahun);
        }

        $batches = $query
            ->orderByDesc('granul_signed_at')
            ->orderByDesc('tgl_rilis_granul')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('produksi.qc_granul.history', compact('batches', 'search', 'bulan', 'tahun', 'perPage'));
    }

    /* =========================================================
     * RELEASE GRANUL (SIMPLE)
     * - input tanggal release (required)
     * - semua user login boleh release
     * - tidak ada TTD / QR / print
     * =======================================================*/
    public function release(Request $request, ProduksiBatch $batch)
    {
        $user = Auth::user();
        if (!$user) abort(403, 'Silakan login terlebih dahulu.');

        if (!empty($batch->is_holding)) {
            return back()->withErrors(['qc' => 'Batch sedang HOLD. Unhold dulu di modul Holding.']);
        }

        if (!empty($batch->granul_signed_at)) {
            return back()->withErrors(['qc' => 'Batch ini sudah Release Granul.']);
        }

        // Validasi sederhana: cuma tanggal release
        $data = $request->validate([
            'tgl_rilis_granul' => ['required', 'date'],
        ]);

        // Optional safety: pastikan batch memang eligible (sesuai index)
        $alurGranul = ['TABLET_NON_SALUT', 'TABLET_SALUT', 'KAPSUL'];
        if (!in_array((string)$batch->tipe_alur, $alurGranul, true) || empty($batch->tgl_mixing)) {
            return back()->withErrors(['qc' => 'Batch ini tidak memenuhi syarat untuk QC Granul.']);
        }

        // simpan
        $batch->tgl_rilis_granul = $data['tgl_rilis_granul'];

        // indikator "released" tetap pakai kolom existing biar alur downstream aman
        $batch->granul_signed_at = now();

        // kalau kolomnya ada, isi aja (biar traceable). TIDAK ditampilkan lagi di UI.
        $this->setIfExists($batch, 'granul_signed_by', $user->name ?? '-');
        $this->setIfExists($batch, 'granul_signed_level', $this->isAdmin($user) ? 'ADMIN' : (($user->role ?? '-') ?: '-'));
        $this->setIfExists($batch, 'granul_signed_user_id', $user->id ?? null);

        $batch->status_proses = 'QC_GRANUL_RELEASED';

        try {
            $batch->save();
        } catch (\Throwable $e) {
            Log::error('Gagal release QC Granul (simple)', [
                'batch_id' => $batch->id ?? null,
                'err' => $e->getMessage(),
            ]);
            return back()->withErrors(['qc' => 'Gagal release: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('qc-granul.index')
            ->with('success', 'Granul berhasil direlease.');
    }

    /* =========================================================
     * HOLD (QC Granul) - tetap ada
     * =======================================================*/
    public function holdForm(ProduksiBatch $batch)
    {
        return $this->hold(request(), $batch);
    }

    public function hold(Request $request, ProduksiBatch $batch)
    {
        $user = Auth::user();
        if (!$user) abort(403, 'Silakan login terlebih dahulu.');

        if (!empty($batch->granul_signed_at)) {
            return back()->withErrors(['qc' => 'Batch sudah Release Granul, tidak bisa di-hold.']);
        }

        if (!empty($batch->is_holding)) {
            if (\Route::has('holding.index')) {
                return redirect()->route('holding.index')->with('success', 'Batch ini sudah berada di Holding.');
            }
            return back()->with('success', 'Batch ini sudah berada di Holding.');
        }

        $holdingReason = trim((string)$request->get('reason', 'Hold dari QC Granul'));
        $holdingNote   = trim((string)$request->get('note', ''));

        $this->setIfExists($batch, 'is_holding', true);
        $this->setIfExists($batch, 'holding_stage', $this->holdStageKey());
        $this->setIfExists($batch, 'holding_return_to', 'qc-granul.index'); // balik ke modul QC Granul
        $this->setIfExists($batch, 'holding_reason', $holdingReason);
        $this->setIfExists($batch, 'holding_note', $holdingNote !== '' ? $holdingNote : null);
        $this->setIfExists($batch, 'holding_prev_status', $batch->status_proses ?? null);

        $this->setIfExists($batch, 'holding_at', now());
        $this->setByColumn($batch, 'holding_by', $user);

        // QC granul tidak pakai pause
        $this->setIfExists($batch, 'is_paused', false);
        $this->setIfExists($batch, 'paused_stage', null);
        $this->setIfExists($batch, 'paused_reason', null);
        $this->setIfExists($batch, 'paused_at', null);
        $this->setIfExists($batch, 'paused_by', null);

        $batch->status_proses = 'HOLDING';

        try {
            $batch->save();
        } catch (\Throwable $e) {
            Log::error('Gagal HOLD QC Granul', [
                'batch_id' => $batch->id ?? null,
                'err' => $e->getMessage(),
            ]);
            return back()->withErrors(['qc' => 'Gagal HOLD: ' . $e->getMessage()]);
        }

        if (\Route::has('holding.index')) {
            return redirect()->route('holding.index')->with('success', 'Batch berhasil di-HOLD dan masuk ke modul Holding.');
        }

        return back()->with('success', 'Batch berhasil di-HOLD.');
    }
}