@extends('layouts.app')

@section('content')
@php
  use Carbon\Carbon;

  function pick($obj, $keys, $default=null){
    foreach($keys as $k){
      $v = data_get($obj, $k);
      if($v !== null && $v !== '') return $v;
    }
    return $default;
  }

  function relDate($row){
    $raw = pick($row, ['tgl_review','tgl_release','tgl_qa_terima_coa','tgl_rilis_granul','released_at','updated_at'], null);
    if(!$raw) return '-';
    try { return Carbon::parse($raw)->format('d-m-Y'); } catch(\Throwable $e){ return (string)$raw; }
  }

  function expDate($row){
    $raw = pick($row, [
      'tanggal_expired','tgl_expired','tgl_expired_produk','expired_at','exp_date','expired_date',
      'granul_tanggal_expired','ruahan_tanggal_expired'
    ], null);

    if(!$raw && $row->gudangRelease && !empty($row->gudangRelease->tanggal_expired)){
      $raw = $row->gudangRelease->tanggal_expired;
    }

    if(!$raw) return '-';
    try { return Carbon::parse($raw)->format('d-m-Y'); } catch(\Throwable $e){ return (string)$raw; }
  }

  function kemasanAuto($row){
    return pick($row, ['produksi.wadah','produksi.kemasan','wadah','kemasan','jenis_kemasan'], '-');
  }

  function totalText($row){
    $gr = $row->gudangRelease ?? null;
    if(!$gr) return '';
    if(isset($gr->total_text) && $gr->total_text) return (string)$gr->total_text;
    if(isset($gr->total) && $gr->total) return (string)$gr->total;
    return '';
  }

  $user = auth()->user();
  $userRole = strtoupper((string) data_get($user, 'produksi_role', ''));
  $isSPV = in_array($userRole, ['SPV','ADMIN'], true);

  // controller may pass:
  // - $bundles (paginated GojDoc) + $unbundledPendingCount
  // - OR $doc + $rows  (viewing a bundle detail)
  // - OR $rows (selected ids)
@endphp

