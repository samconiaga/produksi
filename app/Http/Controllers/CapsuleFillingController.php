<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use Illuminate\Http\Request;

class CapsuleFillingController extends Controller
{
    /**
     * List batch yang BUTUH Capsule Filling & belum selesai Capsule Filling.
     * Syarat:
     * - tipe_alur = KAPSUL
     * - tgl_mixing       != null (sudah selesai mixing)
     * - tgl_rilis_granul != null (sudah lewat QC Granul)
     * - tgl_capsule_filling = null (belum diisi)
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

        $alurCapsule = ['KAPSUL'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurCapsule)
            ->whereNotNull('tgl_mixing')          // sudah mixing
            ->whereNotNull('tgl_rilis_granul')    // sudah QC granul
            ->whereNull('tgl_capsule_filling');   // belum capsule filling

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

        return view('produksi.capsule_filling.index', [
            'batches' => $batches,
            'search'  => $search,
            'bulan'   => $bulan,
            'tahun'   => $tahun,
        ]);
    }

    /**
     * Riwayat batch yang sudah selesai Capsule Filling.
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

        $alurCapsule = ['KAPSUL'];

        $query = ProduksiBatch::with('produksi')
            ->whereIn('tipe_alur', $alurCapsule)
            ->whereNotNull('tgl_capsule_filling');   // sudah capsule filling

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

        return view('produksi.capsule_filling.history', [
            'batches' => $batches,
            'search'  => $search,
            'bulan'   => $bulan,
            'tahun'   => $tahun,
        ]);
    }

    /**
     * Konfirmasi Capsule Filling per batch.
     */
    public function confirm(Request $request, ProduksiBatch $batch)
    {
        $data = $request->validate([
            'tgl_mulai_capsule_filling' => ['nullable', 'date'],
            'tgl_capsule_filling'       => ['required', 'date'],
        ]);

        $start = $data['tgl_mulai_capsule_filling'] ?? $data['tgl_capsule_filling'];

        $batch->tgl_mulai_capsule_filling = $start;
        $batch->tgl_capsule_filling       = $data['tgl_capsule_filling'];
        $batch->status_proses             = 'CAPSULE_FILLING_SELESAI';

        $batch->save();

        return redirect()
            ->route('capsule-filling.index')
            ->with('success', 'Capsule Filling untuk batch tersebut berhasil dikonfirmasi.');
    }
}
