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

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
          <div>
            <h4 class="card-title mb-0">Riwayat QC Produk Ruahan Akhir</h4>
            <small class="text-muted">Batch yang sudah <strong>Release Ruahan Akhir</strong> (read-only).</small>
          </div>

          <a href="{{ route('qc-ruahan-akhir.index') }}" class="btn btn-sm btn-outline-secondary">
            &laquo; Kembali ke Data Aktif
          </a>
        </div>

        @if(session('success'))
          <div class="alert alert-success m-2 py-1 mb-0">{{ session('success') }}</div>
        @endif
        @if($errors->any())
          <div class="alert alert-danger m-2 py-1 mb-0">{{ $errors->first() }}</div>
        @endif

        <div class="card-body border-bottom">
          <form method="GET" action="{{ route('qc-ruahan-akhir.history') }}" class="row g-1 align-items-center">
            <div class="col-12 col-md-4">
              <input type="text" name="q" class="form-control form-control-sm"
                     placeholder="Cari produk / no batch / kode batch..." value="{{ $search ?? '' }}">
            </div>

            <div class="col-6 col-md-3">
              <select name="bulan" class="form-select form-select-sm">
                <option value="all" {{ $bulanAktif === 'all' || $bulanAktif === '' ? 'selected' : '' }}>Semua Bulan</option>
                @foreach($namaBulan as $num => $label)
                  <option value="{{ $num }}" {{ (int)$bulanAktif === $num ? 'selected' : '' }}>{{ $label }}</option>
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
                <th style="width:40px;">#</th>
                <th>Nama Produk</th>
                <th>No WO</th>
                <th>Kode Batch</th>
                <th>Primary Pack</th>
                <th class="text-center">Tanggal Release</th>
                <th class="text-center">Released At</th>
                <th class="text-center">Released By</th>
              </tr>
            </thead>

            <tbody>
            @forelse($batches as $idx => $batch)
              <tr>
                <td>{{ $batches->firstItem() + $idx }}</td>
                <td>{{ $batch->produksi->nama_produk ?? $batch->nama_produk }}</td>
                <td>{{ $batch->no_batch }}</td>
                <td>{{ $batch->kode_batch }}</td>
                <td>{{ $batch->tgl_primary_pack ? $batch->tgl_primary_pack->format('d-m-Y H:i') : '-' }}</td>

                <td class="text-center">{{ $batch->tgl_rilis_ruahan_akhir ? $batch->tgl_rilis_ruahan_akhir->format('d-m-Y') : '-' }}</td>
                <td class="text-center">{{ $batch->ruahan_akhir_signed_at ? $batch->ruahan_akhir_signed_at->format('d-m-Y H:i') : '-' }}</td>
                <td class="text-center">{{ $batch->ruahan_akhir_signed_by ?? '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted">Belum ada riwayat QC Ruahan Akhir.</td>
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