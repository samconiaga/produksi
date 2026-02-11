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
    $gr = $row->gudangRelease;
    if(!$gr) return '';
    if(isset($gr->total_text) && $gr->total_text) return (string)$gr->total_text;
    if(isset($gr->total) && $gr->total) return (string)$gr->total;
    return '';
  }

  $user = auth()->user();
  $userRole = strtoupper((string) data_get($user, 'produksi_role', ''));
  $isSPV = in_array($userRole, ['SPV','ADMIN'], true);
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
</style>

<div class="row">
  <div class="col-12">
    <div class="card cardx">
      <div class="headx">
        <div class="d-flex flex-column flex-lg-row gap-2 justify-content-between">
          <div>
            <h4 class="titlex">LPHP – Pilih Data untuk Print</h4>
            <div class="subx">
              Menampilkan data <b>APPROVED</b> (siap diprint) / <b>REJECTED</b> (GOJ Reject untuk revisi).
              <b>REJECTED</b> bisa diedit (Isi/Total) & Re-Approve dulu sebelum diprint ulang.
            </div>
          </div>
          <div class="d-flex gap-50 align-items-center">
            <a href="{{ route('gudang-release.index') }}" class="btn btn-sm btn-outline-secondary">Kembali Gudang</a>
            <a href="{{ route('gudang-release.history') }}" class="btn btn-sm btn-outline-secondary">Riwayat</a>
          </div>
        </div>

        <form class="filter-wrap mt-1" method="get" action="{{ route('gudang-release.lphp') }}">
          <div style="min-width:260px;flex:1">
            <div class="fl">Cari Produk / Batch</div>
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="nama / no batch / kode batch" value="{{ $q ?? '' }}">
          </div>

          <div style="min-width:150px">
            <div class="fl">Status</div>
            <select name="status" class="form-select form-select-sm">
              <option value="ALL" {{ ($status ?? 'ALL')==='ALL'?'selected':'' }}>ALL</option>
              <option value="APPROVED" {{ ($status ?? '')==='APPROVED'?'selected':'' }}>APPROVED</option>
              <option value="REJECTED" {{ ($status ?? '')==='REJECTED'?'selected':'' }}>REJECTED</option>
            </select>
          </div>

          <div style="min-width:160px">
            <div class="fl">Bulan</div>
            <select name="bulan" class="form-select form-select-sm">
              <option value="">Semua Bulan</option>
              @for($b=1;$b<=12;$b++)
                <option value="{{ $b }}" {{ (string)($bulan ?? '') === (string)$b ? 'selected' : '' }}>
                  {{ Carbon::create()->month($b)->locale('id')->translatedFormat('F') }}
                </option>
              @endfor
            </select>
          </div>

          <div style="width:120px">
            <div class="fl">Tahun</div>
            <input type="number" name="tahun" class="form-control form-control-sm"
                   placeholder="YYYY" value="{{ $tahun ?? '' }}">
          </div>

          <div class="d-flex align-items-end">
            <button class="btn btn-sm btn-primary" type="submit">Filter</button>
          </div>
        </form>
      </div>

      <div class="bodyx">
        @if(session('success'))
          <div class="alert alert-success mb-1">{{ session('success') }}</div>
        @endif

        {{-- FORM PRINT (kosong agar tidak nesting forms) --}}
        <form method="post" action="{{ route('gudang-release.lphp.print') }}" target="_blank" id="formPrint">
          @csrf
        </form>

        <div class="d-flex justify-content-between align-items-center mb-1">
          <div class="d-flex gap-50">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAll">Pilih Semua (Approved)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnNone">Batal Semua</button>
          </div>

          <button type="submit" class="btn btn-sm btn-success" form="formPrint">
            Print yang Dipilih
          </button>
        </div>

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
              @forelse($rows as $i => $row)
                @php
                  $gr = $row->gudangRelease;

                  $statusRow = $gr?->status ?? '-';
                  $gojStatus = $gr?->goj_status ?? null;

                  $isRejected = ($gojStatus === 'REJECTED');
                  $isApproved = (
                    $statusRow === 'APPROVED'
                    && ($gojStatus === null || in_array($gojStatus, ['APPROVED','PENDING'], true))
                  );

                  $formId = 'reapprove_'.$row->id;

                  $isiVal   = $gr?->isi ?? '';
                  $jmlVal   = $gr?->jumlah_release ?? '';
                  $totalVal = totalText($row);

                  $hasGojDoc = !empty($gr?->goj_doc_id);
                  $isGojPending = $hasGojDoc && $gr->goj_status === 'PENDING';
                @endphp

                {{-- Re-Approve form (for REJECTED items) --}}
                @if($isRejected)
                  <form id="{{ $formId }}" method="post" action="{{ route('gudang-release.approve', $row->id) }}">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                  </form>
                @endif

                <tr>
                  <td>
                    @if($isApproved)
                      <input type="checkbox" name="ids[]" value="{{ $row->id }}" class="chkRow" form="formPrint">
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>

                  <td>{{ ($rows->firstItem() ?? 0) + $i }}</td>
                  <td class="fw-semibold">{{ $row->produksi->nama_produk ?? $row->nama_produk }}</td>
                  <td>{{ $row->no_batch }}</td>
                  <td>{{ $row->kode_batch }}</td>
                  <td>{{ relDate($row) }}</td>
                  <td>{{ expDate($row) }}</td>
                  <td>{{ kemasanAuto($row) }}</td>

                  {{-- ISI --}}
                  <td class="td-input-wide">
                    @if($isRejected)
                      <input type="text" name="isi" class="form-control form-control-sm"
                             value="{{ $isiVal }}" required form="{{ $formId }}"
                             placeholder="contoh: 10 Box/100 + 53 dus">
                    @else
                      {{ $gr?->isi ?? '-' }}
                    @endif
                  </td>

                  {{-- JUMLAH --}}
                  <td class="td-input">
                    @if($isRejected)
                      <input type="number" name="jumlah_release" class="form-control form-control-sm"
                             value="{{ is_numeric($jmlVal) ? $jmlVal : '' }}" min="0" required form="{{ $formId }}">
                    @else
                      {{ $gr?->jumlah_release ?? '-' }}
                    @endif
                  </td>

                  {{-- TOTAL --}}
                  <td class="td-input">
                    @if($isRejected)
                      <input type="text" name="total" class="form-control form-control-sm"
                             value="{{ $totalVal }}" required form="{{ $formId }}"
                             placeholder="contoh: 1086 dus / 90 box / 6060 botol">
                    @else
                      {{ $totalVal ?: '-' }}
                    @endif
                  </td>

                  {{-- STATUS --}}
                  <td>
                    @if($isApproved)
                      <span class="badge bg-success badge-mini">Approved</span>
                      @if($gojStatus === 'PENDING')
                        <div class="text-muted" style="font-size:.72rem;line-height:1.2;margin-top:.15rem">
                          GOJ: PENDING
                        </div>
                      @endif
                    @elseif($isRejected)
                      <span class="badge bg-danger badge-mini">GOJ Rejected</span>
                      @if(!empty($gr?->goj_note))
                        <div class="text-muted" style="font-size:.72rem;line-height:1.2;margin-top:.15rem">
                          GOJ: {{ $gr->goj_note }}
                        </div>
                      @elseif(!empty($gr?->catatan))
                        <div class="text-muted" style="font-size:.72rem;line-height:1.2;margin-top:.15rem">
                          {{ $gr->catatan }}
                        </div>
                      @endif
                    @else
                      <span class="badge bg-secondary badge-mini">{{ $statusRow }}</span>
                    @endif
                  </td>

                  {{-- AKSI --}}
                  <td class="text-center td-actions d-flex justify-content-center gap-1">
                    {{-- Re-Approve (for REJECTED) --}}
                    @if($isRejected)
                      <button type="submit" class="btn btn-sm btn-success" form="{{ $formId }}"
                        onclick="return confirm('Re-Approve data ini setelah revisi?')">
                        Re-Approve
                      </button>
                    @else
                      <span class="text-muted">-</span>
                    @endif

                    {{-- GOJ Approve / Reject (SPV only) --}}
                    @if($isSPV && $hasGojDoc)
                      @if($isGojPending)
                        {{-- Approve GOJ --}}
                        <form method="post" action="{{ route('gudang-release.gojApprove', $row->id) }}" class="d-inline">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve GOJ untuk batch {{ $row->kode_batch }}?')">
                            GOJ Approve
                          </button>
                        </form>

                        {{-- Open GOJ Reject modal --}}
                        <button type="button" class="btn btn-sm btn-outline-danger btn-open-goj-reject"
                                data-id="{{ $row->id }}" data-batch="{{ $row->kode_batch }}">
                          GOJ Reject
                        </button>
                      @else
                        <span class="text-muted" style="font-size:.8rem">GOJ: {{ $gr->goj_status ?? 'N/A' }}</span>
                      @endif
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="13" class="text-center text-muted py-2">Tidak ada data.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-1">
          {{ $rows->withQueryString()->links() }}
        </div>
      </div>
    </div>
  </div>
