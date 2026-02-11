<?php

namespace App\Http\Controllers;

use App\Models\GojDoc;
use App\Models\GojDocItem;
use App\Models\GudangRelease;
use App\Models\ProduksiBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class GojController extends Controller
{
    /**
     * Index (Review) - tampilkan GOJ dokumen yang masih PENDING
     */
    public function index(Request $request)
    {
        $q      = trim((string) $request->get('q', ''));
        $from   = $request->get('from');
        $to     = $request->get('to');

        $docs = GojDoc::query()
            ->where('status', 'PENDING')
            ->when($q !== '', fn($qb) => $qb->where('doc_no', 'like', "%{$q}%"))
            ->when($from, fn($qb) => $qb->whereDate('doc_date', '>=', $from))
            ->when($to, fn($qb) => $qb->whereDate('doc_date', '<=', $to))
            ->withCount('items')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('goj.index', compact('docs', 'q', 'from', 'to'));
    }

    /**
     * History - dokumen GOJ yang sudah APPROVED / REJECTED
     */
    public function history(Request $request)
    {
        $q      = trim((string) $request->get('q', ''));
        $status = $request->get('status', 'APPROVED'); // default riwayat
        $from   = $request->get('from');
        $to     = $request->get('to');

        $docs = GojDoc::query()
            ->whereIn('status', ['APPROVED', 'REJECTED'])
            ->when($q !== '', fn($qb) => $qb->where('doc_no', 'like', "%{$q}%"))
            ->when(in_array($status, ['APPROVED', 'REJECTED'], true), fn($qb) => $qb->where('status', $status))
            ->when($from, fn($qb) => $qb->whereDate('doc_date', '>=', $from))
            ->when($to, fn($qb) => $qb->whereDate('doc_date', '<=', $to))
            ->withCount('items')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('goj.history', compact('docs', 'q', 'status', 'from', 'to'));
    }

    /**
     * Show GOJ detail
     */
    public function show(GojDoc $goj)
    {
        $goj->load('items');
        return view('goj.show', compact('goj'));
    }

    /**
     * Preview/Print (prepare ids for preview/print page)
     */
    public function preview(GojDoc $goj)
    {
        $goj->load('items');
        $ids = $goj->items->pluck('produksi_batch_id')->filter()->values()->all();
        return view('goj.preview_post', compact('ids', 'goj'));
    }

    /**
     * APPROVE GOJ -> tandai GOJ approved, update gudang_release & produksi_batch
     * Redirect ke riwayat (APPROVED)
     *
     * NOTE: IMPORTANT - DO NOT modify spv_* columns here.
     * SPV fields must be handled only by SpvReviewController::approve()
     */
    public function approve(GojDoc $goj, Request $request)
    {
        // jika sudah diproses, jangan lakukan apa-apa
        if ($goj->status !== 'PENDING') {
            return redirect()
                ->route('goj.history', ['status' => 'APPROVED'])
                ->with('success', 'Dokumen sudah diproses.');
        }

        // ambil batchIds dari items
        $batchIds = $goj->items()->pluck('produksi_batch_id')->filter()->unique()->values()->all();

        DB::transaction(function () use ($goj, $batchIds) {
            // update goj
            $goj->status      = 'APPROVED';
            $goj->approved_by = Auth::id();
            $goj->approved_at = now();
            $goj->save();

            if (!empty($batchIds)) {
                // persiapan update untuk tabel gudang_releases
                // IMPORTANT: do NOT set/overwrite spv_* fields here
                $update = [
                    'status'      => 'APPROVED',
                    'catatan'     => null,
                    'goj_doc_id'  => $goj->id,
                    'goj_status'  => 'APPROVED',
                    'goj_note'    => null,
                    'goj_approved_by' => Auth::id(),
                    'goj_approved_at' => now(),
                    'goj_rejected_by' => null,
                    'goj_rejected_at' => null,
                ];

                // Keep this block minimal — avoid touching spv_* to prevent overwriting SPV signatures
                GudangRelease::query()
                    ->whereIn('produksi_batch_id', $batchIds)
                    ->update($update);
            }

            // update ProduksiBatch flags (jika ada kolom terkait GOJ/returned)
            if (!empty($batchIds)) {
                $pbUpdate = [];
                if (Schema::hasColumn((new ProduksiBatch())->getTable(), 'goj_returned')) {
                    $pbUpdate['goj_returned'] = false;
                }
                if (Schema::hasColumn((new ProduksiBatch())->getTable(), 'goj_return_note')) {
                    $pbUpdate['goj_return_note'] = null;
                }
                if (Schema::hasColumn((new ProduksiBatch())->getTable(), 'goj_returned_at')) {
                    $pbUpdate['goj_returned_at'] = null;
                }
                if (!empty($pbUpdate)) {
                    ProduksiBatch::query()->whereIn('id', $batchIds)->update($pbUpdate);
                }
            }
        });

        return redirect()
            ->route('goj.history', ['status' => 'APPROVED'])
            ->with('success', 'GOJ Approved. Dokumen masuk Riwayat.');
    }

    /**
     * REJECT GOJ -> tandai GOJ rejected, update gudang_release agar muncul di LPHP
     * Tetap berada di halaman GOJ detail setelah reject.
     */
    public function reject(GojDoc $goj, Request $request)
    {
        // hanya bisa reject dokumen yang masih PENDING
        if ($goj->status !== 'PENDING') {
            return redirect()
                ->route('goj.show', $goj->id)
                ->with('success', 'Dokumen sudah diproses.');
        }

        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $batchIds = $goj->items()->pluck('produksi_batch_id')->filter()->unique()->values()->all();

        DB::transaction(function () use ($goj, $data, $batchIds) {
            $goj->status        = 'REJECTED';
            $goj->rejected_by   = Auth::id();
            $goj->rejected_at   = now();
            // simpan alasan reject
            if (Schema::hasColumn($goj->getTable(), 'reject_reason')) {
                $goj->reject_reason = $data['reason'];
            } else {
                // fallback simpan ke kolom note jika tersedia
                if (Schema::hasColumn($goj->getTable(), 'note')) {
                    $goj->note = $data['reason'];
                }
            }
            $goj->save();

            if (!empty($batchIds)) {
                $update = [
                    'status'       => 'REJECTED',
                    'catatan'      => $data['reason'],
                    'goj_doc_id'   => $goj->id,
                    'goj_status'   => 'REJECTED',
                    'goj_note'     => $data['reason'],
                    'goj_approved_by' => null,
                    'goj_approved_at' => null,
                    'goj_rejected_by' => Auth::id(),
                    'goj_rejected_at' => now(),
                ];

                GudangRelease::query()
                    ->whereIn('produksi_batch_id', $batchIds)
                    ->update($update);
            }

            // update ProduksiBatch flags (menandakan returned)
            if (!empty($batchIds)) {
                $pbUpdate = [];
                if (Schema::hasColumn((new ProduksiBatch())->getTable(), 'goj_returned')) {
                    $pbUpdate['goj_returned'] = true;
                }
                if (Schema::hasColumn((new ProduksiBatch())->getTable(), 'goj_return_note')) {
                    $pbUpdate['goj_return_note'] = $data['reason'];
                }
                if (Schema::hasColumn((new ProduksiBatch())->getTable(), 'goj_returned_at')) {
                    $pbUpdate['goj_returned_at'] = now();
                }
                if (!empty($pbUpdate)) {
                    ProduksiBatch::query()->whereIn('id', $batchIds)->update($pbUpdate);
                }
            }
        });

        return redirect()
            ->route('goj.show', $goj->id)
            ->with('success', 'GOJ Rejected. Data sudah ditandai REJECTED & masuk antrian LPHP. (Tidak auto pindah halaman)');
    }
}