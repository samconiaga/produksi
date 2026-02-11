@extends('layouts.app')

@section('content')
@php
  $bulanAktif = $bulan ?? request('bulan', 'all');

  $namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
  ];

  $perPage = (int) request('per_page', $perPage ?? 20);
  if (!in_array($perPage, [10,20,50,100], true)) $perPage = 20;
@endphp

<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header">
          <h4 class="card-title mb-0">Tracking Batch</h4>
          <small class="text-muted">
            Posisi batch sekarang, sudah sejak kapan, lama tertahan, dan status hold.
          </small>
        </div>

        <div class="card-body border-bottom">
          <form method="GET" action="{{ route('tracking-batch.index') }}" class="row g-1 align-items-center">
            <div class="col-12 col-md-4">
              <input type="text" name="q" class="form-control form-control-sm"
                     placeholder="Cari produk / no WO / kode batch..." value="{{ $search ?? '' }}">
            </div>

            <div class="col-6 col-md-3">
              <select name="bulan" class="form-select form-select-sm">
                <option value="all">Semua Bulan</option>
                @foreach($namaBulan as $num=>$label)
                  <option value="{{ $num }}" {{ (int)$bulanAktif===$num ? 'selected':'' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-6 col-md-2">
              <input type="number" name="tahun" class="form-control form-control-sm"
                     placeholder="Tahun" value="{{ $tahun ?? '' }}">
            </div>

            <div class="col-6 col-md-2">
              <select name="per_page" class="form-select form-select-sm">
                @foreach([10,20,50,100] as $n)
                  <option value="{{ $n }}" {{ $perPage===$n ? 'selected':'' }}>{{ $n }} / halaman</option>
                @endforeach
              </select>
            </div>

            <div class="col-6 col-md-1">
              <button class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
          </form>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr class="text-nowrap">
                <th>#</th>
                <th>Produk</th>
                <th>No WO</th>
                <th>Kode Batch</th>
                <th>Status</th>
                <th class="text-center">Sejak</th>
                <th class="text-center">Durasi</th>
                <th class="text-center">Last Update</th>
                <th>Alasan Hold</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($batches as $idx => $batch)
                @php $t = $batch->track ?? null; @endphp
                <tr>
                  <td>{{ $batches->firstItem() + $idx }}</td>
                  <td>{{ $batch->produksi->nama_produk ?? '-' }}</td>
                  <td>{{ $batch->no_batch ?? '-' }}</td>
                  <td>{{ $batch->kode_batch ?? '-' }}</td>

                  <td>
                    @if($t && $t['is_holding'])
                      <span class="badge bg-danger">HOLD</span>
                      <div class="small text-muted">Durasi Hold: <strong>{{ $t['hold_age_text'] }}</strong></div>
                    @else
                      <span class="badge bg-info">{{ $t['current'] ?? '-' }}</span>
                    @endif
                  </td>

                  <td class="text-center">{{ $t['since_text'] ?? '-' }}</td>
                  <td class="text-center"><strong>{{ $t['age_text'] ?? '-' }}</strong></td>
                  <td class="text-center">{{ $t['last_text'] ?? '-' }}</td>
                  <td class="text-muted">{{ $t['hold_reason'] ?? '-' }}</td>

                  <td class="text-center">
                    <a href="{{ route('tracking-batch.show', $batch) }}" class="btn btn-sm btn-outline-secondary py-0">
                      Detail
                    </a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="10" class="text-center text-muted">Belum ada data batch.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="card-body">
          <div class="d-flex justify-content-center">
            {{ $batches->withQueryString()->links('pagination::bootstrap-4') }}
          </div>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection