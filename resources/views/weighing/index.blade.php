@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- HEADER --}}
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title">Weighing (WO)</h4>
            <p class="mb-0 text-muted">
              Daftar Work Order untuk proses Weighing. Data otomatis dari Upload WO.
            </p>
          </div>
        </div>

        {{-- FILTER --}}
        <div class="card-body border-bottom">
          <form class="row g-1" method="GET">

            {{-- Search --}}
            <div class="col-md-4">
              <input type="text"
                     name="q"
                     value="{{ $q }}"
                     class="form-control"
                     placeholder="Cari WO / produk / batch...">
            </div>

            {{-- Bulan --}}
            <div class="col-md-3">
              <select name="bulan" class="form-control">
                <option value="">Semua Bulan</option>
                @for($i = 1; $i <= 12; $i++)
                  <option value="{{ $i }}" {{ $bulan == $i ? 'selected' : '' }}>
                    {{ sprintf('%02d', $i) }}
                  </option>
                @endfor
              </select>
            </div>

            {{-- Tahun --}}
            <div class="col-md-2">
              <input type="number"
                     name="tahun"
                     value="{{ $tahun }}"
                     class="form-control"
                     placeholder="Tahun">
            </div>

            {{-- Filter button --}}
            <div class="col-md-2">
              <button class="btn btn-outline-primary w-100">
                Filter
              </button>
            </div>

          </form>
        </div>

        {{-- TABEL --}}
        <div class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>No WO</th>
                <th>Nama Produk</th>
                <th>Nomor Batch</th>
                <th>WO Date</th>
                <th>Expected Date</th>
                <th>Weighing</th>
              </tr>
            </thead>

            <tbody>
              @forelse($rows as $idx => $row)
                <tr>
                  <td>{{ $rows->firstItem() + $idx }}</td>

                  {{-- No WO --}}
                  <td>{{ $row->no_batch }}</td>

                  {{-- Nama Produk --}}
                  <td>{{ $row->produksi->nama_produk ?? $row->nama_produk }}</td>

                  {{-- Nomor Batch (kode) --}}
                  <td>{{ $row->kode_batch }}</td>

                  <td>{{ optional($row->wo_date)->format('d-m-Y') }}</td>
                  <td>{{ optional($row->expected_date)->format('d-m-Y') }}</td>

                  {{-- Tanggal Weighing --}}
                  <td>{{ optional($row->tgl_weighing)->format('d-m-Y') ?: '-' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted">
                    Belum ada data Weighing.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- PAGINATION DI TENGAH --}}
        <div class="card-body">
          <div class="d-flex justify-content-center">
            {{ $rows->withQueryString()->links('pagination::bootstrap-4') }}
          </div>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