</div>

{{-- MODAL GOJ REJECT --}}
<div class="modal fade" id="modalGojReject" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="post" id="formGojReject">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Reject GOJ (kembalikan ke Operator)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-1">
          <label class="form-label">Catatan (wajib)</label>
          <textarea name="catatan" class="form-control" rows="3" required
                    placeholder="contoh: qty fisik tidak cocok, kemasan rusak, dsb"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-danger" type="submit">Kirim GOJ Reject</button>
      </div>
    </form>
  </div>
</div>

<script>
  // helper select all / none
  const allBtn = document.getElementById('btnAll');
  const noneBtn = document.getElementById('btnNone');

  function setAll(val){
    document.querySelectorAll('.chkRow').forEach(chk => chk.checked = val);
  }

  allBtn?.addEventListener('click', () => setAll(true));
  noneBtn?.addEventListener('click', () => setAll(false));

  // prevent print if nothing selected
  document.querySelector('button[form="formPrint"]')?.addEventListener('click', function(e){
    const any = Array.from(document.querySelectorAll('.chkRow')).some(c => c.checked);
    if(!any){
      e.preventDefault();
      alert('Pilih minimal 1 data APPROVED untuk diprint.');
    }
  });

  // open GOJ reject modal & set action to /gudang-release/{id}/goj-reject
  document.querySelectorAll('.btn-open-goj-reject').forEach(btn => {
    btn.addEventListener('click', function(){
      const id = this.getAttribute('data-id');

      const form = document.getElementById('formGojReject');
      form.action = "{{ url('gudang-release') }}/" + id + "/goj-reject";

      const ta = form.querySelector('textarea[name="catatan"]');
      if (ta) ta.value = '';

      if (window.bootstrap && window.bootstrap.Modal) {
        new bootstrap.Modal(document.getElementById('modalGojReject')).show();
      } else if (window.jQuery && typeof window.jQuery('#modalGojReject').modal === 'function') {
        window.jQuery('#modalGojReject').modal('show');
      } else {
        alert('Modal tidak tersedia. Pastikan bootstrap aktif.');
      }
    });
  });
</script>
@endsection
