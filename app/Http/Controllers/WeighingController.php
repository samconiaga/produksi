<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class WeighingController extends Controller
{
    /**
     * Halaman daftar Weighing (WO).
     * Hanya lihat data, tanpa edit.
     */
    public function index(Request $request)
    {
        $q       = $request->get('q', '');
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = (int) $request->get('per_page', 25);

        if ($perPage <= 0) {
            $perPage = 25;
        }

        $rows = ProduksiBatch::with('produksi')
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
            ->paginate($perPage)
            ->withQueryString();

        return view('weighing.index', compact(
            'rows',
            'q',
            'bulan',
            'tahun',
            'perPage'
        ));
    }
}
