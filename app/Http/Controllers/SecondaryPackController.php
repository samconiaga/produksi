<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class SecondaryPackController extends Controller
{
    /**
     * INDEX – batch yang butuh SECONDARY PACK
     * Syarat umum:
     * - tgl_primary_pack != null      (Primary selesai)
     * - tgl_secondary_pack_1 == null  (Secondary 1 belum selesai)
     *
     * Tambahan:
     * - jika tipe_alur = CAIRAN_LUAR → wajib tgl_rilis_ruahan_akhir != null
     * - selain CAIRAN_LUAR → tidak butuh ruahan akhir, boleh langsung Secondary
     */
    public function index(Request $request)
    {
        $q     = trim($request->get('q', ''));
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $rows = ProduksiBatch::with('produksi')
            ->whereNotNull('tgl_primary_pack')
            ->whereNull('tgl_secondary_pack_1')
            ->where(function ($qMain) {
                $qMain
                    // CAIRAN_LUAR → harus lewat QC Ruahan Akhir dulu
                    ->where(function ($q1) {
                        $q1->where('tipe_alur', 'CAIRAN_LUAR')
                           ->whereNotNull('tgl_rilis_ruahan_akhir');
                    })
                    // selain CAIRAN_LUAR → tidak perlu ruahan akhir
                    ->orWhere(function ($q2) {
                        $q2->where('tipe_alur', '<>', 'CAIRAN_LUAR');
                    });
            })
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('nama_produk', 'like', "%{$q}%")
                        ->orWhere('no_batch', 'like', "%{$q}%")
                        ->orWhere('kode_batch', 'like', "%{$q}%");
                });
            })
            ->when($bulan !== null && $bulan !== '' && $bulan !== 'all', function ($qb) use ($bulan) {
                $qb->where('bulan', (int) $bulan);
            })
            ->when($tahun !== null && $tahun !== '', function ($qb) use ($tahun) {
                $qb->where('tahun', (int) $tahun);
            })
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('secondary_pack.index', compact('rows', 'q', 'bulan', 'tahun'));
    }

    /**
     * HISTORY – batch yang sudah selesai SECONDARY PACK 1
     * (boleh semua tipe_alur, tidak perlu dibatasi lagi)
     */
    public function history(Request $request)
    {
        $q     = trim($request->get('q', ''));
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $rows = ProduksiBatch::with('produksi')
            ->whereNotNull('tgl_secondary_pack_1')
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('nama_produk', 'like', "%{$q}%")
                        ->orWhere('no_batch', 'like', "%{$q}%")
                        ->orWhere('kode_batch', 'like', "%{$q}%");
                });
            })
            ->when($bulan !== null && $bulan !== '' && $bulan !== 'all', function ($qb) use ($bulan) {
                $qb->where('bulan', (int) $bulan);
            })
            ->when($tahun !== null && $tahun !== '', function ($qb) use ($tahun) {
                $qb->where('tahun', (int) $tahun);
            })
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('secondary_pack.history', compact('rows', 'q', 'bulan', 'tahun'));
    }

    /**
     * START – mulai Secondary Pack 1 (realtime datetime)
     */
    public function start(Request $request, ProduksiBatch $batch)
    {
        if ($batch->tgl_mulai_secondary_pack_1) {
            return back()->withErrors([
                'secondary' => 'Secondary Pack sudah dimulai untuk batch ini.',
            ]);
        }

        // Safety: pastikan primary sudah selesai
        if (!$batch->tgl_primary_pack) {
            return back()->withErrors([
                'secondary' => 'Secondary Pack tidak bisa dimulai sebelum Primary Pack selesai.',
            ]);
        }

        // Khusus CAIRAN_LUAR: pastikan Ruahan Akhir sudah rilis
        if ($batch->tipe_alur === 'CAIRAN_LUAR' && !$batch->tgl_rilis_ruahan_akhir) {
            return back()->withErrors([
                'secondary' => 'Batch CAIRAN_LUAR harus lewat QC Ruahan Akhir dulu sebelum Secondary Pack.',
            ]);
        }

        $batch->update([
            'tgl_mulai_secondary_pack_1' => now(),   // full datetime
            'status_proses'              => 'SECONDARY_PACK_MULAI',
        ]);

        return back()->with('ok', 'Secondary Pack berhasil dimulai.');
    }

    /**
     * STOP – selesai Secondary Pack 1 (realtime datetime),
     * lalu lanjut ke form Qty Batch.
     */
    public function stop(Request $request, ProduksiBatch $batch)
    {
        if (!$batch->tgl_mulai_secondary_pack_1) {
            return back()->withErrors([
                'secondary' => 'Tidak bisa menghentikan Secondary Pack sebelum proses dimulai.',
            ]);
        }

        // Kalau sudah selesai sebelumnya, langsung arahkan ke Qty
        if ($batch->tgl_secondary_pack_1) {
            return redirect()
                ->route('secondary-pack.qty.form', $batch->id)
                ->with('ok', 'Secondary Pack sudah selesai. Silakan cek / lengkapi Qty Batch.');
        }

        $batch->update([
            'tgl_secondary_pack_1' => now(),  // full datetime
            'status_proses'        => 'SECONDARY_PACK_SELESAI',
        ]);

        return redirect()
            ->route('secondary-pack.qty.form', $batch->id)
            ->with('ok', 'Secondary Pack berhasil diselesaikan. Silakan input Qty Batch.');
    }

    /**
     * FORM QTY BATCH setelah Secondary selesai.
     */
    public function qtyForm(ProduksiBatch $batch)
    {
        return view('secondary_pack.qty_form', compact('batch'));
    }

    /**
     * SIMPAN QTY BATCH.
     */
    public function qtySave(Request $request, ProduksiBatch $batch)
    {
        $data = $request->validate([
            'qty_batch' => ['required', 'integer', 'min:0'],
        ]);

        $batch->update([
            'qty_batch'        => $data['qty_batch'],
            'status_qty_batch' => 'pending',
        ]);

        return redirect()
            ->route('qty-batch.index')
            ->with('ok', 'Qty Batch berhasil disimpan.');
    }
}
