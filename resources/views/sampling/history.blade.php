@extends('layouts.app')

@section('content')

@php
  use Carbon\Carbon;

  $fmtDate = function ($value) {
      if (empty($value)) return '-';
      try {
          return Carbon::parse($value)->format('Y-m-d');
      } catch (\Throwable $e) {
          return str_replace(' 00:00:00', '', (string) $value);
      }
  };
@endphp

<section class="app-user-list">

  <div class="row">
    <div class="col-12">

      <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-0">Riwayat Sampling</h4>
            <p class="text-muted mb-0">
              Menampilkan batch yang Sampling-nya sudah final (Confirmed / Rejected).
            </p>
          </div>

          <a href="{{ route('sampling.index') }}"
             class="btn btn-sm btn-outline-secondary">
            &laquo; Kembali ke Data Aktif
          </a>
        </div>

        @if(session('ok'))
          <div class="alert alert-success m-2">{{ session('ok') }}</div>
        @endif

        {{-- Filter (biar konsisten sama index) --}}
        <div class="card-body border-bottom">
          <form class="row g-1" method="GET" action="{{ route('sampling.history') }}">

            <div class="col-md-4">
              <input type="text" class="form-control" name="q" placeholder="Cari..."
                     value="{{ $q ?? '' }}">
            </div>

            <div class="col-md-2">
              <input type="number" class="form-control" name="bulan" placeholder="Bulan"
                     value="{{ $bulan ?? '' }}">
            </div>

            <div class="col-md-2">
              <input type="number" class="form-control" name="tahun" placeholder="Tahun"
                     value="{{ $tahun ?? '' }}">
            </div>

            <div class="col-md-2">
              <button class="btn btn-primary w-100">Filter</button>
            </div>

          </form>
        </div>

        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Kode Batch</th>
                <th>Produk</th>
                <th>Qty</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>Status</th>
                <th>Tanggal Sampling</th>
              </tr>
            </thead>

            <tbody>
              @forelse ($rows as $idx => $row)
                @php
                  $status = $row->status_sampling ?? '-';

                  switch ($status) {
                      case 'confirmed':
                          $badge = 'badge-light-success';
                          $label = 'Confirmed';
                          break;
                      case 'rejected':
                          $badge = 'badge-light-danger';
                          $label = 'Rejected';
                          break;
                      default:
                          $badge = 'badge-light-secondary';
                          $label = ucfirst($status);
                          break;
                  }
                @endphp

                <tr>
                  <td>{{ $rows->firstItem() + $idx }}</td>
                  <td>{{ $row->kode_batch }}</td>
                  <td>{{ $row->nama_produk }}</td>
                  <td>{{ $row->qty_batch }}</td>
                  <td>{{ $row->bulan }}</td>
                  <td>{{ $row->tahun }}</td>

                  <td>
                    <span class="badge {{ $badge }}">{{ $label }}</span>
                  </td>

                  {{-- FIX: tanggal tanpa jam --}}
                  <td>{{ $fmtDate($row->tgl_sampling) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted">
                    Belum ada riwayat sampling.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="card-body">
          {{ $rows->withQueryString()->links() }}
        </div>

      </div>
    </div>
  </div>

</section>
@endsection