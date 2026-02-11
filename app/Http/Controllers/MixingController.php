<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MixingController extends Controller
{
    /**
     * Menampilkan batch yang BELUM selesai mixing.
     * Fokus: START / STOP(+REKON per batch) / PAUSE / HOLD.
     * Target rekon MIX dari master produk (kolom target_rekon_mixing).
     */
    public function index(Request $request)
    {
        $search  = trim($request->get('q', ''));
        $bulan   = $request->get('bulan', 'all');
        $tahun   = $request->get('tahun');
        $perPage = max(1, (int) $request->get('per_page', 25));

        $alurMixing = [
            'CAIRAN_LUAR',
            'DRY_SYRUP',
            'TABLET_NON_SALUT',
            'TABLET_SALUT',
            'KAPSUL',
        ];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurMixing)
            ->whereNotNull('tgl_weighing')
            ->whereNull('tgl_mixing'); // aktif mixing (belum selesai)

        if ($search !== '') {
            $query->where(function ($q2) use ($search) {
                $q2->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('no_batch', 'like', "%{$search}%")
                    ->orWhere('kode_batch', 'like', "%{$search}%");
            });
        }

        if ($bulan && $bulan !== 'all') {
            $query->where('bulan', (int) $bulan);
        }

        if ($tahun) {
            $query->where('tahun', (int) $tahun);
        }

        $batches = $query
            ->orderByRaw('CASE WHEN tgl_mulai_mixing IS NULL THEN 1 ELSE 0 END')
            ->orderBy('tgl_mulai_mixing', 'desc')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        // --- tambah informasi paused_by_name agar bisa ditampilkan di view ---
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

        return view('produksi.mixing.index', compact(
            'batches',
            'search',
            'bulan',
            'tahun',
            'perPage'
        ));
    }

    /**
     * START MIXING
     */
    public function start(ProduksiBatch $batch)
    {
        if ($batch->is_holding) {
            return back()->withErrors(['error' => 'Batch sedang HOLD. Selesaikan dari modul Holding terlebih dahulu.']);
        }

        if ($batch->is_paused) {
            return back()->withErrors(['error' => 'Batch sedang PAUSE. Silakan RESUME dulu.']);
        }

        if (!$batch->tgl_mulai_mixing) {
            $batch->tgl_mulai_mixing = now();
            $batch->status_proses    = 'MIXING_START';
            $batch->save();
        }

        return back()->with('success', 'Mixing dimulai untuk batch ' . ($batch->kode_batch ?? $batch->no_batch) . '.');
    }

    /**
     * STOP MIXING + INPUT REKON (WAJIB) => REKON PER BATCH (KG, boleh desimal)
     * Catatan rekon: TIDAK DIPAKAI
     */
    public function stop(Request $request, ProduksiBatch $batch)
    {
        if (!$batch->tgl_mulai_mixing) {
            return back()->withErrors(['error' => 'Mixing belum dimulai.']);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['error' => 'Batch sedang HOLD. Selesaikan dari modul Holding terlebih dahulu.']);
        }

        if ($batch->is_paused) {
            return back()->withErrors(['error' => 'Batch sedang PAUSE. Silakan RESUME dulu sebelum STOP.']);
        }

        $data = $request->validate([
            'rekon_qty' => ['required', 'numeric', 'min:0'],
        ], [
            'rekon_qty.required' => 'Rekon wajib diisi saat STOP.',
            'rekon_qty.numeric'  => 'Rekon harus angka (boleh desimal).',
        ]);

        $rekon = round((float) $data['rekon_qty'], 3);

        $batch->forceFill([
            'tgl_mixing'        => now(),
            'status_proses'     => 'MIXING_SELESAI',

            'mixing_rekon_qty'  => $rekon,
            'mixing_rekon_note' => null, // catatan dihapus

            'mixing_rekon_at'   => now(),
            'mixing_rekon_by'   => Auth::id(),
        ])->save();

        $rekonText = rtrim(rtrim(number_format($rekon, 3, '.', ','), '0'), '.');

        return redirect()
            ->route('mixing.index')
            ->with('success', 'Mixing selesai + Rekon tersimpan (' . $rekonText . ' KG) untuk batch ' . ($batch->kode_batch ?? $batch->no_batch) . '.');
    }

    /**
     * PAUSE (kendala lapangan)
     */
    public function pause(Request $request, ProduksiBatch $batch)
    {
        if (!$batch->tgl_mulai_mixing) {
            return back()->withErrors(['error' => 'Mixing belum dimulai. Tidak bisa PAUSE.']);
        }

        if ($batch->tgl_mixing) {
            return back()->withErrors(['error' => 'Mixing sudah selesai. Tidak bisa PAUSE.']);
        }

        if ($batch->is_holding) {
            return back()->withErrors(['error' => 'Batch sedang HOLD. Tidak bisa PAUSE.']);
        }

        if ($batch->is_paused) {
            return back()->withErrors(['error' => 'Batch sudah dalam kondisi PAUSE.']);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'reason.required' => 'Alasan PAUSE wajib diisi.',
        ]);

        $batch->forceFill([
            'is_paused'     => 1,
            'paused_stage'  => 'MIXING',
            'paused_reason' => $data['reason'],
            'paused_at'     => now(),
            'paused_by'     => Auth::id(),
            'status_proses' => 'MIXING_PAUSED',
        ])->save();

        return back()->with('success', 'Batch di-PAUSE. Alasan: ' . $data['reason']);
    }

    /**
     * RESUME
     */
    public function resume(ProduksiBatch $batch)
    {
        if (!$batch->is_paused) {
            return back()->withErrors(['error' => 'Batch tidak dalam kondisi PAUSE.']);
        }

        if (($batch->paused_stage ?? null) !== 'MIXING') {
            return back()->withErrors(['error' => 'Batch PAUSE bukan pada tahap MIXING.']);
        }

        $batch->forceFill([
            'is_paused'     => 0,
            'paused_stage'  => null,
            'paused_reason' => null,
            'paused_at'     => null,
            'paused_by'     => null,
            'status_proses' => 'MIXING_START',
        ])->save();

        return back()->with('success', 'Batch berhasil di-RESUME. Silakan lanjutkan mixing.');
    }

    /**
     * HOLD (pindah ke modul Holding)
     */
    public function hold(Request $request, ProduksiBatch $batch)
    {
        if (!$batch->tgl_mulai_mixing) {
            return back()->withErrors(['error' => 'Mixing belum dimulai. Tidak bisa HOLD.']);
        }

        if ($batch->tgl_mixing) {
            return back()->withErrors(['error' => 'Mixing sudah selesai. Tidak bisa HOLD.']);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'note'   => ['nullable', 'string', 'max:500'],
        ], [
            'reason.required' => 'Alasan HOLD wajib diisi.',
        ]);

        // kalau dia paused, bersihkan dulu
        if ($batch->is_paused) {
            $batch->forceFill([
                'is_paused'     => 0,
                'paused_stage'  => null,
                'paused_reason' => null,
                'paused_at'     => null,
                'paused_by'     => null,
            ]);
        }

        $prev = $batch->status_proses;

        $batch->forceFill([
            'is_holding'          => 1,
            'holding_stage'       => 'MIXING',
            'holding_reason'      => $data['reason'],
            'holding_note'        => $data['note'] ?? null,
            'holding_prev_status' => $prev,
            'holding_at'          => now(),
            'holding_by'          => Auth::id(),
            'status_proses'       => 'HOLD',
        ])->save();

        return redirect()
            ->route('holding.index')
            ->with('success', 'Batch masuk HOLD dari Mixing. Alasan: ' . $data['reason']);
    }

    /**
     * History mixing (batch yang SUDAH selesai mixing).
     */
    public function history(Request $request)
    {
        $search  = trim($request->get('q', ''));
        $bulan   = $request->get('bulan', 'all');
        $tahun   = $request->get('tahun');
        $perPage = max(1, (int) $request->get('per_page', 25));

        $alurMixing = [
            'CAIRAN_LUAR',
            'DRY_SYRUP',
            'TABLET_NON_SALUT',
            'TABLET_SALUT',
            'KAPSUL',
        ];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurMixing)
            ->whereNotNull('tgl_mixing');

        if ($search !== '') {
            $query->where(function ($q2) use ($search) {
                $q2->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('no_batch', 'like', "%{$search}%")
                    ->orWhere('kode_batch', 'like', "%{$search}%");
            });
        }

        if ($bulan && $bulan !== 'all') {
            $query->where('bulan', (int) $bulan);
        }

        if ($tahun) {
            $query->where('tahun', (int) $tahun);
        }

        $batches = $query
            ->orderBy('tgl_mixing', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('produksi.mixing.history', compact(
            'batches',
            'search',
            'bulan',
            'tahun',
            'perPage'
        ));
    }
}