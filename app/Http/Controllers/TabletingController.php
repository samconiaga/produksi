<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class TabletingController extends Controller
{
    /**
     * List batch yang BUTUH Tableting & belum selesai Tableting.
     * Syarat:
     * - tipe_alur IN (TABLET_NON_SALUT, TABLET_SALUT)
     * - tgl_mixing       != null
     * - tgl_rilis_granul != null (sudah QC granul)
     * - tgl_tableting    = null  (belum tableting)
     */
    public function index(Request $request)
    {
        $search  = trim($request->get('q', ''));
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = (int) $request->get('per_page', 25);
        if ($perPage <= 0) {
            $perPage = 25;
        }

        $alurTablet = ['TABLET_NON_SALUT', 'TABLET_SALUT'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurTablet)
            ->whereNotNull('tgl_mixing')
            ->whereNotNull('tgl_rilis_granul')   // sudah QC granul
            ->whereNull('tgl_tableting');        // belum tableting

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
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('produksi.tableting.index', [
            'batches' => $batches,
            'search'  => $search,
            'bulan'   => $bulan,
            'tahun'   => $tahun,
        ]);
    }

    /**
     * Riwayat batch yang sudah selesai Tableting.
     */
    public function history(Request $request)
    {
        $search  = trim($request->get('q', ''));
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = (int) $request->get('per_page', 25);
        if ($perPage <= 0) {
            $perPage = 25;
        }

        $alurTablet = ['TABLET_NON_SALUT', 'TABLET_SALUT'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurTablet)
            ->whereNotNull('tgl_tableting');

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
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('produksi.tableting.history', [
            'batches' => $batches,
            'search'  => $search,
            'bulan'   => $bulan,
            'tahun'   => $tahun,
        ]);
    }

    /**
     * START – realtime mulai Tableting (klik tombol Start).
     * Mengisi tgl_mulai_tableting = now()
     */
    public function start(ProduksiBatch $batch)
    {
        // Kalau sudah pernah start, jangan diubah lagi
        if ($batch->tgl_mulai_tableting) {
            return back()->with('success', 'Tableting untuk batch ini sudah pernah dimulai.');
        }

        $batch->tgl_mulai_tableting = now();
        $batch->status_proses       = 'TABLETING_MULAI';
        $batch->save();

        return back()->with('success', 'Proses Tableting dimulai.');
    }

    /**
     * STOP – realtime selesai Tableting (klik tombol Stop).
     * Mengisi tgl_tableting = now()
     */
    public function stop(ProduksiBatch $batch)
    {
        // Belum pernah start
        if (! $batch->tgl_mulai_tableting) {
            return back()->withErrors([
                'tableting' => 'Tableting belum dimulai untuk batch ini.',
            ]);
        }

        // Sudah pernah selesai
        if ($batch->tgl_tableting) {
            return back()->with('success', 'Tableting untuk batch ini sudah selesai sebelumnya.');
        }

        $batch->tgl_tableting = now();
        $batch->status_proses = 'TABLETING_SELESAI';
        $batch->save();

        return back()->with('success', 'Proses Tableting selesai.');
    }

    /**
     * Konfirmasi manual Tableting via form (opsional).
     * Bisa dipakai kalau mau input tanggal manual, bukan realtime Start/Stop.
     */
    public function confirm(Request $request, ProduksiBatch $batch)
    {
        $data = $request->validate([
            'tgl_mulai_tableting' => ['nullable', 'date'],
            'tgl_tableting'       => ['required', 'date'],
        ]);

        $start = $data['tgl_mulai_tableting'] ?? $data['tgl_tableting'];

        $batch->tgl_mulai_tableting = $start;
        $batch->tgl_tableting       = $data['tgl_tableting'];
        $batch->status_proses       = 'TABLETING_SELESAI';
        $batch->save();

        return redirect()
            ->route('tableting.index')
            ->with('success', 'Tableting untuk batch tersebut berhasil dikonfirmasi.');
    }
}
