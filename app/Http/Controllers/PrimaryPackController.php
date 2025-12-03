<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class PrimaryPackController extends Controller
{
    /**
     * INDEX – daftar batch yang butuh PRIMARY PACK
     * Syarat umum (semua tipe_alur):
     * - tgl_rilis_ruahan != null  (QC Ruahan sudah RELEASE)
     * - tgl_primary_pack == null  (Primary belum selesai)
     */
    public function index(Request $request)
    {
        $q     = trim($request->get('q', ''));
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $rows = ProduksiBatch::with('produksi')
            ->whereNotNull('tgl_rilis_ruahan')     // sudah QC Ruahan
            ->whereNull('tgl_primary_pack')        // Primary belum selesai
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

        return view('primary_pack.index', compact('rows', 'q', 'bulan', 'tahun'));
    }

    /**
     * HISTORY – batch yang sudah selesai PRIMARY PACK.
     */
    public function history(Request $request)
    {
        $q     = trim($request->get('q', ''));
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $rows = ProduksiBatch::with('produksi')
            ->whereNotNull('tgl_primary_pack')
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

        return view('primary_pack.history', compact('rows', 'q', 'bulan', 'tahun'));
    }

    /**
     * START – mulai Primary Pack (isi tgl_mulai_primary_pack = now datetime)
     */
    public function start(Request $request, ProduksiBatch $batch)
    {
        // Safety: jangan start kalau sudah pernah mulai
        if ($batch->tgl_mulai_primary_pack) {
            return back()->withErrors([
                'primary' => 'Primary Pack untuk batch ini sudah dimulai.',
            ]);
        }

        $batch->update([
            'tgl_mulai_primary_pack' => now(), // full datetime
            'status_proses'          => 'PRIMARY_PACK_MULAI',
        ]);

        return back()->with('ok', 'Primary Pack berhasil dimulai.');
    }

    /**
     * STOP – selesai Primary Pack (isi tgl_primary_pack = now datetime)
     */
    public function stop(Request $request, ProduksiBatch $batch)
    {
        if (!$batch->tgl_mulai_primary_pack) {
            return back()->withErrors([
                'primary' => 'Tidak bisa menghentikan Primary Pack sebelum proses dimulai.',
            ]);
        }

        if ($batch->tgl_primary_pack) {
            return back()->withErrors([
                'primary' => 'Primary Pack untuk batch ini sudah selesai.',
            ]);
        }

        $batch->update([
            'tgl_primary_pack' => now(), // full datetime
            'status_proses'    => 'PRIMARY_PACK_SELESAI',
        ]);

        return back()->with('ok', 'Primary Pack berhasil diselesaikan.');
    }
}
