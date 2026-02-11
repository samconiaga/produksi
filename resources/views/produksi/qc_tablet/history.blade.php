@extends('layouts.app')

@section('content')
@php
  $bulanAktif = $bulan ?? request('bulan', 'all');
  $namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
  ];
@endphp

<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
          <div>
            <h4 class="card-title mb-0">Riwayat Produk Antara Tablet</h4>
            <small class="text-muted">
              Menampilkan batch yang sudah <strong>Release Tablet</strong> (read-only).
            </small>
          </div>

          <a href="{{ route('qc-tablet.index') }}" class="btn btn-sm btn-outline-secondary">
            &laquo; Kembali ke Data Aktif
          </a>
        </div>

        {{-- FLASH --}}
        @if(session('success'))
          <div class="alert alert-success m-2 py-1 mb-0">{{ session('success') }}</div>
        @endif
        @if($errors->any())
          <div class="alert alert-danger m-2 py-1 mb-0">{{ $errors->first() }}</div>
        @endif

        {{-- FILTER --}}
        <div class="card-body border-bottom">
          <form method="GET" action="{{ route('qc-tablet.history') }}" class="row g-1 g-md-2 align-items-center">
            <div class="col-12 col-md-4">
              <input type="text" name="q" class="form-control form-control-sm"
                     placeholder="Cari produk / no batch / kode batch..." value="{{ $search ?? '' }}">
            </div>

            <div class="col-6 col-md-3">
              <select name="bulan" class="form-select form-select-sm">
                <option value="all" {{ $bulanAktif === 'all' || $bulanAktif === '' ? 'selected' : '' }}>
                  Semua Bulan
                </option>
                @foreach($namaBulan as $num => $label)
                  <option value="{{ $num }}" {{ (int)$bulanAktif === $num ? 'selected' : '' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-6 col-md-2">
              <input type="number" name="tahun" class="form-control form-control-sm"
                     placeholder="Tahun" value="{{ $tahun ?? '' }}">
            </div>

            <div class="col-12 col-md-3">
              <button class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
          </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr class="text-nowrap">
                <th style="width:40px;">#</th>
                <th>Nama Produk</th>
                <th>No WO</th>
                <th>Kode Batch</th>
                <th>Tableting</th>
                <th class="text-center">Tanggal Release</th>
                <th class="text-center">Released At</th>
              </tr>
            </thead>

            <tbody>
            @forelse($batches as $idx => $batch)
              <tr>
                <td>{{ $batches->firstItem() + $idx }}</td>
                <td>{{ $batch->produksi->nama_produk ?? $batch->nama_produk }}</td>
                <td>{{ $batch->no_batch }}</td>
                <td>{{ $batch->kode_batch }}</td>
                <td>{{ $batch->tgl_tableting ? $batch->tgl_tableting->format('d-m-Y') : '-' }}</td>
                <td class="text-center">{{ $batch->tgl_rilis_tablet ? $batch->tgl_rilis_tablet->format('d-m-Y') : '-' }}</td>
                <td class="text-center">{{ $batch->tablet_signed_at ? $batch->tablet_signed_at->format('d-m-Y H:i') : '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted">Belum ada riwayat Produk Antara Tablet.</td>
              </tr>
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