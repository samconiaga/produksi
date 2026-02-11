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

  function releaseDateObj($row){
    $raw = pick($row, ['tgl_review','tgl_release','tgl_qa_terima_coa','tgl_rilis_granul','released_at','updated_at'], null);
    return $raw ? Carbon::parse($raw) : null;
  }

  function expiredFromBatch($row){
    $raw = pick($row, [
      'tanggal_expired','tgl_expired','tgl_expired_produk','expired_at','exp_date','expired_date',
      'granul_tanggal_expired','ruahan_tanggal_expired'
    ], null);

    if(!$raw) return null;
    try { return Carbon::parse($raw); } catch(\Throwable $e){ return null; }
  }

  function expiredAutoFromMasterYears($row){
    $rel = releaseDateObj($row);
    if(!$rel) return null;

    $years = pick($row, ['produksi.expired_years','expired_years'], 0);
    if(!is_numeric($years)) return null;

    $y = (int)$years;
    if($y <= 0) return null;

    return $rel->copy()->addYears($y);
  }

  function expiredResolveForView($row){
    $fromRelease = expiredFromBatch($row);
    if($fromRelease) return $fromRelease;

    if($row->gudangRelease && !empty($row->gudangRelease->tanggal_expired)){
      try { return Carbon::parse($row->gudangRelease->tanggal_expired); }
      catch(\Throwable $e){}
    }

    return expiredAutoFromMasterYears($row);
  }

  function totalText($row){
    $gv = $row->gudangRelease;
    if(!$gv) return '';
    if(isset($gv->total_text) && $gv->total_text) return $gv->total_text;
    if(isset($gv->total) && $gv->total) return $gv->total;
    // fallback: kalau total disimpan di catatan "TOTAL: xxx"
    if(!empty($gv->catatan) && str_contains($gv->catatan, 'TOTAL:')) return $gv->catatan;
    // terakhir: tampilkan angka kalau ada
    return ($gv->jumlah_release !== null) ? (string)$gv->jumlah_release : '';
  }
@endphp

