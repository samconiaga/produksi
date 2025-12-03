<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class QcRuahanController extends Controller
{
    /**
     * Produk Ruahan – batch yang BELUM rilis ruahan.
     * Syarat per tipe_alur:
     * - TABLET_NON_SALUT : tgl_tableting       != null
     * - TABLET_SALUT     : tgl_coating         != null
     * - KAPSUL           : tgl_capsule_filling != null
     * - CAIRAN_LUAR      : tgl_mixing          != null
     * - DRY_SYRUP        : tgl_mixing          != null
     */
    public function index(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $query = ProduksiBatch::with('produksi')
            ->whereNull('tgl_rilis_ruahan')
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->where('tipe_alur', 'TABLET_NON_SALUT')
                       ->whereNotNull('tgl_tableting');
                })
                ->orWhere(function ($q2) {
                    $q2->where('tipe_alur', 'TABLET_SALUT')
                       ->whereNotNull('tgl_coating');
                })
                ->orWhere(function ($q3) {
                    $q3->where('tipe_alur', 'KAPSUL')
                       ->whereNotNull('tgl_capsule_filling');
                })
                ->orWhere(function ($q4) {
                    $q4->where('tipe_alur', 'CAIRAN_LUAR')
                       ->whereNotNull('tgl_mixing');
                })
                ->orWhere(function ($q5) {
                    $q5->where('tipe_alur', 'DRY_SYRUP')
                       ->whereNotNull('tgl_mixing');
                });
            });

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

        return view('produksi.qc_ruahan.index', compact('batches', 'search', 'bulan', 'tahun'));
    }

    /**
     * Riwayat Produk Ruahan – sudah rilis ruahan.
     */
    public function history(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $query = ProduksiBatch::with('produksi')
            ->whereNotNull('tgl_rilis_ruahan');

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

        return view('produksi.qc_ruahan.history', compact('batches', 'search', 'bulan', 'tahun'));
    }

    /**
     * Konfirmasi datang / analisa / rilis ruahan
     * (fungsi lama tetap)
     */
    public function update(Request $request, ProduksiBatch $batch)
    {
        $action   = $request->input('action');
        $qcAction = $request->input('qc_action');

        if ($action === 'confirm_datang') {
            $data = $request->validate([
                'tgl_datang_ruahan' => ['required', 'date'],
            ]);

            $batch->tgl_datang_ruahan = $data['tgl_datang_ruahan'];
            $batch->save();

            return back()->with('success', 'Tanggal datang ruahan berhasil dikonfirmasi.');
        }

        if ($qcAction === 'start_analisa') {
            if (is_null($batch->tgl_analisa_ruahan)) {
                $batch->tgl_analisa_ruahan = now()->format('Y-m-d');
                $batch->save();
            }
            return back()->with('success', 'Analisa ruahan dimulai.');
        }

        if ($qcAction === 'stop_release') {
            if (is_null($batch->tgl_rilis_ruahan)) {
                $batch->tgl_rilis_ruahan = now()->format('Y-m-d');
                $batch->save();
            }
            return back()->with('success', 'Ruahan telah direlease.');
        }

        return back()->with('success', 'Tidak ada perubahan.');
    }
}
