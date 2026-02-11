<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class QcJobSheetController extends Controller
{
    /**
     * INDEX: Job Sheet QC yang masih aktif dikerjakan QC
     * - Belum pernah dikirim ke Review (status_jobsheet null / pending), ATAU
     * - Sudah dikirim, tapi status_review = 'hold' (dikembalikan dari Review).
     */
    public function index(Request $request)
    {
        $q     = $request->get('q', '');
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $rows = ProduksiBatch::query()
            ->whereNotNull('qty_batch')
            ->where('status_qty_batch', 'confirmed')

            ->where(function ($qb) {
                $qb
                    ->whereNull('status_jobsheet')
                    ->orWhere('status_jobsheet', 'pending')
                    ->orWhere(function ($q2) {
                        $q2->where('status_jobsheet', 'done')
                           ->where('status_review', 'hold');
                    });
            })

            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('nama_produk', 'like', "%{$q}%")
                        ->orWhere('no_batch', 'like', "%{$q}%")
                        ->orWhere('kode_batch', 'like', "%{$q}%");
                });
            })

            ->when($bulan !== null && $bulan !== '', function ($qb) use ($bulan) {
                $qb->where('bulan', (int) $bulan);
            })

            ->when($tahun !== null && $tahun !== '', function ($qb) use ($tahun) {
                $qb->where('tahun', (int) $tahun);
            })

            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate(25);

        return view('qc_jobsheet.index', compact(
            'rows',
            'q',
            'bulan',
            'tahun'
        ));
    }

    /**
     * RIWAYAT: Job Sheet yang sudah DIKIRIM ke review
     * (status_jobsheet = 'done') – termasuk yang released / rejected / masih pending.
     */
    public function history(Request $request)
    {
        $q     = $request->get('q', '');
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $rows = ProduksiBatch::query()
            ->whereNotNull('qty_batch')
            ->where('status_qty_batch', 'confirmed')
            ->where('status_jobsheet', 'done')

            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('nama_produk', 'like', "%{$q}%")
                        ->orWhere('no_batch', 'like', "%{$q}%")
                        ->orWhere('kode_batch', 'like', "%{$q}%");
                });
            })

            ->when($bulan !== null && $bulan !== '', function ($qb) use ($bulan) {
                $qb->where('bulan', (int) $bulan);
            })

            ->when($tahun !== null && $tahun !== '', function ($qb) use ($tahun) {
                $qb->where('tahun', (int) $tahun);
            })

            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate(25);

        return view('qc_jobsheet.history', compact(
            'rows',
            'q',
            'bulan',
            'tahun'
        ));
    }

    /**
     * Form Job Sheet untuk 1 batch.
     */
    public function edit(ProduksiBatch $batch)
    {
        $defaultKonfirmasi = $batch->tgl_konfirmasi_produksi ?: now()->toDateString();

        $jobsheet = (object) [
            'tgl_konfirmasi_produksi' => $defaultKonfirmasi,
            'tgl_terima_jobsheet'     => $batch->tgl_terima_jobsheet,
        ];

        return view('qc_jobsheet.edit', compact('batch', 'jobsheet'));
    }

    /**
     * Simpan Job Sheet dari form.
     * - Hanya mengubah tanggal + status_jobsheet (default pending).
     * - Tidak menyentuh status_review / catatan_review.
     */
    public function update(Request $request, ProduksiBatch $batch)
    {
        $data = $request->validate([
            'tgl_konfirmasi_produksi' => ['required', 'date'],
            'tgl_terima_jobsheet'     => ['nullable', 'date'],
        ]);

        if (empty($batch->status_jobsheet)) {
            $data['status_jobsheet'] = 'pending';
        }

        $batch->update($data);

        return redirect()
            ->route('qc-jobsheet.index')
            ->with('ok', 'Job Sheet QC berhasil disimpan.');
    }

    /**
     * KONFIRMASI:
     * - status_jobsheet = 'done'
     * - tgl_terima_jobsheet diisi kalau masih kosong
     * - status_review = 'pending' (atau reset dari 'hold')
     * - catatan_review ditambah jejak bahwa jobsheet dikirim / dikirim ulang.
     */
    public function confirm(ProduksiBatch $batch)
    {
        $update = [
            'status_jobsheet'     => 'done',
            'tgl_terima_jobsheet' => $batch->tgl_terima_jobsheet ?: now()->toDateString(),
        ];

        $batch->update($update);

        $catatanLama = trim($batch->catatan_review ?? '');
        $tambahan    = 'Job Sheet QC dikonfirmasi oleh QC pada ' . now()->format('d-m-Y') . '.';

        $batch->update([
            'status_review'  => 'pending',
            'catatan_review' => trim($catatanLama . ' ' . $tambahan),
        ]);

        return redirect()
            ->route('qc-jobsheet.index')
            ->with('ok', 'Job Sheet QC telah dikonfirmasi dan dikirim ke Review.');
    }
}