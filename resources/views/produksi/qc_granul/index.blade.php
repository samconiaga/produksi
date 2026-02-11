{{-- qc_granul/index.blade --}}
@extends('layouts.app')

@section('content')
@php
  $bulanAktif = $bulan ?? request('bulan', 'all');
  $perPageAktif = request('per_page', $perPage ?? 20);

  $namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
  ];

  $hasHoldRoute = \Illuminate\Support\Facades\Route::has('qc-granul.hold');
@endphp

<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- HEADER --}}
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
          <div>
            <h4 class="card-title mb-0">QC Produk Antara Granul (Data Aktif)</h4>
            <small class="text-muted">
              Revisi: <strong>langsung Release</strong> (tanpa Tgl Datang / Start / Stop Analisa, tanpa TTD/QR).
              Cukup isi <strong>Tanggal Release</strong> lalu klik <strong>Release</strong>.
              Aksi lain: <strong>Hold</strong> → masuk modul Holding.
            </small>
          </div>

          <a href="{{ route('qc-granul.history') }}" class="btn btn-sm btn-outline-secondary">
            Riwayat Produk Antara Granul
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
          <form method="GET" action="{{ route('qc-granul.index') }}" class="row g-1 g-md-2 align-items-center">

            <div class="col-12 col-md-4">
              <input type="text"
                     name="q"
                     class="form-control form-control-sm"
                     placeholder="Cari produk / no batch / kode batch..."
                     value="{{ $search ?? '' }}">
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
              <input type="number"
                     name="tahun"
                     class="form-control form-control-sm"
                     placeholder="Tahun"
                     value="{{ $tahun ?? '' }}">
            </div>

            <div class="col-6 col-md-2">
              <select name="per_page" class="form-select form-select-sm">
                @foreach([20, 50, 100] as $opt)
                  <option value="{{ $opt }}" {{ (int)$perPageAktif === $opt ? 'selected' : '' }}>
                    {{ $opt }} / halaman
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-6 col-md-1 text-end">
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
                <th>Mixing</th>
                <th class="text-center" style="width:180px;">Tanggal Release</th>
                <th class="text-center" style="width:160px;">Aksi</th>
              </tr>
            </thead>

            <tbody>
            @forelse($batches as $idx => $batch)
              @php
                $holdUrl = $hasHoldRoute
                  ? route('qc-granul.hold', $batch)
                  : url('/qc-granul/'.$batch->id.'/hold');
              @endphp

              <tr>
                <td>{{ $batches->firstItem() + $idx }}</td>
                <td>{{ $batch->produksi->nama_produk ?? $batch->nama_produk }}</td>
                <td>{{ $batch->no_batch }}</td>
                <td>{{ $batch->kode_batch }}</td>
                <td>{{ $batch->tgl_mixing ? $batch->tgl_mixing->format('d-m-Y') : '-' }}</td>

                {{-- TANGGAL RELEASE + ACTION --}}
                <td class="text-center">
                  <form action="{{ route('qc-granul.release', $batch) }}" method="POST" class="d-flex gap-2 justify-content-center align-items-center">
                    @csrf
                    <input type="date"
                           name="tgl_rilis_granul"
                           class="form-control form-control-sm"
                           style="max-width:160px;"
                           value="{{ old('tgl_rilis_granul', now()->format('Y-m-d')) }}"
                           required>
                </td>

                <td class="text-center">
                    {{-- tombol sejajar (side-by-side) --}}
                    <div class="d-flex gap-2 justify-content-center align-items-center" style="min-width:160px;">
                      <button type="submit"
                              class="btn btn-sm btn-success"
                              style="min-width:90px;"
                              onclick="return confirm('Release Granul untuk batch ini?');">
                        Release
                      </button>

                      <a href="{{ $holdUrl }}"
                         class="btn btn-sm btn-outline-danger"
                         style="min-width:90px;"
                         onclick="return confirm('Pindahkan batch ini ke Holding (QC Granul)?');"
                         @if(!$hasHoldRoute)
                           title="(Fallback URL) Route qc-granul.hold tidak kebaca, pakai url manual."
                         @endif>
                        Hold
                      </a>
                    </div>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted">
                  Belum ada batch untuk QC Produk Antara Granul.
                </td>
              </tr>
            @endforelse
            </tbody>

          </table>
        </div>

        {{-- PAGINATION --}}
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
