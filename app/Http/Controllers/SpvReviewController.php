<?php

namespace App\Http\Controllers;

use App\Models\SpvDoc;
use App\Models\SpvDocItem;
use App\Models\GojDoc;
use App\Models\GojDocItem;
use App\Models\ProduksiBatch;
use App\Models\GudangRelease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SpvReviewController extends Controller
{
    /**
     * List SPV documents (index)
     * Query params:
     *  - q: search doc_no
     *  - status: PENDING|APPROVED|REJECTED|ALL
     */
    public function index(Request $request)
    {
        $q = (string) $request->get('q', '');
        $status = strtoupper((string) $request->get('status', 'PENDING'));

        $query = SpvDoc::query()
            ->when($q !== '', fn($qb) => $qb->where('doc_no', 'like', "%{$q}%"))
            ->when($status !== 'ALL', fn($qb) => $qb->where('status', $status));

        // default order desc
        $rows = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('spv.index', compact('rows', 'q', 'status'));
    }

    /**
     * History page: show processed docs (APPROVED / REJECTED)
     * Query params:
     *  - q: search doc_no
     *  - status: APPROVED|REJECTED|ALL
     */
    public function history(Request $request)
    {
        $q = (string) $request->get('q', '');
        $status = strtoupper((string) $request->get('status', 'ALL'));

        $query = SpvDoc::query()
            ->when($q !== '', fn($qb) => $qb->where('doc_no', 'like', "%{$q}%"));

        if ($status !== 'ALL') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['APPROVED', 'REJECTED']);
        }

        $rows = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('spv.history', compact('rows', 'q', 'status'));
    }

    /**
     * Detail SPV document (show)
     */
    public function detail(SpvDoc $spvDoc)
    {
        $spvDoc->load(['items']);
        return view('spv.detail', compact('spvDoc'));
    }

    /**
     * Approve SPV -> create GOJ and mark gudang_release accordingly
     */
    public function approve(SpvDoc $spvDoc, Request $request)
    {
        // optional policy check (if you have policies)
        if (method_exists($this, 'authorize')) {
            try {
                $this->authorize('approve', $spvDoc);
            } catch (\Throwable $e) {
                // ignore if no policy
            }
        }

        // double-check status to prevent double-processing
        if (strtoupper($spvDoc->status) !== 'PENDING') {
            return redirect()->route('spv.index')->with('warning', 'Dokumen SPV sudah diproses sebelumnya.');
        }

        $spvDoc->load('items');

        DB::transaction(function () use ($spvDoc) {
            // mark SPV doc approved (audit fields if exist)
            $spvDoc->status = 'APPROVED';
            if (Schema::hasColumn($spvDoc->getTable(), 'approved_by')) {
                $spvDoc->approved_by = Auth::id();
            }
            if (Schema::hasColumn($spvDoc->getTable(), 'approved_at')) {
                $spvDoc->approved_at = now();
            }
            $spvDoc->save();

            // create GOJ doc
            $docNo = 'GOJ-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

            $goj = GojDoc::create([
                'doc_no'    => $docNo,
                'doc_date'  => now()->toDateString(),
                'status'    => 'PENDING',
                'created_by'=> Auth::id(),
            ]);

            $batchIds = [];

            foreach ($spvDoc->items as $it) {
                GojDocItem::create([
                    'goj_doc_id'         => $goj->id,
                    'produksi_batch_id'  => $it->produksi_batch_id,
                    'nama_produk'        => $it->nama_produk,
                    'batch_no'           => $it->batch_no,
                    'kode_batch'         => $it->kode_batch,
                    'tgl_release'        => $it->tgl_release,
                    'tgl_expired'        => $it->tgl_expired,
                    'kemasan'            => $it->kemasan,
                    'isi'                => $it->isi,
                    'jumlah'             => $it->jumlah,
                    'status_gudang'      => $it->status_gudang,
                ]);

                if (!empty($it->produksi_batch_id)) {
                    $batchIds[] = $it->produksi_batch_id;
                }
            }

            $batchIds = collect($batchIds)->unique()->values()->all();

            if (!empty($batchIds)) {
                $update = [
                    'goj_doc_id' => $goj->id,
                    'goj_status' => 'PENDING',
                ];

                // optional SPV fields in GudangRelease
                $grTable = (new GudangRelease())->getTable();
                if (Schema::hasColumn($grTable, 'spv_doc_id')) {
                    $update['spv_doc_id'] = $spvDoc->id;
                }
                // set spv_status/spv_approved_by/spv_approved_at if those columns exist
                if (Schema::hasColumn($grTable, 'spv_status')) {
                    $update['spv_status'] = 'APPROVED';
                }
                if (Schema::hasColumn($grTable, 'spv_approved_by')) {
                    $update['spv_approved_by'] = Auth::id();
                }
                if (Schema::hasColumn($grTable, 'spv_approved_at')) {
                    $update['spv_approved_at'] = now();
                }
                if (Schema::hasColumn($grTable, 'spv_note')) {
                    $update['spv_note'] = null;
                }

                // reset any GOJ reject fields
                if (Schema::hasColumn($grTable, 'goj_rejected_by')) {
                    $update['goj_rejected_by'] = null;
                }
                if (Schema::hasColumn($grTable, 'goj_rejected_at')) {
                    $update['goj_rejected_at'] = null;
                }

                GudangRelease::query()
                    ->whereIn('produksi_batch_id', $batchIds)
                    ->update($update);
            }
        });

        return redirect()->route('spv.index')->with('success', 'SPV Approve berhasil — data diteruskan ke GOJ (PENDING).');
    }

    /**
     * Reject SPV -> tandai spv_status REJECTED & tulis catatan (kembali ke operator)
     */
    public function reject(SpvDoc $spvDoc, Request $request)
    {
        $data = $request->validate([
            'catatan' => 'required|string|max:500',
        ]);

        if (strtoupper($spvDoc->status) !== 'PENDING') {
            return redirect()->route('spv.index')->with('warning', 'Dokumen SPV sudah diproses sebelumnya.');
        }

        $spvDoc->load('items');

        DB::transaction(function () use ($spvDoc, $data) {
            $spvDoc->status = 'REJECTED';
            if (Schema::hasColumn($spvDoc->getTable(), 'rejected_by')) {
                $spvDoc->rejected_by = Auth::id();
            }
            if (Schema::hasColumn($spvDoc->getTable(), 'rejected_at')) {
                $spvDoc->rejected_at = now();
            }
            if (Schema::hasColumn($spvDoc->getTable(), 'spv_note')) {
                $spvDoc->spv_note = $data['catatan'];
            }
            $spvDoc->save();

            $batchIds = $spvDoc->items->pluck('produksi_batch_id')->filter()->unique()->values()->all();

            if (!empty($batchIds)) {
                $update = [
                    'spv_status' => 'REJECTED',
                ];

                $grTable = (new GudangRelease())->getTable();

                if (Schema::hasColumn($grTable, 'spv_note')) {
                    $update['spv_note'] = $data['catatan'];
                }
                if (Schema::hasColumn($grTable, 'spv_rejected_by')) {
                    $update['spv_rejected_by'] = Auth::id();
                }
                if (Schema::hasColumn($grTable, 'spv_rejected_at')) {
                    $update['spv_rejected_at'] = now();
                }

                GudangRelease::query()
                    ->whereIn('produksi_batch_id', $batchIds)
                    ->update($update);
            }
        });

        return redirect()->route('spv.index')->with('success', 'SPV Reject berhasil — kembalikan ke Operator untuk perbaikan.');
    }
}
