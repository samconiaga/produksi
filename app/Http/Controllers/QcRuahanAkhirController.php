<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class QcRuahanAkhirController extends Controller
{
    /**
     * INDEX – hanya untuk tipe_alur CAIRAN_LUAR
     * Syarat:
     * - tipe_alur = CAIRAN_LUAR
     * - tgl_primary_pack != null           (Primary sudah selesai)
     * - tgl_rilis_ruahan_akhir == null     (Ruahan akhir belum release)
     */
    public function index(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $query = ProduksiBatch::with('produksi')
            ->where('tipe_alur', 'CAIRAN_LUAR')     // hanya CLO yang lewat sini
            ->whereNotNull('tgl_primary_pack')      // sudah primary pack
            ->whereNull('tgl_rilis_ruahan_akhir');  // ruahan akhir belum release

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
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->paginate(20)
            ->withQueryString();

        return view('produksi.qc_ruahan_akhir.index', compact('batches', 'search', 'bulan', 'tahun'));
    }

    /**
     * HISTORY – juga khusus CAIRAN_LUAR yang sudah rilis ruahan akhir.
     */
    public function history(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $query = ProduksiBatch::with('produksi')
            ->where('tipe_alur', 'CAIRAN_LUAR')
            ->whereNotNull('tgl_rilis_ruahan_akhir');

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
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->paginate(20)
            ->withQueryString();

        return view('produksi.qc_ruahan_akhir.history', compact('batches', 'search', 'bulan', 'tahun'));
    }

    public function update(Request $request, ProduksiBatch $batch)
    {
        $action   = $request->input('action');
        $qcAction = $request->input('qc_action');

        if ($action === 'confirm_datang') {
            $data = $request->validate([
                'tgl_datang_ruahan_akhir' => ['required', 'date'],
            ]);

            $batch->tgl_datang_ruahan_akhir = $data['tgl_datang_ruahan_akhir'];
            $batch->save();

            return back()->with('success', 'Tanggal datang ruahan akhir berhasil dikonfirmasi.');
        }

        if ($qcAction === 'start_analisa') {
            if (is_null($batch->tgl_analisa_ruahan_akhir)) {
                $batch->tgl_analisa_ruahan_akhir = now()->format('Y-m-d');
                $batch->save();
            }
            return back()->with('success', 'Analisa ruahan akhir dimulai.');
        }

        if ($qcAction === 'stop_release') {
            if (is_null($batch->tgl_rilis_ruahan_akhir)) {
                $batch->tgl_rilis_ruahan_akhir = now()->format('Y-m-d');

                // OPSIONAL: ini tahap QC terakhir → tandai batch sudah QC RELEASED
                $batch->status_proses = 'QC RELEASED';

                $batch->save();
            }
            return back()->with('success', 'Ruahan akhir telah direlease & status QC RELEASED.');
        }

        return back()->with('success', 'Tidak ada perubahan.');
    }
}
