<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class QcReleaseController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $query = ProduksiBatch::with('produksi')
            ->whereNotNull('tgl_mixing')
            ->where(function ($q) {
                $q->whereNull('status_proses')
                  ->orWhere('status_proses', '!=', 'QC RELEASED');
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

        return view('produksi.qc_release.index', [
            'batches' => $batches,
            'search'  => $search,
            'bulan'   => $bulan,
            'tahun'   => $tahun,
        ]);
    }

    public function history(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $query = ProduksiBatch::with('produksi')
            ->where('status_proses', 'QC RELEASED');

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

        return view('produksi.qc_release.history', [
            'batches' => $batches,
            'search'  => $search,
            'bulan'   => $bulan,
            'tahun'   => $tahun,
        ]);
    }

    public function update(Request $request, ProduksiBatch $batch)
    {
        $qcAction = $request->input('qc_action');

        // Mode quick action (Start / Stop)
        if ($qcAction) {
            $this->handleQuickAction($batch, $qcAction);
            return back()->with('success', 'Tanggal QC berhasil diperbarui.');
        }

        // ==== Mode form biasa (Simpan) ====
        $data = $request->validate([
            'tgl_datang_granul'        => ['nullable', 'date'],
            'tgl_datang_tablet'        => ['nullable', 'date'],
            'tgl_datang_ruahan'        => ['nullable', 'date'],
            'tgl_datang_ruahan_akhir'  => ['nullable', 'date'],
            'action'                   => ['nullable', 'string'], // "save"
        ]);

        $fields = [
            'tgl_datang_granul',
            'tgl_datang_tablet',
            'tgl_datang_ruahan',
            'tgl_datang_ruahan_akhir',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $batch->{$field} = $data[$field];
            }
        }

        // Kalau semua release untuk tipe_alur ini sudah terisi,
        // otomatis set status ke QC RELEASED (pindah ke riwayat).
        if ($this->isCompletelyReleased($batch)) {
            $batch->status_proses = 'QC RELEASED';
        }

        $batch->save();

        return back()->with('success', 'Data QC berhasil disimpan.');
    }

    protected function handleQuickAction(ProduksiBatch $batch, string $qcAction): void
    {
        $today = now()->format('Y-m-d');

        switch ($qcAction) {
            // GRANUL - ANALISA
            case 'start_analisa_granul':
            case 'stop_analisa_granul':
                if (is_null($batch->tgl_analisa_granul)) {
                    $batch->tgl_analisa_granul = $today;
                }
                break;

            // GRANUL - RELEASE
            case 'start_release_granul':
            case 'stop_release_granul':
                if (is_null($batch->tgl_rilis_granul)) {
                    $batch->tgl_rilis_granul = $today;
                }
                break;

            // TABLET - ANALISA
            case 'start_analisa_tablet':
            case 'stop_analisa_tablet':
                if (is_null($batch->tgl_analisa_tablet)) {
                    $batch->tgl_analisa_tablet = $today;
                }
                break;

            // TABLET - RELEASE
            case 'start_release_tablet':
            case 'stop_release_tablet':
                if (is_null($batch->tgl_rilis_tablet)) {
                    $batch->tgl_rilis_tablet = $today;
                }
                break;

            // RUAHAN - ANALISA
            case 'start_analisa_ruahan':
            case 'stop_analisa_ruahan':
                if (is_null($batch->tgl_analisa_ruahan)) {
                    $batch->tgl_analisa_ruahan = $today;
                }
                break;

            // RUAHAN - RELEASE
            case 'start_release_ruahan':
            case 'stop_release_ruahan':
                if (is_null($batch->tgl_rilis_ruahan)) {
                    $batch->tgl_rilis_ruahan = $today;
                }
                break;

            // RUAHAN AKHIR - ANALISA
            case 'start_analisa_ruahan_akhir':
            case 'stop_analisa_ruahan_akhir':
                if (is_null($batch->tgl_analisa_ruahan_akhir)) {
                    $batch->tgl_analisa_ruahan_akhir = $today;
                }
                break;

            // RUAHAN AKHIR - RELEASE
            case 'start_release_ruahan_akhir':
            case 'stop_release_ruahan_akhir':
                if (is_null($batch->tgl_rilis_ruahan_akhir)) {
                    $batch->tgl_rilis_ruahan_akhir = $today;
                }
                break;
        }

        $batch->save();
    }

    /**
     * Cek apakah untuk tipe_alur tertentu,
     * semua tahap RELEASE yang diwajibkan sudah terisi.
     */
    protected function isCompletelyReleased(ProduksiBatch $batch): bool
    {
        $tipeAlur = $batch->produksi->tipe_alur ?? $batch->tipe_alur ?? '';

        switch ($tipeAlur) {
            case 'CLO':
                return !is_null($batch->tgl_rilis_ruahan_akhir);

            case 'CAIRAN_LUAR':
                return !is_null($batch->tgl_rilis_ruahan);

            case 'DRY_SYRUP':
                return !is_null($batch->tgl_rilis_ruahan)
                    && !is_null($batch->tgl_rilis_ruahan_akhir);

            case 'TABLET_NON_SALUT':
            case 'TABLET_SALUT':
                return !is_null($batch->tgl_rilis_granul)
                    && !is_null($batch->tgl_rilis_tablet)
                    && !is_null($batch->tgl_rilis_ruahan);

            case 'KAPSUL':
                return !is_null($batch->tgl_rilis_granul)
                    && !is_null($batch->tgl_rilis_ruahan);

            default:
                return !is_null($batch->tgl_rilis_ruahan_akhir);
        }
    }
}
