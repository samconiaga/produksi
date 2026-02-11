@extends('layouts.app')

@section('content')
@php
  use Carbon\Carbon;

  /**
   * Helper kecil untuk fallback property chain
   */
  function pick($obj, $keys, $default = null) {
    foreach ($keys as $k) {
      $v = data_get($obj, $k);
      if ($v !== null && $v !== '') return $v;
    }
    return $default;
  }

  function resolveDate($raw) {
    if (!$raw) return null;
    try { return Carbon::parse($raw); } catch (\Throwable $e) { return null; }
  }

  function expiredResolve($row) {
    // cocokkan dengan helper di index
    $raw = pick($row, [
      'tanggal_expired','tgl_expired','tgl_expired_produk','expired_at','exp_date','expired_date',
      'granul_tanggal_expired','ruahan_tanggal_expired',
      'gudangRelease.tanggal_expired',
    ], null);

    if ($raw) {
      try { return Carbon::parse($raw); } catch (\Throwable $e) {}
    }

    // fallback: kalau ada tgl release + expired_years
    $rel = pick($row, ['tgl_review','tgl_release','tgl_qa_terima_coa','tgl_rilis_granul','released_at','updated_at'], null);
    $relObj = $rel ? resolveDate($rel) : null;
    if (!$relObj) return null;

    $years = pick($row, ['produksi.expired_years','expired_years'], 0);
    if (!is_numeric($years) || (int)$years <= 0) return null;
    return $relObj->copy()->addYears((int)$years);
  }

  // apakah dipanggil dengan $doc (GOJ doc) atau $release (produksi batch)
  $isDocView = isset($doc);
  $isReleaseView = isset($release) && !$isDocView;

  // items collection to render rows
  if ($isDocView) {
    $docNo = $doc->doc_no ?? 'GOJ-UNKNOWN';
    $docDate = resolveDate($doc->doc_date ?? $doc->created_at ?? null);
    // expect $doc->items relation (GojDocItem)
    $items = $items ?? ($doc->items ?? collect());
    $headerStatus = strtoupper((string)($doc->status ?? 'PENDING'));
  } else {
    // release view: use single batch (or if $items provided, use it)
    $docNo = 'PRODUKSI-BATCH';
    $docDate = resolveDate($release->wo_date ?? $release->updated_at ?? $release->created_at ?? null);
    if (!isset($items)) {
      // render single row from $release: wrap into collection to reuse table rendering
      $items = collect([ $release ]);
    }
    $headerStatus = strtoupper((string)(data_get($release, 'gudangRelease.status') ?? 'PENDING'));
  }

  $user = auth()->user();
  $userRole = strtoupper((string) data_get($user, 'produksi_role', ''));
  $isOperator = $userRole === 'OPERATOR';
@endphp

