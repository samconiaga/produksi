<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class SamplingController extends Controller
{
    /**
     * INDEX: menampilkan data sampling yang masih aktif
     * (status_sampling: NULL / 'pending' / 'accepted').
     */
    public function index(Request $request)
    {
        $q      = $request->get('q', '');
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        $rows = ProduksiBatch::query()
            ->where('status_qty_batch', 'confirmed')

            // Hanya tampilkan yang BELUM final:
            // status_sampling NULL, 'pending', atau 'accepted'
            ->where(function ($qb) {
                $qb->whereNull('status_sampling')
                   ->orWhere('status_sampling', 'pending')
                   ->orWhere('status_sampling', 'accepted');
            })

            // Search
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($s) use ($q) {
                    $s->where('nama_produk', 'like', "%{$q}%")
                      ->orWhere('kode_batch', 'like', "%{$q}%")
                      ->orWhere('no_batch', 'like', "%{$q}%");
                });
            })

            // Filter bulan & tahun
            ->when($bulan !== null && $bulan !== '', fn($qb) => $qb->where('bulan', (int) $bulan))
            ->when($tahun !== null && $tahun !== '', fn($qb) => $qb->where('tahun', (int) $tahun))

            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate(25);

        return view('sampling.index', compact('rows', 'q', 'bulan', 'tahun'));
    }

    /**
     * RIWAYAT: menampilkan sampling yang SUDAH final
     * (status_sampling: 'confirmed' atau 'rejected').
     */
    public function history(Request $request)
    {
        $q     = $request->get('q', '');
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $rows = ProduksiBatch::query()
            ->where('status_qty_batch', 'confirmed')
            ->whereIn('status_sampling', ['confirmed', 'rejected'])

            // Search
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($s) use ($q) {
                    $s->where('nama_produk', 'like', "%{$q}%")
                      ->orWhere('kode_batch', 'like', "%{$q}%")
                      ->orWhere('no_batch', 'like', "%{$q}%");
                });
            })

            // Filter bulan & tahun
            ->when($bulan !== null && $bulan !== '', fn($qb) => $qb->where('bulan', (int) $bulan))
            ->when($tahun !== null && $tahun !== '', fn($qb) => $qb->where('tahun', (int) $tahun))

            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate(25);

        return view('sampling.history', compact('rows', 'q', 'bulan', 'tahun'));
    }

    /**
     * ACC Sampling → status_sampling = 'accepted'
     * dan tgl_sampling = hari ini (kalau belum diisi).
     * Tetap muncul di INDEX.
     */
    public function acc(ProduksiBatch $batch)
    {
        $batch->update([
            'status_sampling' => 'accepted',
            'tgl_sampling'    => $batch->tgl_sampling ?: now()->toDateString(),
        ]);

        return back()->with('ok', 'Sampling berhasil di-ACCEPT. Silakan Konfirmasi bila sudah final.');
    }

    /**
     * KONFIRMASI Sampling → status_sampling = 'confirmed'
     * - Pindah ke Riwayat
     * - status_review = 'pending'
     * - catatan_review ditambah log singkat.
     */
    public function confirm(ProduksiBatch $batch)
    {
        if ($batch->status_sampling !== 'accepted') {
            return back()->with('ok', 'Sampling belum di-ACCEPT, tidak dapat dikonfirmasi.');
        }

        $batch->update([
            'status_sampling' => 'confirmed',
            'tgl_sampling'    => $batch->tgl_sampling ?: now()->toDateString(),
        ]);

        $catatanLama = trim($batch->catatan_review ?? '');
        $tambahan    = 'Sampling dikonfirmasi oleh QA/QC pada ' . now()->format('d-m-Y') . '.';

        $batch->update([
            'status_review'  => 'pending',
            'catatan_review' => trim($catatanLama . ' ' . $tambahan),
        ]);

        return back()->with('ok', 'Sampling dikonfirmasi, dipindah ke Riwayat dan dikirim ke Review.');
    }

    /**
     * Tolak Sampling → status_sampling = 'rejected'
     * juga otomatis pindah ke Riwayat.
     */
    public function reject(ProduksiBatch $batch)
    {
        $batch->update([
            'status_sampling' => 'rejected',
            'tgl_sampling'    => $batch->tgl_sampling ?: now()->toDateString(),
        ]);

        return back()->with('ok', 'Sampling ditolak dan pindah ke Riwayat.');
    }
}