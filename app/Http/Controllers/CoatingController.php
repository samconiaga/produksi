<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class CoatingController extends Controller
{
    /**
     * Mapping nama step → kolom datetime di tabel.
     */
    private array $stepMap = [
        'main'      => ['mulai' => 'tgl_mulai_coating',           'selesai' => 'tgl_coating'],
        'inti'      => ['mulai' => 'tgl_mulai_coating_inti',      'selesai' => 'tgl_coating_inti'],
        'dasar'     => ['mulai' => 'tgl_mulai_coating_dasar',     'selesai' => 'tgl_coating_dasar'],
        'warna'     => ['mulai' => 'tgl_mulai_coating_warna',     'selesai' => 'tgl_coating_warna'],
        'polishing' => ['mulai' => 'tgl_mulai_coating_polishing', 'selesai' => 'tgl_coating_polishing'],
    ];

    /* =========================================================
     * INDEX – batch BELUM selesai Coating
     * Hanya untuk tipe_alur TABLET_SALUT
     * =======================================================*/
    public function index(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $query = ProduksiBatch::with('produksi')
            ->where('tipe_alur', 'TABLET_SALUT')
            ->whereNotNull('tgl_tableting')  // setelah Tableting
            ->whereNull('tgl_coating');      // belum selesai Coating (summary)

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
            ->paginate(25);

        $batches->appends($request->query());

        return view('produksi.coating.index', [
            'batches' => $batches,
            'search'  => $search,
            'bulan'   => $bulan,
            'tahun'   => $tahun,
        ]);
    }

    /* =========================================================
     * HALAMAN DETAIL PER BATCH
     * =======================================================*/
    public function show(ProduksiBatch $batch)
    {
        $batch->load('produksi');

        return view('produksi.coating.show', [
            'batch'         => $batch,
            'isTabletSalut' => $batch->isTabletSalut(),
        ]);
    }

    /* =========================================================
     * HISTORY – batch SUDAH selesai Coating
     * =======================================================*/
    public function history(Request $request)
    {
        $search = trim($request->get('q', ''));
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $query = ProduksiBatch::with('produksi')
            ->where('tipe_alur', 'TABLET_SALUT')
            ->whereNotNull('tgl_tableting')
            ->whereNotNull('tgl_coating'); // summary selesai

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
            ->paginate(25);

        $batches->appends($request->query());

        return view('produksi.coating.history', [
            'batches' => $batches,
            'search'  => $search,
            'bulan'   => $bulan,
            'tahun'   => $tahun,
        ]);
    }

    /* ====== START / STOP & fungsi EAZ tetap sama persis seperti kodenmu ====== */

    public function start(Request $request, ProduksiBatch $batch)
    {
        $stepKey = $request->input('step', 'main');

        if (! $batch->isTabletSalut()) {
            $stepKey = 'main';
        }

        $cols = $this->stepMap[$stepKey] ?? $this->stepMap['main'];

        if ($batch->{$cols['mulai']}) {
            return back()->with('success', "Step Coating {$stepKey} sudah pernah dimulai.");
        }

        $now = Carbon::now();

        $batch->update([
            $cols['mulai']  => $now,
            'status_proses' => 'COATING_' . strtoupper($stepKey) . '_MULAI',
        ]);

        if ($stepKey !== 'main' && ! $batch->tgl_mulai_coating) {
            $batch->update(['tgl_mulai_coating' => $now]);
        }

        return back()->with('success', "Coating step {$stepKey} dimulai untuk batch tersebut.");
    }

    public function stop(Request $request, ProduksiBatch $batch)
    {
        $stepKey = $request->input('step', 'main');

        if (! $batch->isTabletSalut()) {
            $stepKey = 'main';
        }

        $cols = $this->stepMap[$stepKey] ?? $this->stepMap['main'];

        if (! $batch->{$cols['mulai']}) {
            $batch->{$cols['mulai']} = Carbon::now();
        }

        if ($batch->{$cols['selesai']}) {
            return back()->with('success', "Step Coating {$stepKey} sudah selesai sebelumnya.");
        }

        $now = Carbon::now();

        $batch->{$cols['selesai']} = $now;
        $batch->status_proses      = 'COATING_' . strtoupper($stepKey) . '_SELESAI';

        if ($stepKey === 'main') {
            $batch->tgl_coating = $now;
        } elseif ($stepKey === 'polishing' && ! $batch->tgl_coating) {
            $batch->tgl_coating = $now;
        }

        if (! $batch->tgl_mulai_coating) {
            $batch->tgl_mulai_coating = $batch->{$cols['mulai']} ?? $now;
        }

        $batch->save();

        return back()->with('success', "Coating step {$stepKey} selesai untuk batch tersebut.");
    }

    public function splitEaz(ProduksiBatch $batch)
    {
        if (! Str::contains($batch->kode_batch, 'EA-')) {
            return back()->with('success', 'Batch ini tidak bisa di-split ke EAZ.');
        }

        $kodeEaz = Str::replaceFirst('EA-', 'EAZ-', $batch->kode_batch);

        $sudahAda = ProduksiBatch::where('kode_batch', $kodeEaz)
            ->where('no_batch', $batch->no_batch)
            ->exists();

        if ($sudahAda) {
            return back()->with('success', 'Mesin 2 (EAZ) sudah pernah dibuat.');
        }

        $new = $batch->replicate();
        $new->kode_batch = $kodeEaz;
        $new->save();

        return back()->with('success', 'Batch mesin 2 (EAZ) berhasil dibuat.');
    }

    public function destroyEaz(ProduksiBatch $batch)
    {
        if (! Str::contains($batch->kode_batch, 'EAZ-')) {
            return back()->with('success', 'Batch ini bukan mesin 2 (EAZ). Tidak dihapus.');
        }

        $batch->delete();

        return back()->with('success', 'Batch mesin 2 (EAZ) berhasil dihapus.');
    }
}
