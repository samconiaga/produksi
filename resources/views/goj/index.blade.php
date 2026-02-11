@extends('layouts.app')

@section('content')
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
</style>

<div class="row">
  <div class="col-12">
    <div class="card gd-card">
      <div class="gd-head">
        <div class="d-flex flex-column flex-lg-row gap-2 justify-content-between">
          <div>
            <h4 class="gd-title">GOJ – Review (Pending)</h4>
            <div class="gd-sub">Dokumen GOJ yang menunggu diproses. Untuk yang sudah diproses lihat menu Riwayat.</div>
          </div>

          <div class="d-flex gap-50 align-items-center">
            <a href="{{ route('goj.history', ['status' => 'APPROVED']) }}" class="btn btn-sm btn-outline-primary">
              Riwayat
            </a>
          </div>
        </div>

        <form class="filter-wrap mt-1" method="get" action="{{ route('goj.index') }}">
          <div style="min-width:260px;flex:1">
            <div class="fl">Cari Dokumen</div>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="GOJ-YYYYMMDD-XXXX" value="{{ $q ?? '' }}">
          </div>

          <div style="min-width:140px">
            <div class="fl">Dari</div>
            <input type="date" name="from" class="form-control form-control-sm" value="{{ $from ?? '' }}">
          </div>

          <div style="min-width:140px">
            <div class="fl">Sampai</div>
            <input type="date" name="to" class="form-control form-control-sm" value="{{ $to ?? '' }}">
          </div>

          <div class="d-flex align-items-end gap-50">
            <button class="btn btn-sm btn-primary" type="submit">Filter</button>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('goj.index') }}">Reset</a>
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
                <th>Dokumen</th>
                <th>Tanggal</th>
                <th>Item</th>
                <th>Status</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($docs as $i => $d)
                <tr>
                  <td>{{ ($docs->firstItem() ?? 0) + $i }}</td>
                  <td class="fw-semibold">{{ $d->doc_no }}</td>
                  <td>{{ optional($d->doc_date)->format('d-m-Y') ?: '-' }}</td>
                  <td>{{ $d->items_count }}</td>
                  <td><span class="badge bg-warning text-dark badge-mini">PENDING</span></td>
                  <td class="text-center">
                    <a href="{{ route('goj.show', $d->id) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                    <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('goj.preview', $d->id) }}">
                      Preview/Print
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-2">Tidak ada dokumen pending.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-1">
          {{ $docs->withQueryString()->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection