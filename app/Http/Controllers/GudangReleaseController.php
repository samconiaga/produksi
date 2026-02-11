<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use App\Models\GudangRelease;
use App\Models\MasterGudang;

// GOJ / SPV models
use App\Models\GojDoc;
use App\Models\GojDocItem;
use App\Models\SpvDoc;
use App\Models\SpvDocItem;

use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GudangReleaseController extends Controller
{
    /* =========================================================
     * Helpers
     * ========================================================= */
    private function gudangOptions()
    {
        return MasterGudang::query()
            ->where('is_active', 1)
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama']);
    }

    private function pickVal($obj, array $keys, $default = null)
    {
        foreach ($keys as $k) {
            $v = data_get($obj, $k);
            if ($v !== null && $v !== '') return $v;
        }
        return $default;
    }

    private function defaultGudangId(): ?int
    {
        $g = MasterGudang::query()
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->where('kode', 'like', '%GDJ%')
                  ->orWhere('kode', 'like', '%FG%')
                  ->orWhere('nama', 'like', '%Finish%')
                  ->orWhere('nama', 'like', '%Good%');
            })
            ->orderBy('id')
            ->first();

        if ($g) return (int) $g->id;

        $g2 = MasterGudang::query()->where('is_active', 1)->orderBy('id')->first();
        return $g2 ? (int) $g2->id : null;
    }

    private function extractIntFromTotal(?string $total): ?int
    {
        if (!$total) return null;
        if (preg_match('/(\d+)/', $total, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    private function baseQuery(Request $request)
    {
        $q     = trim((string) $request->get('q', ''));
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        return ProduksiBatch::with([
                'produksi',
                'gudangRelease',
                'gudangRelease.gudang',
            ])
            ->whereIn('status_review', ['released', 'RELEASED'])
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($s) use ($q) {
                    $s->where('nama_produk', 'like', "%{$q}%")
                      ->orWhere('kode_batch', 'like', "%{$q}%")
                      ->orWhere('no_batch', 'like', "%{$q}%");
                });
            })
            ->when($bulan !== null && $bulan !== '', fn ($qb) => $qb->where('bulan', (int) $bulan))
            ->when($tahun !== null && $tahun !== '', fn ($qb) => $qb->where('tahun', (int) $tahun));
    }

    /* =========================================================
     * Index / History
     * ========================================================= */
    public function index(Request $request)
    {
        $q     = $request->get('q', '');
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $query = $this->baseQuery($request)
            ->where(function ($q) {
                $q->doesntHave('gudangRelease')
                  ->orWhereHas('gudangRelease', fn ($s) => $s->where('status', 'PENDING'));
            });

        $rows = $query->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $gudangs = $this->gudangOptions();

        return view('gudang_release.index', compact('rows', 'q', 'bulan', 'tahun', 'gudangs'));
    }

    public function history(Request $request)
    {
        $q      = $request->get('q', '');
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');
        $status = $request->get('status', 'ALL');

        $query = $this->baseQuery($request)
            ->whereHas('gudangRelease', function ($q) use ($status) {
                if ($status === 'APPROVED') $q->where('status', 'APPROVED');
                elseif ($status === 'REJECTED') $q->where('status', 'REJECTED');
                else $q->whereIn('status', ['APPROVED', 'REJECTED']);
            });

        $rows = $query->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $gudangs = $this->gudangOptions();

        return view('gudang_release.history', compact('rows', 'q', 'bulan', 'tahun', 'status', 'gudangs'));
    }

    /* =========================================================
     * LPHP
     * ========================================================= */
    public function lphp(Request $request)
    {
        $q      = $request->get('q', '');
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');
        $status = $request->get('status', 'ALL');

        $grTable = (new GudangRelease())->getTable();
        $hasSpvDocId = Schema::hasColumn($grTable, 'spv_doc_id');
        $hasGojStatus = Schema::hasColumn($grTable, 'goj_status');
        $hasStatus = Schema::hasColumn($grTable, 'status');

        $query = $this->baseQuery($request);

        // Build gudangRelease condition but only reference columns if they exist
        if ($hasStatus || $hasGojStatus) {
            $query->whereHas('gudangRelease', function ($qq) use ($status, $hasGojStatus, $hasStatus) {

                if ($status === 'REJECTED' && $hasGojStatus) {
                    $qq->where('goj_status', 'REJECTED');
                    return;
                }

                if ($status === 'APPROVED') {
                    if ($hasStatus) {
                        $qq->where('status', 'APPROVED');
                    }
                    if ($hasGojStatus) {
                        $qq->where(function ($w) {
                            $w->whereNull('goj_status')
                              ->orWhereIn('goj_status', ['APPROVED', 'PENDING']);
                        });
                    }
                    return;
                }

                // default: approved or goj rejected
                $qq->where(function ($w) use ($hasStatus, $hasGojStatus) {
                    if ($hasStatus) {
                        $w->where(function ($a) use ($hasGojStatus) {
                            $a->where('status', 'APPROVED');
                            if ($hasGojStatus) {
                                $a->where(function ($x) {
                                    $x->whereNull('goj_status')
                                      ->orWhereIn('goj_status', ['APPROVED', 'PENDING']);
                                });
                            }
                        });
                    }
                    if ($hasGojStatus) {
                        $w->orWhere('goj_status', 'REJECTED');
                    }
                });
            });
        } else {
            // If the table does not have expected columns, fallback to "has gudangRelease"
            $query->whereHas('gudangRelease');
        }

        // EXCLUDE rows that already have been grouped into an SPV document (already printed)
        if ($hasSpvDocId) {
            $query->whereDoesntHave('gudangRelease', function ($q) use ($grTable) {
                $q->whereNotNull('spv_doc_id');
            });
        }

        $rows = $query->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $gudangs = $this->gudangOptions();

        return view('gudang_release.lphp', compact('rows', 'q', 'bulan', 'tahun', 'status', 'gudangs'));
    }

    /**
     * NOTE: produce SPV document first (LPHP -> SPV) instead of direct GOJ.
     * SPV will review and then create GOJ.
     */
    public function lphpPrint(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $grTable = (new GudangRelease())->getTable();
        $hasGojStatus = Schema::hasColumn($grTable, 'goj_status');
        $hasStatus = Schema::hasColumn($grTable, 'status');

        $rowsQuery = ProduksiBatch::with(['produksi', 'gudangRelease', 'gudangRelease.gudang'])
            ->whereIn('id', $data['ids']);

        // only add the gudangRelease condition if columns exist, otherwise just ensure relation exists
        if ($hasStatus || $hasGojStatus) {
            $rowsQuery->whereHas('gudangRelease', function ($q) use ($hasStatus, $hasGojStatus) {
                if ($hasStatus) {
                    $q->where('status', 'APPROVED');
                }
                if ($hasGojStatus) {
                    $q->where(function ($w) {
                        $w->whereNull('goj_status')
                          ->orWhereIn('goj_status', ['APPROVED', 'PENDING']);
                    });
                }
            });
        } else {
            $rowsQuery->whereHas('gudangRelease');
        }

        $rows = $rowsQuery->orderBy('nama_produk')
            ->orderBy('kode_batch')
            ->get();

        abort_if($rows->isEmpty(), 404, 'Data tidak ditemukan / belum APPROVED.');

        $spvDoc = null;
        $docNo = null;

        DB::transaction(function () use ($rows, &$spvDoc, &$docNo, $grTable) {
            // create SPV document (LPHP -> SPV)
            $docNo = 'SPV-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

            $doc = SpvDoc::create([
                'doc_no'     => $docNo,
                'doc_date'   => now()->toDateString(),
                'status'     => 'PENDING',
                'created_by' => Auth::id(),
            ]);

            foreach ($rows as $r) {
                $gr = $r->gudangRelease;

                SpvDocItem::create([
                    'spv_doc_id'        => $doc->id,
                    'produksi_batch_id' => $r->id,
                    'nama_produk'   => $r->produksi->nama_produk ?? $r->nama_produk,
                    'batch_no'      => $r->no_batch,
                    'kode_batch'    => $r->kode_batch,
                    'tgl_release'   => $this->releaseDateOf($r)?->toDateString(),
                    'tgl_expired'   => $this->resolveExpiredForGudang($r, $gr)?->toDateString(),
                    'kemasan'       => $gr?->kemasan,
                    'isi'           => $gr?->isi,
                    'jumlah'        => $gr?->jumlah_release,
                    'status_gudang' => $gr?->status,
                ]);

                if ($gr) {
                    // Only set spv_doc_id when empty so we don't overwrite existing association.
                    if (Schema::hasColumn($grTable, 'spv_doc_id') && empty($gr->spv_doc_id)) {
                        $gr->spv_doc_id = $doc->id;
                    }

                    // mark as printed to filter out from LPHP list
                    if (Schema::hasColumn($grTable, 'spv_status') && empty($gr->spv_status)) {
                        $gr->spv_status = 'PRINTED';
                    }

                    if (Schema::hasColumn($grTable, 'spv_note') && empty($gr->spv_note)) {
                        $gr->spv_note = null;
                    }

                    $gr->save();
                }
            }

            $spvDoc = $doc;
        });

        //
        // --- VERY IMPORTANT: reload rows from DB so we have fresh gudangRelease values
        //
        $ids = $rows->pluck('id')->all();
        $rows = ProduksiBatch::with(['produksi', 'gudangRelease', 'gudangRelease.gudang'])
            ->whereIn('id', $ids)
            ->orderBy('nama_produk')
            ->orderBy('kode_batch')
            ->get();

        //
        // --- compute signature names/dates here (controller decides)
        //     Prefer values stored on gudang_release (single source of truth)
        //
        $opName = null; $opAt = null; $spvName = null; $spvAt = null; $spvTtdUrl = null;
        $gojName = null; $gojAt = null;
        $showGoj = false;

        // OPERATOR (DIISI OLEH): prefer gudang_release.approved_by (user yang melakukan isian/approve di LPHP)
        // fallback ke spvDoc creator (user yang membuat SPV doc / yang men-generate print), lalu ke Auth
        $grApproved = $rows->pluck('gudangRelease')
            ->filter(function ($g) {
                return $g && !empty($g->approved_by);
            });

        if ($grApproved->isNotEmpty()) {
            // pilih approval paling akhir (paling relevan untuk tanggal)
            $lastGr = $grApproved->sortByDesc(function ($g) {
                return $g->approved_at ?? null;
            })->first();

            if ($lastGr) {
                $u = User::find($lastGr->approved_by);
                if ($u) $opName = $u->name ?? $u->email;
                $opAt = $lastGr->approved_at ?? null;
            }
        } elseif ($spvDoc && !empty($spvDoc->created_by)) {
            $u = User::find($spvDoc->created_by);
            if ($u) $opName = $u->name ?? $u->email;
            $opAt = $spvDoc->created_at ?? now();
        } else {
            $op = Auth::user();
            if ($op) $opName = $op->name ?? $op->email;
            $opAt = now();
        }

        // -----------------------
        // SPV signature: STRICT rules
        // - Prefer last gudang_release row that has spv_status APPROVED + spv_approved_by set
        // - Do NOT fallback to spvDoc.approved_by here.
        // -----------------------
        $spvCandidates = $rows->pluck('gudangRelease')
            ->filter(function ($g) {
                return $g && !empty($g->spv_status)
                    && strtoupper((string)$g->spv_status) === 'APPROVED'
                    && !empty($g->spv_approved_at)
                    && !empty($g->spv_approved_by);
            });

        if ($spvCandidates->isNotEmpty()) {
            // pick most recent approval
            $lastSpv = $spvCandidates->sortByDesc(function ($g) {
                return $g->spv_approved_at ?? null;
            })->first();

            if ($lastSpv) {
                $spvUser = User::find($lastSpv->spv_approved_by);
                if ($spvUser) {
                    $spvName = $spvUser->name ?? $spvUser->email;
                    $spvAt = $lastSpv->spv_approved_at ?? null;
                    if (!empty($spvUser->ttd)) {
                        $spvTtdUrl = asset('storage/' . ltrim($spvUser->ttd, '/'));
                    }
                }
            }
        } else {
            // do NOT fallback to spvDoc.approved_by here.
            // If no gudang_release SPV approval exists, leave SPV values null so blade prints blank.
            $spvName = null;
            $spvAt = null;
            $spvTtdUrl = null;
        }

        // GOJ: only show GOJ if ALL rows are approved by GOJ and they share the same approver
        $grCandidates = $rows->pluck('gudangRelease')
            ->filter(function ($g) {
                return $g && !empty($g->goj_status) && strtoupper((string)$g->goj_status) === 'APPROVED' && !empty($g->goj_approved_at);
            });

        if ($grCandidates->isNotEmpty() && $grCandidates->count() === $rows->count()) {
            // ensure single unique approver across all rows
            $uniqueApprovers = $grCandidates->pluck('goj_approved_by')->unique()->filter(fn($x) => !empty($x));
            if ($uniqueApprovers->count() === 1) {
                $approvedBy = $uniqueApprovers->first();
                $gUser = User::find($approvedBy);
                if ($gUser) $gojName = $gUser->name ?? $gUser->email;
                // take latest approved_at for displayed date
                $gojAt = $grCandidates->map(fn($g) => $g->goj_approved_at)->filter()->sort()->last() ?? null;
                $showGoj = true;
            }
        }

        // kirim semua ke view (sertakan docNo agar header cetak menampilkan nomor dokumen)
        return view('gudang_release.lphp_print', compact(
            'rows', 'spvDoc', 'docNo', 'opName', 'opAt', 'spvName', 'spvAt', 'spvTtdUrl', 'gojName', 'gojAt', 'showGoj'
        ));
    }

    /* =========================================================
     * Expired logic
     * ========================================================= */
    private function releaseDateOf(ProduksiBatch $batch): ?Carbon
    {
        $raw = $this->pickVal($batch, [
            'tgl_review', 'tgl_release', 'tgl_qa_terima_coa', 'tgl_rilis_granul', 'released_at', 'updated_at',
        ], null);

        if (!$raw) return null;
        try { return Carbon::parse($raw); } catch (\Throwable $e) { return null; }
    }

    private function expiredFromRelease(ProduksiBatch $batch): ?Carbon
    {
        $raw = $this->pickVal($batch, [
            'tanggal_expired','tgl_expired','tgl_expired_produk','expired_at','exp_date','expired_date',
            'granul_tanggal_expired','ruahan_tanggal_expired',
        ], null);

        if (!$raw) return null;
        try { return Carbon::parse($raw); } catch (\Throwable $e) { return null; }
    }

    private function autoExpiredFromMasterYears(ProduksiBatch $batch): ?Carbon
    {
        $rel = $this->releaseDateOf($batch);
        if (!$rel) return null;

        $years = $this->pickVal($batch, ['produksi.expired_years','expired_years'], 0);
        if (!is_numeric($years)) return null;

        $y = (int) $years;
        if ($y <= 0) return null;

        return $rel->copy()->addYears($y);
    }

    private function resolveExpiredForGudang(ProduksiBatch $batch, ?GudangRelease $gv = null): ?Carbon
    {
        $fromRelease = $this->expiredFromRelease($batch);
        if ($fromRelease) return $fromRelease;

        if (!empty($gv?->tanggal_expired)) {
            try { return Carbon::parse($gv->tanggal_expired); } catch (\Throwable $e) {}
        }

        return $this->autoExpiredFromMasterYears($batch);
    }

    /* =========================================================
     * Actions: Approve / Reject (Gudang)
     * ========================================================= */
    public function approve(ProduksiBatch $release, Request $request)
    {
        $data = $request->validate([
            'isi'           => 'required|string|max:255',
            'total'         => 'required|string|max:60',
            'jumlah_release'=> 'nullable|numeric|min:0',
            'redirect_to'   => 'nullable|string',
        ]);

        $gv = GudangRelease::firstOrNew([
            'produksi_batch_id' => $release->id,
        ]);

        $kemasanAuto = $this->pickVal($release, [
            'produksi.wadah','produksi.kemasan','wadah','kemasan','jenis_kemasan',
        ], '');

        $gv->kemasan = $kemasanAuto ?: ($gv->kemasan ?? '-');
        $gv->isi     = $data['isi'];

        $angka = null;
        if (isset($data['jumlah_release']) && $data['jumlah_release'] !== null) {
            $angka = (int) $data['jumlah_release'];
        } else {
            $angka = $this->extractIntFromTotal($data['total']);
        }

        if ($angka !== null) {
            $gv->jumlah_release = $angka;
            if (Schema::hasColumn($gv->getTable(), 'qty_fisik')) {
                $gv->qty_fisik = $angka;
            }
        }

        if (Schema::hasColumn($gv->getTable(), 'total_text')) {
            $gv->total_text = $data['total'];
        } elseif (Schema::hasColumn($gv->getTable(), 'total')) {
            $gv->total = $data['total'];
        } else {
            $note = trim((string)($gv->catatan ?? ''));
            $tag  = "TOTAL: ".$data['total'];
            $gv->catatan = $note ? ($note." | ".$tag) : $tag;
        }

        if (Schema::hasColumn($gv->getTable(), 'gudang_id')) {
            if (empty($gv->gudang_id)) {
                $gv->gudang_id = $this->defaultGudangId();
            }
        }

        $expired = $this->resolveExpiredForGudang($release, $gv);
        $gv->tanggal_expired = $expired ? $expired->format('Y-m-d') : null;

        // approve
        $gv->status      = 'APPROVED';
        $gv->approved_by = Auth::id();
        $gv->approved_at = now();

        // reset GOJ flags (biar tidak lagi dianggap REJECTED)
        if (Schema::hasColumn($gv->getTable(), 'goj_doc_id')) $gv->goj_doc_id = null;
        if (Schema::hasColumn($gv->getTable(), 'goj_status')) $gv->goj_status = null;
        if (Schema::hasColumn($gv->getTable(), 'goj_note')) $gv->goj_note = null;
        if (Schema::hasColumn($gv->getTable(), 'goj_approved_by')) $gv->goj_approved_by = null;
        if (Schema::hasColumn($gv->getTable(), 'goj_approved_at')) $gv->goj_approved_at = null;
        if (Schema::hasColumn($gv->getTable(), 'goj_rejected_by')) $gv->goj_rejected_by = null;
        if (Schema::hasColumn($gv->getTable(), 'goj_rejected_at')) $gv->goj_rejected_at = null;

        // reset SPV flags if present (so it can be re-SPV'ed if needed) - DO NOT overwrite approved fields here
        if (Schema::hasColumn($gv->getTable(), 'spv_doc_id')) $gv->spv_doc_id = null;
        if (Schema::hasColumn($gv->getTable(), 'spv_status')) $gv->spv_status = null;
        if (Schema::hasColumn($gv->getTable(), 'spv_note')) $gv->spv_note = null;
        if (Schema::hasColumn($gv->getTable(), 'spv_approved_by')) $gv->spv_approved_by = null;
        if (Schema::hasColumn($gv->getTable(), 'spv_approved_at')) $gv->spv_approved_at = null;
        if (Schema::hasColumn($gv->getTable(), 'spv_rejected_by')) $gv->spv_rejected_by = null;
        if (Schema::hasColumn($gv->getTable(), 'spv_rejected_at')) $gv->spv_rejected_at = null;

        $gv->save();

        if (!empty($data['redirect_to'])) {
            return redirect()
                ->to($data['redirect_to'])
                ->with('success', 'Re-Approve berhasil. Data sekarang Approved (siap diprint).');
        }

        return redirect()
            ->route('gudang-release.index')
            ->with('success', 'Approve berhasil. Data masuk LPHP untuk diprint.');
    }

    public function reject(ProduksiBatch $release, Request $request)
    {
        $data = $request->validate([
            'catatan' => 'required|string|max:255',
        ]);

        $gv = GudangRelease::firstOrNew([
            'produksi_batch_id' => $release->id,
        ]);

        $kemasanAuto = $this->pickVal($release, [
            'produksi.wadah','produksi.kemasan','wadah','kemasan','jenis_kemasan',
        ], '');

        if (empty($gv->kemasan)) {
            $gv->kemasan = $kemasanAuto ?: '-';
        }

        $gv->status      = 'REJECTED';
        $gv->approved_by = Auth::id();
        $gv->approved_at = now();
        $gv->catatan     = $data['catatan'];
        $gv->save();

        return redirect()
            ->route('gudang-release.index')
            ->with('success', 'Reject berhasil. Data tetap pending perbaikan.');
    }

    /* =========================================================
     * SPV / GOJ actions used to populate print signatures
     * - spvApprove: SPV menekan approve pada SPV doc (set spv_doc approved + update gudang_release rows)
     * - gojApprove: GOJ menekan approve pada single produksi_batch (set goj fields on gudang_release)
     * ========================================================= */
    public function spvApprove(SpvDoc $spvDoc, Request $request)
    {
        DB::transaction(function () use ($spvDoc) {
            $spvDoc->status = 'APPROVED';
            $spvDoc->approved_by = Auth::id();
            $spvDoc->approved_at = now();
            $spvDoc->save();

            // update related gudang_release rows
            $items = SpvDocItem::where('spv_doc_id', $spvDoc->id)->get();
            foreach ($items as $it) {
                $gr = GudangRelease::where('produksi_batch_id', $it->produksi_batch_id)->first();
                if (!$gr) continue;
                if (Schema::hasColumn($gr->getTable(), 'spv_status')) $gr->spv_status = 'APPROVED';
                if (Schema::hasColumn($gr->getTable(), 'spv_approved_by')) $gr->spv_approved_by = Auth::id();
                if (Schema::hasColumn($gr->getTable(), 'spv_approved_at')) $gr->spv_approved_at = now();
                $gr->save();
            }
        });

        return redirect()->back()->with('success', 'SPV Approve berhasil.');
    }

    /**
     * GOJ Approve for a single ProduksiBatch (used in lphp view's GOJ Approve button)
     */
    public function gojApprove(ProduksiBatch $release, Request $request)
    {
        $gr = GudangRelease::firstOrNew(['produksi_batch_id' => $release->id]);

        if (Schema::hasColumn($gr->getTable(), 'goj_status')) $gr->goj_status = 'APPROVED';
        if (Schema::hasColumn($gr->getTable(), 'goj_approved_by')) $gr->goj_approved_by = Auth::id();
        if (Schema::hasColumn($gr->getTable(), 'goj_approved_at')) $gr->goj_approved_at = now();

        $gr->save();

        return redirect()->back()->with('success', 'GOJ Approve berhasil.');
    }
}