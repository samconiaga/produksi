@extends('layouts.app')

@section('content')
@php
  use Carbon\Carbon;

  function dmy($v){
    if(empty($v)) return '-';
    try{
      if($v instanceof \Carbon\CarbonInterface) return $v->format('d-m-Y');
      return Carbon::parse($v)->format('d-m-Y');
    }catch(\Throwable $e){
      return (string)$v;
    }
  }

  function totalTextRow($gv){
    if(!$gv) return '-';
    if(isset($gv->total_text) && $gv->total_text) return $gv->total_text;
    if(isset($gv->total) && $gv->total) return $gv->total;
    if(!empty($gv->catatan) && str_contains($gv->catatan, 'TOTAL:')) return $gv->catatan;
    if($gv->jumlah_release !== null) return (string)$gv->jumlah_release;
    return '-';
  }
@endphp

<style>
  .gd-card{border-radius:16px;border:0;box-shadow:0 12px 32px rgba(15,23,42,.08)}
  .gd-head{padding:18px 20px 10px}
  .gd-body{padding:14px 20px 18px}
  .gd-title{font-size:1.05rem;font-weight:800;margin:0}
  .gd-sub{font-size:.82rem;color:#6b7280;margin:2px 0 0}
  .filter-wrap{background:#f8fafc;border:1px solid #eef2f7;border-radius:14px;padding:10px 12px;display:flex;gap:10px;flex-wrap:wrap;align-items:end}
  .fl{font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin:0 0 4px}
  .table-gd thead th{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;background:#f9fafb;border-bottom-color:#e5e7eb;white-space:nowrap}
  .table-gd tbody td{font-size:.82rem;vertical-align:middle}
  .badge-mini{font-size:.68rem;border-radius:999px;padding:.25rem .6rem}
  .btn-soft{background:#eef2ff;border:1px solid #e0e7ff;color:#3730a3}
  .btn-soft:hover{background:#e0e7ff}
</style>

<div class="row">
  <div class="col-12">
    <div class="card gd-card">
      <div class="gd-head">
        <div class="d-flex flex-column flex-lg-row gap-2 justify-content-between">
          <div>
            <h4 class="gd-title">Riwayat Verifikasi Release</h4>
            <div class="gd-sub">Data yang sudah diputuskan: <b>Approved</b> / <b>Rejected</b>.</div>
          </div>

          <div class="d-flex gap-50 align-items-center">
            <a href="{{ route('gudang-release.index', request()->query()) }}" class="btn btn-sm btn-soft">
              &laquo; Kembali
            </a>
          </div>
        </div>

        <form class="filter-wrap mt-1" method="get" action="{{ route('gudang-release.history') }}">
          <div style="min-width:240px;flex:1">
            <div class="fl">Cari Produk / Batch</div>
            <input type="text" name="q" class="form-control form-control-sm" value="{{ $q }}" placeholder="nama / no batch / kode batch">
          </div>

          <div style="min-width:160px">
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

          <div style="width:110px">
            <div class="fl">Tahun</div>
            <input type="number" name="tahun" class="form-control form-control-sm" value="{{ $tahun }}" placeholder="YYYY">
          </div>

          <div style="min-width:160px">
            <div class="fl">Status</div>
            <select name="status" class="form-select form-select-sm">
              <option value="ALL" {{ $status==='ALL'?'selected':'' }}>Semua</option>
              <option value="APPROVED" {{ $status==='APPROVED'?'selected':'' }}>Approved</option>
              <option value="REJECTED" {{ $status==='REJECTED'?'selected':'' }}>Rejected</option>
            </select>
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
                <th>Kemasan</th>
                <th>Isi</th>
                <th>Total</th>
                <th>Tgl Expired</th>
                <th>Status</th>
                <th>Catatan</th>
                <th>Waktu</th>
              </tr>
            </thead>
            <tbody>
              @forelse($rows as $i => $row)
                @php
                  $gv = $row->gudangRelease;
                  $st = $gv->status ?? '-';
                  $badge = $st === 'APPROVED' ? 'bg-success' : ($st === 'REJECTED' ? 'bg-danger' : 'bg-secondary');
                @endphp
                <tr>
                  <td>{{ $rows->firstItem() + $i }}</td>
                  <td class="fw-semibold">{{ $row->produksi->nama_produk ?? $row->nama_produk }}</td>
                  <td>{{ $row->no_batch }}</td>
                  <td>{{ $row->kode_batch }}</td>
                  <td>{{ $gv->kemasan ?? '-' }}</td>
                  <td>{{ $gv->isi ?? '-' }}</td>
                  <td class="fw-semibold">{{ totalTextRow($gv) }}</td>
                  <td>{{ dmy($gv->tanggal_expired ?? null) }}</td>
                  <td><span class="badge {{ $badge }} badge-mini">{{ $st }}</span></td>
                  <td style="max-width:260px;">
                    <span class="text-muted">{{ $gv->catatan ?: '-' }}</span>
                  </td>
                  <td class="text-muted">{{ dmy($gv->approved_at ?? null) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="11" class="text-center text-muted py-2">Belum ada riwayat.</td>
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
@endsection