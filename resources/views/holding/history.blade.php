@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row">
    <div class="col-12">
      <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-0">Rekap Holding Log</h4>
            <p class="mb-0 text-muted">Riwayat HOLD/Release/Reject untuk semua batch (atau per batch).</p>
          </div>
          <div class="d-flex gap-1">
            <a href="{{ route('holding.index') }}" class="btn btn-outline-primary btn-sm">Kembali</a>
          </div>
        </div>

        @if(session('ok'))
          <div class="alert alert-success m-2">{{ session('ok') }}</div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger m-2">
            <ul class="mb-0">
              @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="card-body border-bottom">
          <form method="GET" class="row g-1">
            <div class="col-md-4">
              <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Cari produk / batch / kode...">
            </div>
            <div class="col-md-3">
              <input type="number" name="batch" value="{{ $batchId }}" class="form-control" placeholder="Filter batch ID (opsional)">
            </div>
            <div class="col-md-2">
              <button class="btn btn-outline-primary w-100">Filter</button>
            </div>
          </form>
        </div>

        <div class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Produk</th>
                <th>No WO</th>
                <th>Kode Batch</th>
                <th>Hold Ke</th>
                <th>Stage</th>
                <th>Alasan</th>
                <th>Held At</th>
                <th>Outcome</th>
                <th>Return To</th>
                <th>Resolved At</th>
                <th>Durasi</th>
              </tr>
            </thead>

            <tbody>
              @forelse($logs as $i => $log)
                @php
                  $b = $log->batch;
                  $produk = $b->produksi->nama_produk ?? $b->nama_produk ?? '-';
                  $dur = (int)($log->duration_seconds ?? 0);
                @endphp

                <tr>
                  <td>{{ $logs->firstItem() + $i }}</td>
                  <td>{{ $produk }}</td>
                  <td>{{ $b->no_batch ?? '-' }}</td>
                  <td>{{ $b->kode_batch ?? '-' }}</td>

                  <td><span class="badge bg-light-primary">#{{ $log->hold_no }}</span></td>
                  <td>{{ $stages[$log->holding_stage] ?? ($log->holding_stage ?? '-') }}</td>
                  <td>{{ $log->holding_reason ?? '-' }}</td>
                  <td>{{ $log->held_at ? $log->held_at->format('d-m-Y H:i') : '-' }}</td>

                  <td>
                    @if($log->outcome === 'RELEASE')
                      <span class="badge bg-light-success">RELEASE</span>
                    @elseif($log->outcome === 'REJECT')
                      <span class="badge bg-light-danger">REJECT</span>
                    @else
                      <span class="badge bg-light-warning">OPEN</span>
                    @endif
                  </td>

                  <td>{{ $log->return_to ? ($stages[$log->return_to] ?? $log->return_to) : '-' }}</td>
                  <td>{{ $log->resolved_at ? $log->resolved_at->format('d-m-Y H:i') : '-' }}</td>

                  <td><span class="badge bg-light-info">{{ gmdate('H:i:s', $dur) }}</span></td>
                </tr>
              @empty
                <tr>
                  <td colspan="12" class="text-center text-muted">Belum ada log holding.</td>
                </tr>
              @endforelse
            </tbody>

          </table>
        </div>

        <div class="card-body">
          <div class="d-flex justify-content-center">
            {{ $logs->withQueryString()->links('pagination::bootstrap-4') }}
          </div>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