<style>
  .cardx{border-radius:16px;border:0;box-shadow:0 12px 32px rgba(15,23,42,.08)}
  .headx{padding:18px 20px 10px}
  .bodyx{padding:14px 20px 18px}
  .titlex{font-size:1.05rem;font-weight:800;margin:0}
  .subx{font-size:.82rem;color:#6b7280;margin:2px 0 0}
  .filter-wrap{background:#f8fafc;border:1px solid #eef2f7;border-radius:14px;padding:10px 12px;display:flex;gap:10px;flex-wrap:wrap;align-items:end}
  .fl{font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin:0 0 4px}
  .tablex thead th{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;background:#f9fafb;border-bottom-color:#e5e7eb;white-space:nowrap}
  .tablex tbody td{font-size:.82rem;vertical-align:middle}
  .badge-mini{font-size:.68rem;border-radius:999px;padding:.25rem .6rem}
  .td-input{min-width:140px}
  .td-input-wide{min-width:260px}
  .td-actions{min-width:160px}
  .muted-small{font-size:.78rem;color:#6b7280}
</style>

<div class="row">
  <div class="col-12">
    <div class="card cardx">
      <div class="headx d-flex justify-content-between align-items-start">
        <div>
          <h4 class="titlex">SPV Review — Review untuk Print / GOJ</h4>
          <div class="subx">
            Halaman khusus SPV / Admin. Pilih data yang dikirim dari LPHP untuk di-<b>Approve</b> atau di-<b>Reject</b>.
            Jika di-<b>Approve</b>, bundle akan diteruskan ke GOJ (sesuai alur backend).
          </div>
        </div>

        <div class="d-flex gap-2">
          <a href="{{ route('gudang-release.lphp') }}" class="btn btn-sm btn-outline-secondary">Kembali LPHP</a>
          <a href="{{ route('gudang-release.index') }}" class="btn btn-sm btn-outline-secondary">Kembali Gudang</a>
        </div>
      </div>

      <div class="bodyx">
        @if(!$isSPV)
          <div class="alert alert-danger">Akses ditolak — halaman ini hanya untuk SPV / Admin.</div>
        @else
          @if(session('success'))
            <div class="alert alert-success mb-1">{{ session('success') }}</div>
          @endif
          @if(session('error'))
            <div class="alert alert-danger mb-1">{{ session('error') }}</div>
          @endif

          {{-- Bulk action form (approve-bulk) --}}
          <form id="formSpvBulk" method="post" action="{{ route('gudang-release.spv.approve-bulk') }}">
            @csrf
            <input type="hidden" name="ids" id="bulk_ids" value="">
          </form>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAll">Pilih Semua</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btnNone">Batal Semua</button>
            </div>

            <div class="d-flex gap-2">
              {{-- If viewing a GOJ doc ($doc exists) show bundle actions --}}
              @if(isset($doc))
                <button type="button" class="btn btn-sm btn-success" id="btnApproveBundle">Approve Bundle (GOJ)</button>
                <button type="button" class="btn btn-sm btn-danger" id="btnRejectBundle">Reject Bundle</button>
                <a href="{{ route('gudang-release.spvReview') }}" class="btn btn-sm btn-outline-secondary">Kembali ke Bundles</a>
              @else
                {{-- default bulk actions for selected ids --}}
                <button type="button" class="btn btn-sm btn-success" id="btnBulkApprove">Approve yang Dipilih</button>
                <button type="button" class="btn btn-sm btn-danger" id="btnBulkReject">Reject yang Dipilih</button>
              @endif
            </div>
          </div>

          {{-- If controller passed $bundles (list of GOJ docs) show bundle listing --}}
          @if(isset($bundles) && $bundles->count() > 0 && !isset($doc) && empty($rows))
            <div class="mb-3 d-flex justify-content-between align-items-center">
              <div>
                <strong>Bundle GOJ (Menunggu SPV)</strong>
                <div class="text-muted" style="font-size:.9rem">Klik "Buka" untuk melihat isi bundle dan melakukan Approve / Reject per-bundle.</div>
              </div>
              <div class="text-end">
                @if(isset($unbundledPendingCount) && $unbundledPendingCount > 0)
                  <span class="badge bg-warning text-dark">Ada {{ $unbundledPendingCount }} item pending belum dibundel</span>
                @endif
              </div>
            </div>

            <div class="table-responsive mb-2">
              <table class="table table-sm table-hover tablex">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Doc No</th>
                    <th>Tgl Doc</th>
                    <th>Jumlah Item</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($bundles as $idx => $bundle)
                    @php
                      $items = $bundle->items()->get(); // load items (will query if not loaded)
                      $itemIds = $items->pluck('produksi_batch_id')->filter()->values()->all();
                    @endphp
                    <tr>
                      <td>{{ ($bundles->firstItem() ?? 0) + $idx }}</td>
                      <td class="fw-semibold">{{ $bundle->doc_no }}</td>
                      <td>{{ optional($bundle->doc_date)->format ? Carbon::parse($bundle->doc_date)->format('d-m-Y') : ($bundle->doc_date ?? '-') }}</td>
                      <td>{{ $bundle->items_count ?? count($itemIds) }}</td>
                      <td>
                        <span class="badge badge-mini {{ $bundle->status === 'PENDING' ? 'bg-secondary' : ($bundle->status === 'APPROVED' ? 'bg-success' : 'bg-danger') }}">
                          {{ $bundle->status }}
                        </span>
                      </td>
                      <td class="text-end">
                        <a href="{{ route('gudang-release.spvReview') }}?goj_doc_id={{ $bundle->id }}" class="btn btn-sm btn-outline-primary">Buka</a>

                        {{-- Approve bundle form (sends ids JSON) --}}
                        <form method="post" action="{{ route('gudang-release.spv.approve-bulk') }}" class="d-inline-block ms-1" id="approve_bundle_{{ $bundle->id }}">
                          @csrf
                          <input type="hidden" name="ids" value='@json($itemIds)'>
                          <button type="button" class="btn btn-sm btn-success btn-approve-bundle" data-form="approve_bundle_{{ $bundle->id }}">
                            Approve
                          </button>
                        </form>

                        {{-- Reject bundle: open modal to enter catatan --}}
                        <button type="button" class="btn btn-sm btn-outline-danger ms-1 btn-reject-bundle"
                                data-ids='@json($itemIds)'>Reject</button>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="mt-2">
              {{ $bundles->withQueryString()->links() }}
            </div>

          @else
            {{-- ELSE: show per-item listing (either $doc detail or $rows) --}}
            <div class="table-responsive">
              <table class="table table-sm table-hover tablex">
                <thead>
                  <tr>
                    <th style="width:40px"></th>
                    <th>#</th>
                    <th>Produk</th>
                    <th>No Batch</th>
                    <th>Kode Batch</th>
                    <th>Tgl Release</th>
                    <th>Exp Date</th>
                    <th>Kemasan</th>
                    <th class="td-input-wide">Isi</th>
                    <th class="td-input">Jumlah</th>
                    <th class="td-input">Total</th>
                    <th>Status</th>
                    <th class="td-actions text-center">Aksi</th>
                  </tr>
                </thead>

                <tbody>
                  @forelse(($rows ?? collect()) as $i => $row)
                    @php
                      $gr = $row->gudangRelease;
                      $isApproved = ($gr && $gr->status === 'APPROVED');
                      $isRejected = ($gr && ($gr->goj_status === 'REJECTED' || $gr->status === 'REJECTED'));
                      $totalVal = totalText($row);
                    @endphp

                    <tr>
                      <td>
                        <input type="checkbox" class="spv-chk" value="{{ $row->id }}">
                      </td>
                      <td>{{ (($rows->firstItem() ?? 0) + $i) }}</td>
                      <td class="fw-semibold">{{ $row->produksi->nama_produk ?? $row->nama_produk }}</td>
                      <td>{{ $row->no_batch }}</td>
                      <td>{{ $row->kode_batch }}</td>
                      <td>{{ relDate($row) }}</td>
                      <td>{{ expDate($row) }}</td>
                      <td>{{ kemasanAuto($row) }}</td>
                      <td class="td-input-wide">{{ $gr?->isi ?? '-' }}</td>
                      <td class="td-input">{{ $gr?->jumlah_release ?? '-' }}</td>
                      <td class="td-input">{{ $totalVal ?: '-' }}</td>

                      <td>
                        @if($isApproved)
                          <span class="badge bg-success badge-mini">Approved</span>
                        @elseif($isRejected)
                          <span class="badge bg-danger badge-mini">Rejected</span>
                        @else
                          <span class="badge bg-secondary badge-mini">{{ $gr?->status ?? '-' }}</span>
                        @endif
                      </td>

                      <td class="text-center td-actions d-flex justify-content-center gap-1">
                        @if(isset($doc))
                          <span class="muted-small">Bundle action only</span>
                        @else
                          <form method="post" action="{{ route('gudang-release.spv.approve', $row->id) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success"
                                    onclick="return confirm('Approve batch {{ $row->kode_batch }} dan kirim ke GOJ?')">
                              Approve
                            </button>
                          </form>

                          <button type="button" class="btn btn-sm btn-outline-danger btn-open-spv-reject"
                                  data-id="{{ $row->id }}" data-batch="{{ $row->kode_batch }}">
                            Reject
                          </button>
                        @endif
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="13" class="text-center text-muted py-2">Tidak ada data untuk direview.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div class="mt-2">
              @if(isset($rows))
                {{ $rows->withQueryString()->links() }}
              @endif
            </div>
          @endif

          {{-- MODAL SPV REJECT --}}
          <div class="modal fade" id="modalSpvReject" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <form class="modal-content" method="post" id="formSpvReject">
                @csrf
                <div class="modal-header">
                  <h5 class="modal-title">Reject oleh SPV (kembalikan ke Operator)</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="ids[]" id="spv_reject_single_id" value="">
                  <div class="mb-1">
                    <label class="form-label">Catatan (wajib)</label>
                    <textarea name="catatan" class="form-control" rows="4" required
                              placeholder="contoh: qty fisik tidak cocok, kemasan rusak, dsb"></textarea>
                  </div>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                  <button class="btn btn-danger" type="submit">Kirim Reject</button>
                </div>
              </form>
            </div>
          </div>

        @endif
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function(){
    const btnAll = document.getElementById('btnAll');
    const btnNone = document.getElementById('btnNone');

    function setAll(val){
      document.querySelectorAll('.spv-chk').forEach(c => c.checked = val);
    }
    btnAll?.addEventListener('click', () => setAll(true));
    btnNone?.addEventListener('click', () => setAll(false));

    // bulk approve (for selected ids)
    const btnBulkApprove = document.getElementById('btnBulkApprove');
    btnBulkApprove?.addEventListener('click', function(){
      const ids = Array.from(document.querySelectorAll('.spv-chk:checked')).map(c => c.value);
      if(ids.length === 0){ alert('Pilih minimal 1 data untuk di-approve.'); return; }
      if(!confirm('Approve ' + ids.length + ' data? Setelah approve akan diteruskan ke GOJ.')) return;

      document.getElementById('bulk_ids').value = JSON.stringify(ids);
      document.getElementById('formSpvBulk').submit();
    });

    // bulk reject -> open modal and set hidden ids
    const btnBulkReject = document.getElementById('btnBulkReject');
    btnBulkReject?.addEventListener('click', function(){
      const ids = Array.from(document.querySelectorAll('.spv-chk:checked')).map(c => c.value);
      if(ids.length === 0){ alert('Pilih minimal 1 data untuk di-reject.'); return; }

      const modal = new bootstrap.Modal(document.getElementById('modalSpvReject'));
      document.getElementById('formSpvReject').action = "{{ route('gudang-release.spv.reject-bulk') }}";
      const container = document.getElementById('formSpvReject');
      container.querySelectorAll('input[name="ids[]"]').forEach(n => n.remove());
      ids.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'ids[]';
        inp.value = id;
        container.prepend(inp);
      });
      modal.show();
    });

    // single reject buttons
    document.querySelectorAll('.btn-open-spv-reject').forEach(btn => {
      btn.addEventListener('click', function(){
        const id = this.getAttribute('data-id');

        const form = document.getElementById('formSpvReject');
        form.action = "{{ url('gudang-release') }}/" + id + "/spv-reject";
        document.getElementById('spv_reject_single_id').value = id;
        form.catatan.value = '';

        new bootstrap.Modal(document.getElementById('modalSpvReject')).show();
      });
    });

    // Approve bundle buttons (they are forms with hidden JSON ids)
    document.querySelectorAll('.btn-approve-bundle').forEach(btn => {
      btn.addEventListener('click', function(){
        const formId = this.getAttribute('data-form');
        const form = document.getElementById(formId);
        if(!form) return;
        if(!confirm('Approve seluruh item pada bundle ini?')) return;
        form.submit();
      });
    });

    // Reject bundle (use modal to provide catatan)
    document.querySelectorAll('.btn-reject-bundle').forEach(btn => {
      btn.addEventListener('click', function(){
        const ids = JSON.parse(this.getAttribute('data-ids') || '[]');
        if(!ids || ids.length === 0){ alert('Tidak ada item pada bundle.'); return; }

        const modal = new bootstrap.Modal(document.getElementById('modalSpvReject'));
        document.getElementById('formSpvReject').action = "{{ route('gudang-release.spv.reject-bulk') }}";
        const container = document.getElementById('formSpvReject');
        container.querySelectorAll('input[name="ids[]"]').forEach(n => n.remove());
        ids.forEach(id => {
          const inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = 'ids[]';
          inp.value = id;
          container.prepend(inp);
        });
        modal.show();
      });
    });

  });
</script>
@endsection
