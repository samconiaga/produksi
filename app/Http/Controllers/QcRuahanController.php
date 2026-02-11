<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class QcRuahanController extends Controller
{
    /* =========================================================
     * HELPERS
     * =======================================================*/
    private function isAdmin($user): bool
    {
        $roleLower = strtolower((string)($user->role ?? ''));
        return in_array($roleLower, ['admin', 'administrator', 'superadmin'], true);
    }

    private function ensureSignedPayload(ProduksiBatch $batch, string $step): array
    {
        $cfg = [
            'ruahan' => [
                'code' => 'ruahan_sign_code',
                'url'  => 'ruahan_sign_url',
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

    private function holdStageKey(): string
    {
        return 'QC_RUAHAN';
    }

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
     * Patokan: ruahan_signed_at NULL + trigger alur terpenuhi + bukan HOLD
     * =======================================================*/
    public function index(Request $request)
    {
        $search = trim((string)$request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $query = ProduksiBatch::with('produksi')
            ->whereNull('ruahan_signed_at')
            ->where(function ($w) { // jangan tampilkan yg HOLD
                $w->whereNull('is_holding')->orWhere('is_holding', false);
            })
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->where('tipe_alur', 'TABLET_NON_SALUT')->whereNotNull('tgl_tableting');
                })
                ->orWhere(function ($q2) {
                    $q2->where('tipe_alur', 'TABLET_SALUT')->whereNotNull('tgl_coating');
                })
                ->orWhere(function ($q3) {
                    $q3->where('tipe_alur', 'KAPSUL')->whereNotNull('tgl_capsule_filling');
                })
                ->orWhere(function ($q4) {
                    $q4->where('tipe_alur', 'CAIRAN_LUAR')->whereNotNull('tgl_mixing');
                })
                ->orWhere(function ($q5) {
                    $q5->where('tipe_alur', 'DRY_SYRUP')->whereNotNull('tgl_mixing');
                });
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

        return view('produksi.qc_ruahan.index', compact('batches', 'search', 'bulan', 'tahun', 'user'));
    }

    /* =========================================================
     * HISTORY (READ ONLY)
     * Patokan: ruahan_signed_at NOT NULL
     * =======================================================*/
    public function history(Request $request)
    {
        $search = trim((string)$request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $query = ProduksiBatch::with('produksi')
            ->whereNotNull('ruahan_signed_at');

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

        return view('produksi.qc_ruahan.history', compact('batches', 'search', 'bulan', 'tahun'));
    }

    /* =========================================================
     * HOLD FORM (ALIAS)
     * =======================================================*/
    public function holdForm(ProduksiBatch $batch)
    {
        return $this->hold(request(), $batch);
    }

    /* =========================================================
     * HOLD (QC Ruahan)
     * =======================================================*/
    public function hold(Request $request, ProduksiBatch $batch)
    {
        $user = Auth::user();
        if (!$user) abort(403, 'Silakan login terlebih dahulu.');

        // sudah release -> tidak boleh hold
        if (!empty($batch->ruahan_signed_at)) {
            return back()->with('success', 'Batch sudah Release Ruahan, tidak bisa di-hold.');
        }

        // sudah holding -> arahkan ke holding
        if (!empty($batch->is_holding)) {
            if (\Route::has('holding.index')) {
                return redirect()->route('holding.index')->with('success', 'Batch ini sudah berada di Holding.');
            }
            return back()->with('success', 'Batch ini sudah berada di Holding.');
        }

        $holdingReason = trim((string)$request->get('reason', 'Hold dari QC Ruahan'));
        $holdingNote   = trim((string)$request->get('note', ''));

        $this->setIfExists($batch, 'is_holding', true);
        $this->setIfExists($batch, 'holding_stage', $this->holdStageKey());         // QC_RUAHAN
        $this->setIfExists($batch, 'holding_return_to', $this->holdStageKey());     // balik QC_RUAHAN
        $this->setIfExists($batch, 'holding_reason', $holdingReason);
        $this->setIfExists($batch, 'holding_note', $holdingNote !== '' ? $holdingNote : null);
        $this->setIfExists($batch, 'holding_prev_status', $batch->status_proses ?? null);

        $this->setIfExists($batch, 'holding_at', now());
        $this->setByColumn($batch, 'holding_by', $user);

        // reset pause kalau ada
        $this->setIfExists($batch, 'is_paused', false);
        $this->setIfExists($batch, 'paused_stage', null);
        $this->setIfExists($batch, 'paused_reason', null);
        $this->setIfExists($batch, 'paused_at', null);
        $this->setIfExists($batch, 'paused_by', null);

        $batch->status_proses = 'HOLDING';

        try {
            $batch->save();
        } catch (\Throwable $e) {
            Log::error('Gagal HOLD QC Ruahan', [
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
     * - wajib input tgl_rilis_ruahan dari UI
     * =======================================================*/
    public function release(Request $request, ProduksiBatch $batch)
    {
        $user = Auth::user();
        if (!$user) abort(403, 'Silakan login terlebih dahulu.');

        if (!empty($batch->is_holding)) {
            return back()->with('success', 'Batch sedang HOLD. Unhold dulu di modul Holding.');
        }

        if (!empty($batch->ruahan_signed_at)) {
            return back()->with('success', 'Batch ini sudah pernah direlease.');
        }

        $data = $request->validate([
            'tgl_rilis_ruahan' => ['required', 'date'],
        ]);

        $batch->tgl_rilis_ruahan = $data['tgl_rilis_ruahan'];

        // exp ikut granul kalau kosong (opsional, aman)
        if (empty($batch->ruahan_exp_date) && !empty($batch->granul_exp_date)) {
            $batch->ruahan_exp_date = $batch->granul_exp_date;
        }

        // generate sign payload (optional keep)
        try {
            $this->ensureSignedPayload($batch, 'ruahan');
        } catch (\Throwable $e) {
            Log::warning('ensureSignedPayload ruahan gagal (boleh diabaikan)', [
                'batch_id' => $batch->id ?? null,
                'err'      => $e->getMessage(),
            ]);
        }

        $batch->ruahan_signed_at      = now();
        $batch->ruahan_signed_by      = $user->name ?? '-';
        $batch->ruahan_signed_level   = strtoupper((string)($user->role ?? 'USER'));
        $batch->ruahan_signed_user_id = $user->id;

        $batch->status_proses = 'QC_RUAHAN_RELEASED';

        try {
            $batch->save();
        } catch (\Throwable $e) {
            Log::error('Gagal RELEASE QC Ruahan', [
                'batch_id' => $batch->id ?? null,
                'err'      => $e->getMessage(),
            ]);
            return back()->with('success', 'Gagal release: ' . $e->getMessage());
        }

        return redirect()
            ->route('qc-ruahan.index')
            ->with('success', 'Ruahan berhasil direlease.');
    }
}