<style>
  /* ringan styling untuk meniru tampilan GOJ detail */
  .goj-card{ border-radius:16px; box-shadow:0 18px 40px rgba(2,6,23,.06); border:0; overflow:visible }
  .goj-head{ padding:18px 22px; display:flex; justify-content:space-between; align-items:center; gap:12px }
  .goj-title{ font-weight:800; font-size:1.1rem; margin:0 }
  .goj-sub{ color:#6b7280; margin-top:4px; font-size:.95rem }
  .goj-actions{ display:flex; gap:.6rem; align-items:center }
  .goj-badge{ border-radius:999px; padding:.35rem .7rem; font-weight:700; font-size:.82rem }
  .table-goj thead th{ background:#f6f7fb; border-bottom:1px solid #e9edf1; color:#6b7280; font-size:.83rem; text-transform:uppercase; }
  .table-goj tbody td{ vertical-align:middle; padding:.8rem .75rem; font-size:.95rem; border-bottom:1px solid #f1f5f9; }
  .goj-card-body{ padding:18px 22px 22px }
  .goj-footer-actions{ padding:14px 22px 20px; display:flex; gap:.5rem; align-items:center }
  .btn-ghost{ background:transparent; border:1px solid #d1d5db; color:#374151; }
  .muted-small{ color:#6b7280; font-size:.9rem }
</style>

<div class="row">
  <div class="col-12">
    <div class="card goj-card">
      <div class="goj-head">
        <div>
          <div class="d-flex align-items-start gap-3">
            <div>
              <h4 class="goj-title">
                @if($isDocView)
                  GOJ Detail — {{ $docNo }}
                @else
                  Gudang Release Detail — {{ $release->kode_batch ?? ($release->no_batch ?? '') }}
                @endif
              </h4>
              <div class="goj-sub">Tanggal: {{ $docDate ? $docDate->format('d-m-Y') : '-' }}</div>
            </div>
          </div>
        </div>

        <div class="goj-actions">
          @if($isDocView)
            {{-- Preview/Print (GOJ module) --}}
            <a href="#" class="btn btn-sm btn-ghost" onclick="alert('Preview/Print tersedia pada modul GOJ.'); return false;">Preview/Print</a>
            <span class="goj-badge {{ $headerStatus === 'APPROVED' ? 'bg-success text-white' : ($headerStatus === 'REJECTED' ? 'bg-danger text-white' : 'bg-warning text-dark') }}">
              {{ $headerStatus }}
            </span>
          @else
            {{-- release view: show Kembali + Preview/Print placeholder + status --}}
            <a href="{{ url()->previous() ?: route('gudang-release.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
            <a href="#" class="btn btn-sm btn-ghost" onclick="alert('Preview/Print ada di modul GOJ'); return false;">Preview/Print</a>
            <span class="goj-badge {{ $headerStatus === 'APPROVED' ? 'bg-success text-white' : ($headerStatus === 'REJECTED' ? 'bg-danger text-white' : 'bg-warning text-dark') }}">
              {{ $headerStatus }}
            </span>
          @endif
        </div>
      </div>

      <div class="goj-card-body">
        <div class="table-responsive">
          <table class="table table-sm table-goj">
            <thead>
              <tr>
                <th style="width:40px">#</th>
                <th>Produk</th>
                <th>BATCH NO</th>
                <th>KODE BATCH</th>
                <th>EXP</th>
                <th>KEMASAN</th>
                <th>ISI</th>
                <th>JUMLAH</th>
                <th>STATUS PRODUKSI</th>
              </tr>
            </thead>

            <tbody>
              @foreach($items as $k => $it)
                @php
                  // Normalisasi field names antara GojDocItem dan ProduksiBatch
                  $nama = pick($it, ['nama_produk', 'produksi.nama_produk', 'produksi.nama', 'nama'], '-');
                  $batchNo = pick($it, ['batch_no', 'no_batch'], '-');
                  $kode = pick($it, ['kode_batch'], pick($it, ['kode_batch','kode'],'-'));
                  $expObj = expiredResolve($it);
                  $expStr = $expObj ? $expObj->format('d-m-Y') : '-';

                  $kemasan = pick($it, ['kemasan','gudangRelease.kemasan','produksi.wadah','produksi.kemasan'], '-');
                  $isi = pick($it, ['isi','gudangRelease.isi','produksi.isi'], '-');
                  $jumlah = pick($it, ['jumlah','jumlah_release','qty_fisik','jumlah_release'], '-');
                  $statusProd = strtoupper((string) pick($it, ['status_gudang','status','status_produksi','status_gudang_release'], 'PENDING'));
                @endphp

                <tr>
                  <td>{{ $k + 1 }}</td>
                  <td class="fw-semibold">{{ $nama }}</td>
                  <td>{{ $batchNo }}</td>
                  <td>{{ $kode }}</td>
                  <td>{{ $expStr }}</td>
                  <td>{{ $kemasan }}</td>
                  <td>{{ $isi }}</td>
                  <td class="fw-semibold">{{ $jumlah }}</td>
                  <td>{{ $statusProd }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Jika ini view single release: tampilkan tombol Approve / Reject (SPV) di bawah --}}
        @if($isReleaseView && !$isOperator)
          <div class="mt-3 d-flex gap-2">
            <form id="approveReleaseForm" method="post" action="{{ route('gudang-release.approveProduksi', $release->id) }}">
              @csrf
              {{-- jika ingin, kirimkan override fields --}}
              <input type="hidden" name="isi" value="{{ pick($release, ['gudangRelease.isi','isi','produksi.isi'], '') }}">
              <input type="hidden" name="total" value="{{ pick($release, ['gudangRelease.total_text','gudangRelease.total'], '') }}">
              <input type="hidden" name="jumlah_release" value="{{ pick($release, ['gudangRelease.jumlah_release','gudangRelease.qty_fisik'], '') }}">
              <button type="button" id="btnApprove" class="btn btn-success">Approve</button>
            </form>

            <button type="button" id="btnOpenReject" class="btn btn-danger" data-id="{{ $release->id }}">Reject</button>
          </div>
        @endif
      </div>

      <div class="goj-footer-actions">
        @if($isDocView)
          <div class="muted-small">Dokumen GOJ berisi {{ $items->count() }} item</div>
        @else
          <div class="muted-small">Detail verifikasi gudang untuk batch.</div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Modal Reject (reused) --}}
<div class="modal fade" id="modalRejectDoc" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" id="formRejectDoc" method="post">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Reject</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Catatan (wajib)</label>
          <textarea name="catatan" class="form-control" rows="3" required placeholder="Alasan reject..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-danger" type="submit">Kirim Reject</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Hook approve button (release view)
  document.addEventListener('DOMContentLoaded', function(){
    const btnApprove = document.getElementById('btnApprove');
    if (btnApprove) {
      btnApprove.addEventListener('click', function(){
        if (!confirm('Approve produksi dan tandai sebagai APPROVED?')) return;
        document.getElementById('approveReleaseForm').submit();
      });
    }

    const btnOpenReject = document.getElementById('btnOpenReject');
    if (btnOpenReject) {
      btnOpenReject.addEventListener('click', function(){
        const id = this.getAttribute('data-id');
        const form = document.getElementById('formRejectDoc');

        // set action to reject-produksi route for this release
        form.action = "{{ url('gudang-release') }}/" + id + "/reject-produksi";

        // show modal
        if (window.bootstrap && window.bootstrap.Modal) {
          new bootstrap.Modal(document.getElementById('modalRejectDoc')).show();
        } else {
          alert('Modal Bootstrap tidak ditemukan.');
        }
      });
    }

    // jika modal reject disubmit, biarkan form melakukan post ke route yang sudah di-set
  });
</script>

@endsection
