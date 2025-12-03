<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class MixingController extends Controller
{
    /**
     * Menampilkan batch yang BELUM selesai mixing.
     * (Dipakai operator untuk realtime start & stop)
     */
    public function index(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');
        $perPage = max(1, (int) $request->get('per_page', 25));

        // Alur yang memiliki mixing
        $alurMixing = [
            'CAIRAN_LUAR',
            'DRY_SYRUP',
            'TABLET_NON_SALUT',
            'TABLET_SALUT',
            'KAPSUL',
        ];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurMixing)
            ->whereNotNull('tgl_weighing') // sudah weighing
            ->whereNull('tgl_mixing');     // belum selesai mixing

        if ($search !== '') {
            $query->where(function ($q2) use ($search) {
                $q2->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('no_batch', 'like', "%{$search}%")
                    ->orWhere('kode_batch', 'like', "%{$search}%");
            });
        }

        if ($bulan && $bulan !== "all") {
            $query->where('bulan', (int) $bulan);
        }

        if ($tahun) {
            $query->where('tahun', (int) $tahun);
        }

        $batches = $query
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('produksi.mixing.index', compact('batches', 'search', 'bulan', 'tahun'));
    }


    /**
     * BUTTON START MIXING — REALTIME
     */
    public function start(ProduksiBatch $batch)
    {
        if (!$batch->tgl_mulai_mixing) {
            $batch->tgl_mulai_mixing = now(); // waktu realtime
            $batch->status_proses = 'MIXING_START';
            $batch->save();
        }

        return back()->with('success', 'Mixing dimulai.');
    }


    /**
     * BUTTON STOP MIXING — REALTIME
     */
    public function stop(ProduksiBatch $batch)
    {
        if (!$batch->tgl_mulai_mixing) {
            return back()->withErrors(['error' => 'Mixing belum dimulai.']);
        }

        $batch->tgl_mixing = now(); // waktu realtime
        $batch->status_proses = 'MIXING_SELESAI';
        $batch->save();

        return back()->with('success', 'Mixing selesai.');
    }


    /**
     * History mixing (sudah stop)
     */
    public function history(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');
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
            ->whereNotNull('tgl_mixing'); // selesai mixing

        if ($search !== '') {
            $query->where(function ($q2) use ($search) {
                $q2->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('no_batch', 'like', "%{$search}%")
                    ->orWhere('kode_batch', 'like', "%{$search}%");
            });
        }

        if ($bulan && $bulan !== "all") {
            $query->where('bulan', (int) $bulan);
        }

        if ($tahun) {
            $query->where('tahun', (int) $tahun);
        }

        $batches = $query
            ->orderBy('tgl_mixing', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('produksi.mixing.history', compact('batches', 'search', 'bulan', 'tahun'));
    }
}
