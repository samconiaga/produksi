<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class QcTabletController extends Controller
{
    /* =========================================================
     * HELPERS
     * =======================================================*/
    private function isAdmin($user): bool
    {
        $roleLower = strtolower((string)($user->role ?? ''));
        return in_array($roleLower, ['admin', 'administrator', 'superadmin'], true);
    }

    /**
     * Kalau kamu masih butuh payload sign code & url (misal untuk arsip/verifikasi),
     * ini tetap disediakan. Tapi UI print/QR/excel kita hilangkan.
     */
    private function ensureSignedPayload(ProduksiBatch $batch, string $step): array
    {
        $cfg = [
            'tablet' => [
                'code' => 'tablet_sign_code',
                'url'  => 'tablet_sign_url',
            ],
        ];

        if (!isset($cfg[$step])) {
            throw new \RuntimeException('Step signature tidak dikenal.');
        }

        $codeCol = $cfg[$step]['code'];
        $urlCol  = $cfg[$step]['url'];

        $dirty = false;

        if (empty($batch->{$codeCol})) {
            $batch->{$codeCol} = strtoupper(Str::random(14));
            $dirty = true;
        }

        $signedUrl = URL::temporarySignedRoute(
            'sign.qc.show',
            now()->addYears(3),
            ['step' => $step, 'code' => $batch->{$codeCol}]
        );

        if (($batch->{$urlCol} ?? '') !== $signedUrl) {
            $batch->{$urlCol} = $signedUrl;
            $dirty = true;
        }

        if ($dirty) $batch->save();

        return [$batch->{$codeCol}, $signedUrl];
    }

    /** key stage holding biar konsisten */
    private function holdStageKey(): string
    {
        return 'QC_TABLET';
    }

    /**
     * Anti error: isi holding_by / paused_by sesuai tipe kolom DB.
     * - kalau kolom *_by integer → isi user_id
     * - kalau kolom *_by string/varchar → isi user_name
     * - kalau kolom tidak ada → skip
     */
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
            // fallback aman
            try {
                $batch->{$col} = (int)($user->id ?? 0);
            } catch (\Throwable $e2) {
                try {
                    $batch->{$col} = (string)($user->name ?? '-');
                } catch (\Throwable $e3) {
                    // skip
                }
            }
        }
    }

    /** Anti error: set kolom hanya kalau exist */
    private function setIfExists(ProduksiBatch $batch, string $col, $value): void
    {
        try {
            if (Schema::hasColumn($batch->getTable(), $col)) {
                $batch->{$col} = $value;
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /* =========================================================
     * INDEX (DATA AKTIF)
     * Patokan: tablet_signed_at NULL, tgl_tableting NOT NULL, bukan HOLD
     * =======================================================*/
    public function index(Request $request)
    {
        $search = trim((string)$request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $alurTablet = ['TABLET_SALUT'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurTablet)
            ->whereNotNull('tgl_tableting')
            ->whereNull('tablet_signed_at') // belum release
            ->where(function ($w) {         // jangan tampilkan yang sedang HOLD
                $w->whereNull('is_holding')->orWhere('is_holding', false);
            });

        if ($search !== '') {
            $query->where(function ($q2) use ($search) {
                $q2->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('no_batch', 'like', "%{$search}%")
                    ->orWhere('kode_batch', 'like', "%{$search}%");
            });
        }

        if ($bulan !== null && $bulan !== '' && $bulan !== 'all') $query->where('bulan', (int)$bulan);
        if ($tahun !== null && $tahun !== '') $query->where('tahun', (int)$tahun);

        $batches = $query
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->paginate(20)
            ->withQueryString();

        $user = Auth::user();

        return view('produksi.qc_tablet.index', compact('batches', 'search', 'bulan', 'tahun', 'user'));
    }

    /* =========================================================
     * HISTORY (READ ONLY)
     * Patokan: tablet_signed_at NOT NULL
     * =======================================================*/
    public function history(Request $request)
    {
        $search = trim((string)$request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $alurTablet = ['TABLET_SALUT'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurTablet)
            ->whereNotNull('tgl_tableting')
            ->whereNotNull('tablet_signed_at');

        if ($search !== '') {
            $query->where(function ($q2) use ($search) {
                $q2->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('no_batch', 'like', "%{$search}%")
                    ->orWhere('kode_batch', 'like', "%{$search}%");
            });
        }

        if ($bulan !== null && $bulan !== '' && $bulan !== 'all') $query->where('bulan', (int)$bulan);
        if ($tahun !== null && $tahun !== '') $query->where('tahun', (int)$tahun);

        $batches = $query
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->paginate(20)
            ->withQueryString();

        return view('produksi.qc_tablet.history', compact('batches', 'search', 'bulan', 'tahun'));
    }

    /* =========================================================
     * HOLD FORM (ALIAS)
     * =======================================================*/
    public function holdForm(ProduksiBatch $batch)
    {
        return $this->hold(request(), $batch);
    }

    /* =========================================================
     * HOLD (QC Tablet)
     * =======================================================*/
    public function hold(Request $request, ProduksiBatch $batch)
    {
        $user = Auth::user();
        if (!$user) abort(403, 'Silakan login terlebih dahulu.');

        // ✅ sesuai permintaan: semua yang login boleh hold
        // (kalau kamu mau balikin jadi QC/Admin, tinggal aktifkan lagi pengecekannya)
        // $isAdmin = $this->isAdmin($user);
        // if (!$isAdmin && ($user->role ?? null) !== 'QC') abort(403, 'Hanya QC atau Admin yang dapat melakukan Hold.');

        // sudah release -> tidak boleh hold
        if (!empty($batch->tablet_signed_at)) {
            return back()->with('success', 'Batch sudah Release Tablet, tidak bisa di-hold.');
        }

        // sudah holding -> arahkan ke holding
        if (!empty($batch->is_holding)) {
            if (\Route::has('holding.index')) {
                return redirect()->route('holding.index')->with('success', 'Batch ini sudah berada di Holding.');
            }
            return back()->with('success', 'Batch ini sudah berada di Holding.');
        }

        $holdingReason = trim((string)$request->get('reason', 'Hold dari QC Tablet'));
        $holdingNote   = trim((string)$request->get('note', ''));

        // set HOLD (aman walau kolom belum ada)
        $this->setIfExists($batch, 'is_holding', true);
        $this->setIfExists($batch, 'holding_stage', $this->holdStageKey());     // QC_TABLET
        $this->setIfExists($batch, 'holding_return_to', $this->holdStageKey()); // balik QC_TABLET
        $this->setIfExists($batch, 'holding_reason', $holdingReason);
        $this->setIfExists($batch, 'holding_note', $holdingNote !== '' ? $holdingNote : null);
        $this->setIfExists($batch, 'holding_prev_status', $batch->status_proses ?? null);

        $this->setIfExists($batch, 'holding_at', now());
        $this->setByColumn($batch, 'holding_by', $user);

        // pastikan tidak ada PAUSE (kalau kolom exist)
        $this->setIfExists($batch, 'is_paused', false);
        $this->setIfExists($batch, 'paused_stage', null);
        $this->setIfExists($batch, 'paused_reason', null);
        $this->setIfExists($batch, 'paused_at', null);
        $this->setIfExists($batch, 'paused_by', null);

        // optional: status proses biar kebaca jelas
        $batch->status_proses = 'HOLDING';

        try {
            $batch->save();
        } catch (\Throwable $e) {
            Log::error('Gagal HOLD QC Tablet', [
                'batch_id' => $batch->id ?? null,
                'err'      => $e->getMessage(),
            ]);
            return back()->with('success', 'Gagal HOLD: ' . $e->getMessage());
        }

        if (\Route::has('holding.index')) {
            return redirect()->route('holding.index')->with('success', 'Batch berhasil di-HOLD dan masuk ke modul Holding.');
        }

        return back()->with('success', 'Batch berhasil di-HOLD.');
    }

    /* =========================================================
     * RELEASE (SIMPLE)
     * - tanpa syarat QC manager/spv
     * - tanpa cek stop analisa
     * - wajib input tgl_rilis_tablet dari UI
     * =======================================================*/
    public function release(Request $request, ProduksiBatch $batch)
    {
        $user = Auth::user();
        if (!$user) abort(403, 'Silakan login terlebih dahulu.');

        // kalau sedang HOLD, tidak boleh release
        if (!empty($batch->is_holding)) {
            return back()->with('success', 'Batch sedang HOLD. Unhold dulu di modul Holding.');
        }

        // sudah release -> skip
        if (!empty($batch->tablet_signed_at)) {
            return back()->with('success', 'Batch ini sudah pernah direlease.');
        }

        // ✅ wajib isi tanggal release
        $data = $request->validate([
            'tgl_rilis_tablet' => ['required', 'date'],
        ]);

        $batch->tgl_rilis_tablet = $data['tgl_rilis_tablet'];

        // exp ikut granul kalau kosong
        if (empty($batch->tablet_exp_date) && !empty($batch->granul_exp_date)) {
            $batch->tablet_exp_date = $batch->granul_exp_date;
        }

        // generate sign payload (optional keep)
        try {
            $this->ensureSignedPayload($batch, 'tablet');
        } catch (\Throwable $e) {
            // kalau route sign.qc.show belum ada, jangan bikin error release
            Log::warning('ensureSignedPayload gagal (boleh diabaikan)', [
                'batch_id' => $batch->id ?? null,
                'err'      => $e->getMessage(),
            ]);
        }

        $batch->tablet_signed_at      = now();
        $batch->tablet_signed_by      = $user->name ?? '-';
        $batch->tablet_signed_level   = strtoupper((string)($user->role ?? 'USER'));
        $batch->tablet_signed_user_id = $user->id;

        $batch->status_proses = 'QC_TABLET_RELEASED';

        try {
            $batch->save();
        } catch (\Throwable $e) {
            Log::error('Gagal RELEASE QC Tablet', [
                'batch_id' => $batch->id ?? null,
                'err'      => $e->getMessage(),
            ]);
            return back()->with('success', 'Gagal release: ' . $e->getMessage());
        }

        return redirect()
            ->route('qc-tablet.index')
            ->with('success', 'Tablet berhasil direlease.');
    }
}