<?php

namespace App\Http\Controllers;

use App\Models\GojDoc;
use App\Models\GojDocItem;
use App\Models\GudangRelease;
use App\Models\ProduksiBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SpvController extends Controller
{
    /**
     * Index: daftar dokumen yang menunggu SPV review (status = SPV_PENDING)
     */
    public function index(Request $request)
    {
        $q    = trim((string)$request->get('q', ''));
        $from = $request->get('from');
        $to   = $request->get('to');

        $docs = GojDoc::query()
            ->where('status', 'SPV_PENDING')
            ->when($q !== '', fn($qb) => $qb->where('doc_no', 'like', "%{$q}%"))
            ->when($from, fn($qb) => $qb->whereDate('doc_date', '>=', $from))
            ->when($to, fn($qb) => $qb->whereDate('doc_date', '<=', $to))
            ->withCount('items')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('spv.index', compact('docs', 'q', 'from', 'to'));
    }

    /**
     * History: semua dokumen SPV (atau filter by status)
     */
    public function history(Request $request)
    {
        $q      = trim((string)$request->get('q', ''));
        $status = $request->get('status', 'ALL');
        $from   = $request->get('from');
        $to     = $request->get('to');

        $docs = GojDoc::query()
            ->when($status !== 'ALL', fn($qb) => $qb->where('status', $status))
            ->when($q !== '', fn($qb) => $qb->where('doc_no', 'like', "%{$q}%"))
            ->when($from, fn($qb) => $qb->whereDate('doc_date', '>=', $from))
            ->when($to, fn($qb) => $qb->whereDate('doc_date', '<=', $to))
            ->withCount('items')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('spv.history', compact('docs', 'q', 'status', 'from', 'to'));
    }

    /**
     * Show detail dokumen SPV
     */
    public function show(GojDoc $goj)
    {
        $goj->load('items');
        return view('spv.show', compact('goj'));
    }

    /**
     * Preview -> submit ke LPHP print (reuse GOJ preview flow)
     */
    public function preview(GojDoc $goj)
    {
        $goj->load('items');
        $ids = $goj->items->pluck('produksi_batch_id')->values()->all();
        return view('spv.preview_post', compact('ids', 'goj'));
    }

    /**
     * Approve oleh SPV: dokumen SPV_PENDING -> PENDING (siap diproses GOJ)
     * Update juga gudang_releases untuk batch terkait.
     */
    public function approve(GojDoc $goj, Request $request)
    {
        if ($goj->status !== 'SPV_PENDING') {
            return redirect()
                ->route('spv.index')
                ->with('success', 'Dokumen sudah diproses.');
        }

        $batchIds = $goj->items()->pluck('produksi_batch_id')->unique()->values();

        DB::transaction(function () use ($goj, $batchIds) {
            $goj->status = 'PENDING';
            $goj->approved_by = Auth::id();
            $goj->approved_at = now();
            $goj->save();

            // update GudangRelease per produksi_batch_id
            GudangRelease::query()
                ->whereIn('produksi_batch_id', $batchIds)
                ->update([
                    'goj_doc_id'      => $goj->id,
                    'goj_status'      => 'PENDING',
                    'goj_note'        => null,
                    'goj_approved_by' => Auth::id(),
                    'goj_approved_at' => now(),
                    'goj_rejected_by' => null,
                    'goj_rejected_at' => null,
                ]);

            ProduksiBatch::query()
                ->whereIn('id', $batchIds)
                ->update([
                    'goj_returned'    => false,
                    'goj_return_note' => null,
                    'goj_returned_at' => null,
                ]);
        });

        return redirect()
            ->route('spv.index')
            ->with('success', 'SPV Approve berhasil — dokumen diteruskan ke GOJ.');
    }

    /**
     * Reject oleh SPV: dokumen SPV_PENDING -> REJECTED
     * Update gudang_releases & produksi_batch agar kembali ke LPHP untuk perbaikan.
     */
    public function reject(GojDoc $goj, Request $request)
    {
        if ($goj->status !== 'SPV_PENDING') {
            return redirect()
                ->route('spv.show', $goj->id)
                ->with('success', 'Dokumen sudah diproses.');
        }

        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $batchIds = $goj->items()->pluck('produksi_batch_id')->unique()->values();

        DB::transaction(function () use ($goj, $batchIds, $data) {
            $goj->status = 'REJECTED';
            $goj->rejected_by = Auth::id();
            $goj->rejected_at = now();
            $goj->reject_reason = $data['reason'];
            $goj->save();

            GudangRelease::query()
                ->whereIn('produksi_batch_id', $batchIds)
                ->update([
                    'status'          => 'REJECTED',
                    'catatan'         => $data['reason'],
                    'goj_doc_id'      => $goj->id,
                    'goj_status'      => 'REJECTED',
                    'goj_note'        => $data['reason'],
                    'goj_approved_by' => null,
                    'goj_approved_at' => null,
                    'goj_rejected_by' => Auth::id(),
                    'goj_rejected_at' => now(),
                ]);

            ProduksiBatch::query()
                ->whereIn('id', $batchIds)
                ->update([
                    'goj_returned'    => true,
                    'goj_return_note' => $data['reason'],
                    'goj_returned_at' => now(),
                ]);
        });

        return redirect()
            ->route('spv.show', $goj->id)
            ->with('success', 'SPV Reject berhasil. Data sudah dikembalikan ke LPHP untuk perbaikan.');
    }
}
 