<style>
  .gd-card{
    border-radius:16px;
    border:0;
    box-shadow:0 12px 32px rgba(15,23,42,.08)
  }

  .gd-head{padding:20px 22px 12px}
  .gd-body{padding:18px 22px 22px}

  .gd-title{
    font-size:1.15rem;
    font-weight:800;
    margin:0
  }

  .gd-sub{
    font-size:.9rem;
    color:#6b7280;
    margin:4px 0 0
  }

  .filter-wrap{
    background:#f8fafc;
    border:1px solid #eef2f7;
    border-radius:14px;
    padding:12px 14px;
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:end
  }

  .fl{
    font-size:.75rem;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#9ca3af;
    margin:0 0 6px
  }

  /* ================= TABEL LEBIH GEDE & LEGA ================= */

  .table-gd thead th{
    font-size:.82rem;
    text-transform:uppercase;
    letter-spacing:.06em;
    color:#4b5563;
    background:#f9fafb;
    border-bottom:1px solid #e5e7eb;
    white-space:nowrap;
    padding:.9rem .75rem;
  }

  .table-gd tbody td{
    font-size:.95rem;
    vertical-align:middle;
    padding:.85rem .75rem;
  }

  .table-gd tbody tr{
    border-bottom:1px solid #f1f5f9;
  }

  .table-gd tbody tr:hover{
    background:#f8fafc;
  }

  .badge-mini{
    font-size:.75rem;
    border-radius:999px;
    padding:.35rem .7rem
  }

  .btn-soft{
    background:#eef2ff;
    border:1px solid #e0e7ff;
    color:#3730a3
  }

  .btn-soft:hover{background:#e0e7ff}

  .td-input{min-width:180px}
  .td-input-wide{min-width:280px}
  .td-actions{min-width:210px}

  .gd-plain{
    min-height:36px;
    display:flex;
    align-items:center;
    font-weight:600;
    white-space:nowrap
  }

  /* ================= INPUT BIAR LEBIH JELAS ================= */

  .table-gd .form-control-sm{
    font-size:.95rem;
    padding:.5rem .65rem;
    height:36px;
  }

  /* ================= TOMBOL AKSI LEBIH PROPORSIONAL ================= */

  .table-gd .btn-sm{
    padding:.45rem .75rem;
    font-size:.85rem;
  }

  /* Responsive biar tetap enak di layar kecil */
  @media (max-width: 768px){
    .table-gd thead th,
    .table-gd tbody td{
      font-size:.85rem;
      padding:.7rem .6rem;
    }

    .td-input{min-width:150px}
    .td-input-wide{min-width:220px}
    .td-actions{min-width:180px}
  }
</style>


<div class="row">
  <div class="col-12">
    <div class="card gd-card">
      <div class="gd-head">
        <div class="d-flex flex-column flex-lg-row gap-2 justify-content-between">
          <div>
            <h4 class="gd-title">Secondary – Verifikasi Fisik Produk Released</h4>
            <div class="gd-sub">
              Batch yang sudah <b>Released QA</b> dan menunggu verifikasi Gudang (<b>Pending</b>).
              Isi & Total mengikuti format LPHP manual (boleh angka/huruf/+).
            </div>
          </div>

          <div class="d-flex gap-50 align-items-center">
            <a href="{{ route('gudang-release.lphp', request()->query()) }}" class="btn btn-sm btn-soft">LPHP</a>
            <a href="{{ route('gudang-release.history', request()->query()) }}" class="btn btn-sm btn-soft">Riwayat</a>
          </div>
        </div>

        <form class="filter-wrap mt-1" method="get" action="{{ route('gudang-release.index') }}">
          <div style="min-width:260px;flex:1">
            <div class="fl">Cari Produk / Batch</div>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light border-end-0">
                <i data-feather="search" style="width:14px;height:14px;"></i>
              </span>
              <input type="text" name="q" class="form-control" placeholder="nama / no batch / kode batch" value="{{ $q }}">
            </div>
          </div>

          <div style="min-width:170px">
            <div class="fl">Bulan Release</div>
            <select name="bulan" class="form-select form-select-sm">
              <option value="">Semua Bulan</option>
              @for($b=1;$b<=12;$b++)
                <option value="{{ $b }}" {{ (string)$bulan === (string)$b ? 'selected' : '' }}>
                  {{ Carbon::create()->month($b)->locale('id')->translatedFormat('F') }}
                </option>
              @endfor
            </select>
          </div>

          <div style="width:120px">
            <div class="fl">Tahun</div>
            <input type="number" name="tahun" class="form-control form-control-sm" placeholder="YYYY" value="{{ $tahun }}">
          </div>

          <div class="d-flex align-items-end">
            <button class="btn btn-sm btn-primary" type="submit">Filter</button>
          </div>
        </form>
      </div>

      <div class="gd-body">
        @if(session('success'))
          <div class="alert alert-success mb-1">{{ session('success') }}</div>
        @endif

        <div class="table-responsive">
          <table class="table table-sm table-hover table-gd">
            <thead>
              <tr>
                <th>#</th>
                <th>Produk</th>
                <th>No Batch</th>
                <th>Kode Batch</th>
                <th>Tgl Release</th>
                <th>Tgl Expired</th>

                <th class="td-input">Kemasan</th>
                <th class="td-input-wide">Isi</th>
                <th class="td-input">Total</th>

                <th>Status</th>
                <th class="text-center td-actions">Aksi</th>
              </tr>
            </thead>

            <tbody>
              @forelse($rows as $i => $row)
                @php
                  $gv = $row->gudangRelease;

                  $tglRelObj = releaseDateObj($row);
                  $tglRelStr = $tglRelObj ? $tglRelObj->format('d-m-Y') : '-';

                  $expObj = expiredResolveForView($row);
                  $expStr = $expObj ? $expObj->format('d-m-Y') : '-';

                  $kemasanAuto = pick($row, ['produksi.wadah','produksi.kemasan','wadah','kemasan','jenis_kemasan'], '');
                  $kemasan = $kemasanAuto ?: ($gv?->kemasan ?? '-');

                  $isiFallback = pick($row, ['isi','isi_kemasan','produksi.isi','produksi.isi_kemasan'], '');
                  $isi   = $gv?->isi ?? $isiFallback;

                  $total = totalText($row);

                  $formId = 'approveForm_'.$row->id;
                @endphp

                <tr>
                  <td>{{ $rows->firstItem() + $i }}</td>
                  <td class="fw-semibold">{{ $row->produksi->nama_produk ?? $row->nama_produk }}</td>
                  <td>{{ $row->no_batch }}</td>
                  <td>{{ $row->kode_batch }}</td>
                  <td>{{ $tglRelStr }}</td>
                  <td class="fw-semibold">{{ $expStr }}</td>

                  <td class="td-input">
                    <div class="gd-plain">{{ $kemasan }}</div>
                  </td>

                  <td class="td-input-wide">
                    <input type="text" name="isi" class="form-control form-control-sm"
                           value="{{ $isi }}" required form="{{ $formId }}"
                           placeholder="contoh: 10 Box/100 + 53 dus">
                  </td>

                  <td class="td-input">
                    <input type="text" name="total" class="form-control form-control-sm"
                           value="{{ $total }}" required form="{{ $formId }}"
                           placeholder="contoh: 1086 dus">
                  </td>

                  <td>
                    <span class="badge bg-warning text-dark badge-mini">Pending</span>
                  </td>

                  <td class="text-center td-actions">
                    <form id="{{ $formId }}" method="post" action="{{ route('gudang-release.approve', $row->id) }}" class="d-inline">
                      @csrf
                      <div class="d-flex flex-column flex-sm-row justify-content-center gap-25">
                        <button type="submit" class="btn btn-sm btn-success"
                                onclick="return confirm('Approve verifikasi gudang untuk batch {{ $row->kode_batch }}?')">
                          Approve
                        </button>

                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-open-reject"
                                data-id="{{ $row->id }}"
                                data-batch="{{ $row->kode_batch }}">
                          Reject
                        </button>
                      </div>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="11" class="text-center text-muted py-2">
                    Tidak ada data <b>Pending</b> untuk diverifikasi gudang.
                  </td>
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

{{-- MODAL REJECT --}}
<div class="modal fade" id="modalReject" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="post" id="formReject">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Reject Verifikasi Gudang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-1">
          <label class="form-label">Catatan (wajib)</label>
          <textarea name="catatan" class="form-control" rows="3" required
                    placeholder="contoh: qty fisik tidak cocok, kemasan tidak sesuai, dll"></textarea>
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
  document.querySelectorAll('.btn-open-reject').forEach(btn => {
    btn.addEventListener('click', function(){
      const id = this.getAttribute('data-id');

      const form = document.getElementById('formReject');
      form.action = "{{ url('gudang-release') }}/" + id + "/reject";
      form.catatan.value = '';

      if (window.bootstrap && window.bootstrap.Modal) {
        new bootstrap.Modal(document.getElementById('modalReject')).show();
      } else if (window.jQuery && typeof window.jQuery('#modalReject').modal === 'function') {
        window.jQuery('#modalReject').modal('show');
      } else {
        alert('Modal tidak tersedia. Pastikan bootstrap aktif.');
      }
    });
  });
</script>
@endsection
