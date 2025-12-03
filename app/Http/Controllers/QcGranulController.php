<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class QcGranulController extends Controller
{
    /**
     * LIST DATA AKTIF (belum release granul)
     * Hanya untuk:
     * - TABLET_NON_SALUT
     * - TABLET_SALUT
     * - KAPSUL
     *
     * Setelah tgl_rilis_granul diisi:
     * - tipe_alur TABLET_*  → muncul di TabletingController@index
     * - tipe_alur KAPSUL    → muncul di CapsuleFillingController@index
     */
    public function index(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $alurGranul = ['TABLET_NON_SALUT', 'TABLET_SALUT', 'KAPSUL'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurGranul)
            ->whereNotNull('tgl_mixing')     // minimal sudah mixing
            ->whereNull('tgl_rilis_granul'); // khusus yang blm release granul

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

        return view('produksi.qc_granul.index', [
            'batches' => $batches,
            'search'  => $search,
            'bulan'   => $bulan,
            'tahun'   => $tahun,
        ]);
    }

    /**
     * RIWAYAT (sudah release granul)
     */
    public function history(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $alurGranul = ['TABLET_NON_SALUT', 'TABLET_SALUT', 'KAPSUL'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurGranul)
            ->whereNotNull('tgl_rilis_granul');

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

        return view('produksi.qc_granul.history', [
            'batches' => $batches,
            'search'  => $search,
            'bulan'   => $bulan,
            'tahun'   => $tahun,
        ]);
    }

    /**
     * UPDATE (Confirm Datang / Start Analisa / Stop Release)
     */
    public function update(Request $request, ProduksiBatch $batch)
    {
        $action   = $request->input('action');     // confirm_datang
        $qcAction = $request->input('qc_action');  // start_analisa / stop_release

        // 1) Konfirmasi Tanggal Datang
        if ($action === 'confirm_datang') {
            $data = $request->validate([
                'tgl_datang_granul' => ['required', 'date'],
            ]);

            $batch->tgl_datang_granul = $data['tgl_datang_granul'];
            $batch->save();

            return back()->with('success', 'Tanggal datang granul berhasil dikonfirmasi.');
        }

        // 2) Start Analisa
        if ($qcAction === 'start_analisa') {
            if (is_null($batch->tgl_analisa_granul)) {
                // boleh now() atau format Y-m-d, sesuaikan dengan cast di model
                $batch->tgl_analisa_granul = now(); // atau now()->format('Y-m-d');
                $batch->save();
            }
            return back()->with('success', 'Analisa granul dimulai.');
        }

        // 3) Stop → RELEASE
        if ($qcAction === 'stop_release') {
            if (is_null($batch->tgl_rilis_granul)) {
                $batch->tgl_rilis_granul = now(); // atau now()->format('Y-m-d');
                $batch->save();
            }
            return back()->with('success', 'Granul telah direlease.');
        }

        return back()->with('success', 'Tidak ada perubahan.');
    }
}
