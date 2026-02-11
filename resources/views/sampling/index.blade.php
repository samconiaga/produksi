@extends('layouts.app')

@section('content')

@php
  use Carbon\Carbon;

  // Format tanggal biar gak ada "00:00:00"
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
            <h4 class="card-title mb-0">Sampling</h4>
            <p class="text-muted mb-0">
              Accept dahulu hasil Sampling, lalu Konfirmasi untuk mengirim ke Riwayat & Review
              setelah Qty Batch dikonfirmasi.
            </p>
          </div>

          <a href="{{ route('sampling.history') }}"
             class="btn btn-sm btn-outline-secondary">
            Riwayat Sampling
          </a>
        </div>

        @if(session('ok'))
          <div class="alert alert-success m-2">{{ session('ok') }}</div>
        @endif

        <div class="card-body border-bottom">
          <form class="row g-1" method="GET">

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
                <th>Status Sampling</th>
                <th>Tanggal Sampling</th>
                <th class="text-center" style="width:220px;">Aksi</th>
              </tr>
            </thead>

            <tbody>
              @forelse ($rows as $idx => $row)
                @php
                  $statusSampling = $row->status_sampling ?? 'pending';

                  switch ($statusSampling) {
                      case 'accepted':
                          $badge  = 'badge-light-info';
                          $label  = 'Accepted';
                          break;
                      case 'confirmed':
                          $badge  = 'badge-light-success';
                          $label  = 'Confirmed';
                          break;
                      case 'rejected':
                          $badge  = 'badge-light-danger';
                          $label  = 'Rejected';
                          break;
                      case 'pending':
                      default:
                          $badge  = 'badge-light-warning';
                          $label  = 'Pending';
                          break;
                  }

                  $canConfirm = ($statusSampling === 'accepted');
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

                  {{-- FIX: tampilkan tanggal tanpa jam --}}
                  <td>{{ $fmtDate($row->tgl_sampling) }}</td>

                  <td class="text-center">
                    <div class="d-flex flex-column flex-lg-row gap-50 justify-content-center">

                      {{-- ACC: set status_sampling = accepted, tetap di index --}}
                      <form method="POST" action="{{ route('sampling.acc', $row->id) }}">
                        @csrf
                        <button type="submit"
                                class="btn btn-sm btn-outline-success"
                                onclick="return confirm('Accept Sampling untuk batch {{ $row->kode_batch }}?')">
                          Accept
                        </button>
                      </form>

                      {{-- KONFIRMASI: pindah ke Riwayat + kirim ke Review --}}
                      <form method="POST" action="{{ route('sampling.confirm', $row->id) }}">
                        @csrf
                        <button type="submit"
                                class="btn btn-sm btn-success"
                                onclick="return confirm('Konfirmasi Sampling dan kirim ke Review?')"
                                {{ $canConfirm ? '' : 'disabled' }}>
                          Konfirmasi
                        </button>
                      </form>

                      {{-- Reject: langsung final, pindah ke Riwayat --}}
                      <form method="POST" action="{{ route('sampling.reject', $row->id) }}">
                        @csrf
                        <button type="submit"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Tolak Sampling untuk batch {{ $row->kode_batch }}?')">
                          Tolak
                        </button>
                      </form>

                    </div>
                  </td>
                </tr>

              @empty
                <tr>
                  <td colspan="9" class="text-center text-muted">
                    Belum ada data sampling.
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