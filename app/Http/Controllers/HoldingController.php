<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use App\Models\HoldingLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HoldingController extends Controller
{
    private function stages(): array
    {
        return [
            'WEIGHING' => 'Weighing (WO)',
            'MIXING' => 'Mixing',
            'QC_GRANUL' => 'Produk Antara Granul (QC)',
            'TABLETING' => 'Tableting',
            'CAPSULE_FILLING' => 'Capsule Filling',
            'COATING' => 'Coating',
            'QC_TABLET' => 'Produk Antara Tablet (QC)',
            'QC_RUAHAN' => 'Produk Ruahan (QC)',
            'QC_RUAHAN_AKHIR' => 'Produk Ruahan Akhir (QC)',
            'PRIMARY_PACK' => 'Primary Pack',
            'SECONDARY_PACK' => 'Secondary Pack',
        ];
    }

    // LIST HOLDING (yang aktif)
    public function index(Request $request)
    {
        $q = (string) $request->get('q', '');

        $rows = ProduksiBatch::query()
            ->with(['produksi', 'holdingLogOpen'])
            ->where('is_holding', true)
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('nama_produk', 'like', "%{$q}%")
                        ->orWhere('no_batch', 'like', "%{$q}%")
                        ->orWhere('kode_batch', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('holding_at')
            ->paginate(25)
            ->withQueryString();

        $batchIds = $rows->getCollection()->pluck('id')->all();

        // summary: total durasi hold yang SUDAH selesai (release/reject) + hitungan hold
        $summary = collect();
        if (!empty($batchIds)) {
            $summary = HoldingLog::query()
                ->selectRaw('produksi_batch_id,
                    COUNT(*) as hold_count,
                    COALESCE(SUM(COALESCE(duration_seconds,0)),0) as total_seconds,
                    COALESCE(MAX(hold_no),0) as max_hold_no
                ')
                ->whereIn('produksi_batch_id', $batchIds)
                ->groupBy('produksi_batch_id')
                ->get()
                ->keyBy('produksi_batch_id');
        }

        $stages = $this->stages();
        return view('holding.index', compact('rows', 'q', 'stages', 'summary'));
    }

    // SET HOLD (dipanggil dari modul manapun)
    public function hold(Request $request, ProduksiBatch $batch)
    {
        $data = $request->validate([
            'holding_stage' => ['required', 'string', 'max:50'],
            'holding_reason' => ['nullable', 'string', 'max:191'],
            'holding_note' => ['nullable', 'string'],
        ]);

        if ($batch->is_holding) {
            return back()->with('ok', 'Batch sudah dalam status HOLD.');
        }

        DB::transaction(function () use ($batch, $data) {
            $now = now();

            $maxHold = (int) HoldingLog::where('produksi_batch_id', $batch->id)->max('hold_no');
            $nextHoldNo = $maxHold + 1;

            // update batch
            $batch->update([
                'is_holding' => true,
                'holding_stage' => $data['holding_stage'],
                'holding_reason' => $data['holding_reason'] ?? null,
                'holding_note' => $data['holding_note'] ?? null,
                'holding_prev_status' => $batch->status_proses,
                'holding_at' => $now,
                'holding_by' => auth()->id(),
            ]);

            // create log OPEN
            HoldingLog::create([
                'produksi_batch_id' => $batch->id,
                'hold_no' => $nextHoldNo,
                'holding_stage' => $data['holding_stage'],
                'holding_reason' => $data['holding_reason'] ?? null,
                'holding_note' => $data['holding_note'] ?? null,
                'held_at' => $now,
                'held_by' => auth()->id(),
                'outcome' => null,
                'return_to' => null,
                'resolve_reason' => null,
                'resolve_note' => null,
                'resolved_at' => null,
                'resolved_by' => null,
                'duration_seconds' => 0,
            ]);
        });

        return back()->with('ok', 'Batch berhasil di-HOLD (log tersimpan).');
    }

    // RELEASE HOLD
    public function release(Request $request, ProduksiBatch $batch)
    {
        $data = $request->validate([
            'holding_return_to' => ['required', 'string', 'max:50'],
            'resolve_reason' => ['nullable', 'string', 'max:191'],
            'resolve_note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($batch, $data) {
            $now = now();

            $log = HoldingLog::where('produksi_batch_id', $batch->id)
                ->whereNull('resolved_at')
                ->orderByDesc('held_at')
                ->lockForUpdate()
                ->first();

            // fallback (kalau log open gak ada)
            if (!$log) {
                $maxHold = (int) HoldingLog::where('produksi_batch_id', $batch->id)->max('hold_no');
                $log = HoldingLog::create([
                    'produksi_batch_id' => $batch->id,
                    'hold_no' => $maxHold + 1,
                    'holding_stage' => (string) ($batch->holding_stage ?? 'UNKNOWN'),
                    'holding_reason' => $batch->holding_reason,
                    'holding_note' => $batch->holding_note,
                    'held_at' => $batch->holding_at ?? $now,
                    'held_by' => $batch->holding_by,
                    'duration_seconds' => 0,
                ]);
            }

            $startTs = $log->held_at ? $log->held_at->timestamp : $now->timestamp;
            $dur = max(0, $now->timestamp - $startTs);

            $log->update([
                'outcome' => 'RELEASE',
                'return_to' => $data['holding_return_to'],
                'resolve_reason' => $data['resolve_reason'] ?? null,
                'resolve_note' => $data['resolve_note'] ?? null,
                'resolved_at' => $now,
                'resolved_by' => auth()->id(),
                'duration_seconds' => $dur,
            ]);

            $batch->update([
                'is_holding' => false,
                'holding_return_to' => $data['holding_return_to'],
                'status_proses' => $batch->holding_prev_status,
            ]);
        });

        return back()->with('ok', 'HOLD dilepas (Release) + log tersimpan.');
    }

    // REJECT HOLD (tutup hold -> batch hilang dari list)
    public function reject(Request $request, ProduksiBatch $batch)
    {
        $data = $request->validate([
            'resolve_reason' => ['required', 'string', 'max:191'],
            'resolve_note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($batch, $data) {
            $now = now();

            $log = HoldingLog::where('produksi_batch_id', $batch->id)
                ->whereNull('resolved_at')
                ->orderByDesc('held_at')
                ->lockForUpdate()
                ->first();

            if (!$log) {
                $maxHold = (int) HoldingLog::where('produksi_batch_id', $batch->id)->max('hold_no');
                $log = HoldingLog::create([
                    'produksi_batch_id' => $batch->id,
                    'hold_no' => $maxHold + 1,
                    'holding_stage' => (string) ($batch->holding_stage ?? 'UNKNOWN'),
                    'holding_reason' => $batch->holding_reason,
                    'holding_note' => $batch->holding_note,
                    'held_at' => $batch->holding_at ?? $now,
                    'held_by' => $batch->holding_by,
                    'duration_seconds' => 0,
                ]);
            }

            $startTs = $log->held_at ? $log->held_at->timestamp : $now->timestamp;
            $dur = max(0, $now->timestamp - $startTs);

            $log->update([
                'outcome' => 'REJECT',
                'return_to' => null,
                'resolve_reason' => $data['resolve_reason'],
                'resolve_note' => $data['resolve_note'] ?? null,
                'resolved_at' => $now,
                'resolved_by' => auth()->id(),
                'duration_seconds' => $dur,
            ]);

            $batch->update([
                'is_holding' => false,
                'holding_return_to' => null,
                'status_proses' => $batch->holding_prev_status,
            ]);
        });

        return back()->with('ok', 'HOLD ditutup (Reject) + log tersimpan.');
    }

    // HISTORY (rekap semua log)
    public function history(Request $request)
    {
        $q = (string) $request->get('q', '');
        $batchId = $request->get('batch');

        $logs = HoldingLog::query()
            ->with(['batch.produksi'])
            ->when($batchId, fn ($qb) => $qb->where('produksi_batch_id', $batchId))
            ->when($q !== '', function ($qb) use ($q) {
                $qb->whereHas('batch', function ($b) use ($q) {
                    $b->where('nama_produk', 'like', "%{$q}%")
                        ->orWhere('no_batch', 'like', "%{$q}%")
                        ->orWhere('kode_batch', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('held_at')
            ->paginate(25)
            ->withQueryString();

        $stages = $this->stages();
        return view('holding.history', compact('logs', 'q', 'batchId', 'stages'));
    }
}