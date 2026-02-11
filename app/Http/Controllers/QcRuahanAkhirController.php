<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class QcRuahanAkhirController extends Controller
{
    private array $alurRuahanAkhir = ['CAIRAN_LUAR', 'CLO'];

    /* =========================================================
     * HELPERS
     * =======================================================*/
    private function isReleased(ProduksiBatch $batch): bool
    {
        return !empty($batch->ruahan_akhir_signed_at) || !empty($batch->tgl_rilis_ruahan_akhir);
    }

    private function ensureSignedPayload(ProduksiBatch $batch, string $step): array
    {
        $cfg = [
            'ruahan_akhir' => [
                'code' => 'ruahan_akhir_sign_code',
                'url'  => 'ruahan_akhir_sign_url',
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

    private function perPage(Request $request, int $default = 20): int
    {
        $perPage = (int) $request->get('per_page', $default);
        if (!in_array($perPage, [10, 20, 50, 100], true)) $perPage = $default;
        return $perPage;
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

    /**
     * Set column value according to column type.
     * If column is integer-type -> set user id; otherwise set user name.
     *
     * @param ProduksiBatch $batch
     * @param string $col
     * @param mixed $user  object user atau array with id/name
     */
    private function setByColumn(ProduksiBatch $batch, string $col, $user): void
    {
        try {
            if (!Schema::hasColumn($batch->getTable(), $col)) return;

            $type = Schema::getColumnType($batch->getTable(), $col);
            $intTypes = ['integer', 'bigint', 'smallint', 'mediumint', 'tinyint'];

            if (in_array($type, $intTypes, true)) {
                $batch->{$col} = (int)($user->id ?? $user['id'] ?? 0);
            } else {
                $batch->{$col} = (string)($user->name ?? $user['name'] ?? '-');
            }
        } catch (\Throwable $e) {
            // fallback: try to set id if available
            try { $batch->{$col} = (int)($user->id ?? $user['id'] ?? 0); } catch (\Throwable $e2) {}
        }
    }

    /* =========================================================
     * INDEX (AKTIF) -> belum release
     * =======================================================*/
    public function index(Request $request)
    {
        $user = Auth::user();

        $search  = trim((string) $request->get('q', ''));
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = $this->perPage($request, 20);

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $this->alurRuahanAkhir)
            ->whereNotNull('tgl_primary_pack')
            ->whereNull('tgl_rilis_ruahan_akhir');

        // ✅ jangan tampilkan yang sedang HOLD
        $query->where(function ($w) {
            $w->whereNull('is_holding')->orWhere('is_holding', false);
        });

        if ($search !== '') {
            $query->where(function ($q2) use ($search) {
                $q2->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('no_batch', 'like', "%{$search}%")
                    ->orWhere('kode_batch', 'like', "%{$search}%");
            });
        }

        if ($bulan !== null && $bulan !== '' && $bulan !== 'all') $query->where('bulan', (int) $bulan);
        if ($tahun !== null && $tahun !== '') $query->where('tahun', (int) $tahun);

        $batches = $query->orderBy('tahun')->orderBy('bulan')->orderBy('wo_date')->orderBy('id')
            ->paginate($perPage)->withQueryString();

        return view('produksi.qc_ruahan_akhir.index', compact(
            'batches', 'search', 'bulan', 'tahun', 'perPage', 'user'
        ));
    }

    /* =========================================================
     * HISTORY -> sudah release
     * =======================================================*/
    public function history(Request $request)
    {
        $user = Auth::user();

        $search  = trim((string) $request->get('q', ''));
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = $this->perPage($request, 20);

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $this->alurRuahanAkhir)
            ->whereNotNull('tgl_rilis_ruahan_akhir');

        if ($search !== '') {
            $query->where(function ($q2) use ($search) {
                $q2->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('no_batch', 'like', "%{$search}%")
                    ->orWhere('kode_batch', 'like', "%{$search}%");
            });
        }

        if ($bulan !== null && $bulan !== '' && $bulan !== 'all') $query->where('bulan', (int) $bulan);
        if ($tahun !== null && $tahun !== '') $query->where('tahun', (int) $tahun);

        $batches = $query->orderBy('tahun')->orderBy('bulan')->orderBy('wo_date')->orderBy('id')
            ->paginate($perPage)->withQueryString();

        return view('produksi.qc_ruahan_akhir.history', compact(
            'batches', 'search', 'bulan', 'tahun', 'perPage', 'user'
        ));
    }

    /* =========================================================
     * RELEASE -> semua user login bisa, tanpa input
     * =======================================================*/
    public function release(Request $request, ProduksiBatch $batch)
    {
        $user = Auth::user();
        if (!$user) abort(403, 'Silakan login.');

        // blok kalau HOLD
        if (!empty($batch->is_holding)) {
            return back()->with('success', 'Batch sedang HOLD. Unhold dulu di modul Holding.');
        }

        if ($this->isReleased($batch)) {
            return back()->with('success', 'Ruahan akhir sudah direlease sebelumnya.');
        }

        // set tanggal release otomatis hari ini (date string)
        if (is_null($batch->tgl_rilis_ruahan_akhir)) {
            // simpan sebagai date (YYYY-mm-dd) kalau kolom ada
            $this->setIfExists($batch, 'tgl_rilis_ruahan_akhir', now()->toDateString());
        }

        // tetap bikin payload ttd/verifikasi (biar data tetap rapi)
        [$code, $url] = $this->ensureSignedPayload($batch, 'ruahan_akhir');

        // signed at (timestamp)
        $this->setIfExists($batch, 'ruahan_akhir_signed_at', now());

        // signed_by harus mengikuti tipe kolom (INT => user id). gunakan helper setByColumn
        $this->setByColumn($batch, 'ruahan_akhir_signed_by', $user);

        // juga simpan explicit user id column jika ada
        $this->setIfExists($batch, 'ruahan_akhir_signed_user_id', $user->id);

        // set signed level / role as string (jika kolom ada) - prefer produksi_role or role
        $role = data_get($user, 'produksi_role') ?: data_get($user, 'role') ?: ($user->name ?? '-');
        $this->setIfExists($batch, 'ruahan_akhir_signed_level', strtoupper((string)$role));

        // set status and save
        $this->setIfExists($batch, 'status_proses', 'QC_RUAHAN_AKHIR_RELEASED');

        $batch->save();

        return back()->with('success', 'Ruahan akhir berhasil direlease.');
    }

    /* =========================================================
     * HOLD -> tombol Hold seperti Granul
     * =======================================================*/
    public function hold(Request $request, ProduksiBatch $batch)
    {
        $user = Auth::user();
        if (!$user) abort(403, 'Silakan login.');

        if ($this->isReleased($batch)) {
            return back()->with('success', 'Batch sudah release, tidak bisa di-HOLD.');
        }

        // set is_holding + metadata kalau kolomnya ada
        $this->setIfExists($batch, 'is_holding', true);
        $this->setIfExists($batch, 'holding_stage', 'QC_RUAHAN_AKHIR');
        $this->setIfExists($batch, 'holding_note', 'HOLD dari QC Ruahan Akhir');
        $this->setIfExists($batch, 'holding_at', now());
        $this->setByColumn($batch, 'holding_by', $user);

        $batch->save();

        return back()->with('success', 'Batch masuk HOLD.');
    }
}
