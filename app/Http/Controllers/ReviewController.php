<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * HALAMAN REVIEW AKTIF
     */
    public function index(Request $request)
    {
        $q      = $request->get('q');
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');
        $status = $request->get('status');

        $rows = ProduksiBatch::with('produksi')
            ->whereNotNull('qty_batch')
            ->where('status_qty_batch', 'confirmed')

            // SEARCH
            ->when($q, function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('nama_produk', 'like', "%{$q}%")
                        ->orWhere('no_batch', 'like', "%{$q}%")
                        ->orWhere('kode_batch', 'like', "%{$q}%");
                });
            })

            // FILTER BULAN
            ->when($bulan, fn($qb) => $qb->where('bulan', (int)$bulan))

            // FILTER TAHUN
            ->when($tahun, fn($qb) => $qb->where('tahun', (int)$tahun))

            // FILTER STATUS
            ->when($status, function ($qb) use ($status) {

                if ($status === 'pending') {
                    $qb->where(function ($sub) {
                        $sub->whereNull('status_review')
                            ->orWhere('status_review', 'pending');
                    });
                } else {
                    $qb->where('status_review', $status);
                }
            })

            // DEFAULT → hanya yg belum final
            ->when(!$status, function ($qb) {
                $qb->where(function ($sub) {
                    $sub->whereNull('status_review')
                        ->orWhereIn('status_review', ['pending', 'hold']);
                });
            })

            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('review.index', compact(
            'rows',
            'q',
            'bulan',
            'tahun',
            'status'
        ));
    }


    /**
     * RIWAYAT (FINAL ONLY)
     */
    public function history(Request $request)
    {
        $q      = $request->get('q');
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');
        $status = $request->get('status');

        $rows = ProduksiBatch::with('produksi')
            ->whereNotNull('qty_batch')
            ->where('status_qty_batch', 'confirmed')
            ->whereIn('status_review', ['released', 'rejected'])

            ->when($q, function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('nama_produk', 'like', "%{$q}%")
                        ->orWhere('no_batch', 'like', "%{$q}%")
                        ->orWhere('kode_batch', 'like', "%{$q}%");
                });
            })

            ->when($bulan, fn($qb) => $qb->where('bulan', (int)$bulan))
            ->when($tahun, fn($qb) => $qb->where('tahun', (int)$tahun))
            ->when($status, fn($qb) => $qb->where('status_review', $status))

            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('review.history', compact(
            'rows',
            'q',
            'bulan',
            'tahun',
            'status'
        ));
    }


    /**
     * HOLD
     */
    public function hold(Request $request, ProduksiBatch $batch)
    {
        $data = $request->validate([
            'return_to'      => ['required', 'in:jobsheet,coa,both'],
            'doc_status'     => ['required', 'in:belum_lengkap,lengkap'],
            'catatan_review' => ['nullable', 'string', 'max:1000'],
        ]);

        $infoDoc = $data['doc_status'] === 'belum_lengkap'
            ? 'Dokumen belum lengkap.'
            : 'Dokumen lengkap (perlu pengecekan ulang).';

        $infoReturn = match ($data['return_to']) {
            'jobsheet' => 'Dikembalikan ke Job Sheet QC.',
            'coa'      => 'Dikembalikan ke COA QC/QA.',
            default    => 'Dikembalikan ke Job Sheet QC dan COA QC/QA.',
        };

        $catatanFinal = trim(
            $infoDoc . ' ' . $infoReturn . ' ' . ($data['catatan_review'] ?? '')
        );

        $update = [
            'status_review'  => 'hold',
            'tgl_review'     => now(),
            'catatan_review' => $catatanFinal,
        ];

        if (in_array($data['return_to'], ['jobsheet', 'both'])) {
            $update['status_jobsheet'] = 'pending';
        }

        if (in_array($data['return_to'], ['coa', 'both'])) {
            $update['status_coa'] = 'pending';
            $update['tgl_qa_terima_coa'] = null;
        }

        $batch->update($update);

        return back()->with('ok', 'Batch berhasil di-HOLD.');
    }


    /**
     * RELEASE — STAY DI HALAMAN (NO REDIRECT)
     */
    public function release(Request $request, ProduksiBatch $batch)
    {
        // 🔥 Anti double-click / race condition
        if ($batch->status_review === 'released') {
            return back()->with('ok', 'Batch sudah pernah di-release.');
        }

        $catatan = $request->input('catatan_review')
            ?: 'Released oleh QA pada ' . now()->format('d-m-Y H:i');

        $batch->update([
            'status_review'  => 'released',
            'tgl_review'     => now(),
            'catatan_review' => $catatan,
        ]);

        // ✅ tetap di halaman yang sama
        return back()->with('ok', 'Batch berhasil di-RELEASE.');
    }


    /**
     * REJECT
     */
    public function reject(Request $request, ProduksiBatch $batch)
    {
        $request->validate([
            'catatan_review' => ['required', 'string'],
        ]);

        $batch->update([
            'status_review'  => 'rejected',
            'tgl_review'     => now(),
            'catatan_review' => $request->catatan_review,
        ]);

        return back()->with('ok', 'Batch berhasil di-REJECT.');
    }
}
