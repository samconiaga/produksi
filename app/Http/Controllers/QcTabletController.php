<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class QcTabletController extends Controller
{
    /**
     * Produk Antara Tablet (QC Tablet)
     * Hanya untuk:
     * - tipe_alur = TABLET_SALUT
     * - sudah Tableting
     * - BELUM rilis tablet
     */
    public function index(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $alurTablet = ['TABLET_SALUT'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurTablet)
            ->whereNotNull('tgl_tableting')   // sudah tableting
            ->whereNull('tgl_rilis_tablet');  // belum QC tablet release

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

        return view('produksi.qc_tablet.index', compact('batches', 'search', 'bulan', 'tahun'));
    }

    /**
     * Riwayat Produk Antara Tablet
     */
    public function history(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $alurTablet = ['TABLET_SALUT'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurTablet)
            ->whereNotNull('tgl_rilis_tablet'); // sudah rilis tablet

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

        return view('produksi.qc_tablet.history', compact('batches', 'search', 'bulan', 'tahun'));
    }

    /**
     * Konfirmasi datang / analisa / rilis tablet
     * (fungsi lama tetap, cuma alur datanya sekarang sudah benar)
     */
    public function update(Request $request, ProduksiBatch $batch)
    {
        $action   = $request->input('action');
        $qcAction = $request->input('qc_action');

        if ($action === 'confirm_datang') {
            $data = $request->validate([
                'tgl_datang_tablet' => ['required', 'date'],
            ]);

            $batch->tgl_datang_tablet = $data['tgl_datang_tablet'];
            $batch->save();

            return back()->with('success', 'Tanggal datang tablet berhasil dikonfirmasi.');
        }

        if ($qcAction === 'start_analisa') {
            if (is_null($batch->tgl_analisa_tablet)) {
                $batch->tgl_analisa_tablet = now()->format('Y-m-d');
                $batch->save();
            }
            return back()->with('success', 'Analisa tablet dimulai.');
        }

        if ($qcAction === 'stop_release') {
            if (is_null($batch->tgl_rilis_tablet)) {
                $batch->tgl_rilis_tablet = now()->format('Y-m-d');
                $batch->save();
            }
            return back()->with('success', 'Tablet telah direlease.');
        }

        return back()->with('success', 'Tidak ada perubahan.');
    }
}
