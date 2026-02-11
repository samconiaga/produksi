@extends('layouts.app')

@section('content')
@php use Carbon\Carbon; @endphp

<style>
  .cardx{border-radius:16px;border:0;box-shadow:0 12px 32px rgba(15,23,42,.06)}
  .headx{padding:18px 20px 10px}
  .bodyx{padding:14px 20px 18px}
  .titlex{font-size:1.05rem;font-weight:700;margin:0}
  .subx{font-size:.9rem;color:#6b7280;margin:6px 0 0}
  .filter-wrap{background:#f8fafc;border:1px solid #eef2f7;border-radius:12px;padding:10px 12px;display:flex;gap:10px;flex-wrap:wrap;align-items:end}
  .fl{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin:0 0 4px}
  .tablex thead th{font-size:.82rem;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;background:#f9fafb;border-bottom-color:#e6e6ee;white-space:nowrap}
  .tablex tbody td{font-size:.9rem;vertical-align:middle}
  .badge-mini{font-size:.72rem;border-radius:999px;padding:.25rem .6rem}
  .btn-actions{min-width:120px}
</style>

<div class="row">
  <div class="col-12">
    <div class="card cardx">
      <div class="headx d-flex justify-content-between align-items-start flex-column flex-md-row gap-2">
        <div>
          <h4 class="titlex">SPV Review (Pending)</h4>
          <div class="subx">Dokumen SPV yang menunggu review SPV. Setelah SPV Approve, data akan diproses menjadi GOJ (PENDING).</div>
        </div>

        <div class="d-flex gap-2">
          <a href="{{ route('gudang-release.lphp') }}" class="btn btn-sm btn-outline-secondary">LPHP</a>
          <!-- ganti Refresh jadi History -->
          <a href="{{ route('spv.history') }}" class="btn btn-sm btn-outline-secondary">History</a>
        </div>
      </div>

      <div class="bodyx">
        @if(session('success'))
          <div class="alert alert-success mb-2">{{ session('success') }}</div>
        @endif

        <form method="get" class="filter-wrap mb-3" action="{{ route('spv.index') }}">
          <div style="min-width:280px;flex:1">
            <div class="fl">Cari Dokumen</div>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari doc no..." value="{{ $q ?? '' }}">
          </div>

          <div style="min-width:160px">
            <div class="fl">Status</div>
            <select name="status" class="form-select form-select-sm">
              <option value="PENDING" {{ ($status ?? 'PENDING')==='PENDING' ? 'selected' : '' }}>PENDING</option>
              <option value="APPROVED" {{ ($status ?? '')==='APPROVED' ? 'selected' : '' }}>APPROVED</option>
              <option value="REJECTED" {{ ($status ?? '')==='REJECTED' ? 'selected' : '' }}>REJECTED</option>
              <option value="ALL" {{ ($status ?? '')==='ALL' ? 'selected' : '' }}>ALL</option>
            </select>
          </div>

          <div>
            <button class="btn btn-sm btn-primary">Filter</button>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-hover table-sm tablex">
            <thead>
              <tr>
                <th style="width:50px">#</th>
                <th>Dokumen</th>
                <th style="width:150px">Tanggal</th>
                <th style="width:90px">Item</th>
                <th style="width:130px">Status</th>
                <th class="text-center btn-actions">Aksi</th>
              </tr>
            </thead>

            <tbody>
              @forelse($rows as $i => $row)
                <tr>
                  <td>{{ ($rows->firstItem() ?? 0) + $i }}</td>
                  <td class="fw-semibold">{{ $row->doc_no }}</td>
                  <td>{{ optional($row->doc_date)->format('d-m-Y') ?? $row->created_at->format('d-m-Y') }}</td>
                  <td class="text-center">{{ $row->items_count ?? $row->items->count() ?? '-' }}</td>
                  <td>
                    @if(strtoupper($row->status) === 'PENDING')
                      <span class="badge bg-warning text-dark badge-mini">PENDING</span>
                    @elseif(strtoupper($row->status) === 'APPROVED')
                      <span class="badge bg-success badge-mini">APPROVED</span>
                    @elseif(strtoupper($row->status) === 'REJECTED')
                      <span class="badge bg-danger badge-mini">REJECTED</span>
                    @else
                      <span class="badge bg-secondary badge-mini">{{ $row->status }}</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <a href="{{ route('spv.detail', $row->id) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-3">Tidak ada data.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-3">
          {{ $rows->withQueryString()->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
