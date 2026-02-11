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

<style>
  .col-catatan-review {
      max-width: 420px;
      white-space: normal;
  }
  @media (min-width: 1400px) {
      .col-catatan-review { max-width: 520px; }
  }
</style>

<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">

      <div class="card">

        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-0">Riwayat Job Sheet</h4>
            <p class="mb-0 text-muted">
              Menampilkan Job Sheet yang sudah dikonfirmasi.
            </p>
          </div>

          <a href="{{ route('qc-jobsheet.index') }}" class="btn btn-sm btn-outline-secondary">
            &laquo; Kembali ke Daftar Aktif
          </a>
        </div>

        {{-- Flash --}}
        @if(session('ok'))
          <div class="alert alert-success m-2">{{ session('ok') }}</div>
        @endif

        {{-- Filter --}}
        <div class="card-body border-bottom">
          <form class="row g-1" method="GET">

            <div class="col-md-3">
              <input type="text"
                     name="q"
                     value="{{ $q ?? '' }}"
                     class="form-control"
                     placeholder="Cari produk / no batch / kode batch...">
            </div>

            <div class="col-md-2">
              <select name="bulan" class="form-control">
                <option value="">Semua Bulan</option>
                @for ($i = 1; $i <= 12; $i++)
                  <option value="{{ $i }}"
                    {{ (string)($bulan ?? '') === (string)$i ? 'selected' : '' }}>
                    {{ sprintf('%02d', $i) }}
                  </option>
                @endfor
              </select>
            </div>

            <div class="col-md-2">
              <input type="number"
                     name="tahun"
                     value="{{ $tahun ?? '' }}"
                     class="form-control"
                     placeholder="Tahun">
            </div>

            <div class="col-md-2">
              <button class="btn btn-outline-primary w-100">Filter</button>
            </div>

          </form>
        </div>

        {{-- Tabel --}}
        <div class="table-responsive">
          <table class="table mb-0 align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Kode Batch</th>
                <th>Nama Produk</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>WO Date</th>
                <th>Konfirmasi Produksi</th>
                <th>Terima Job Sheet</th>
                <th>Status Review</th>
                <th class="col-catatan-review">Catatan Review</th>
              </tr>
            </thead>

            <tbody>
            @forelse($rows as $idx => $row)
              @php
                $statusReview = $row->status_review ?? 'pending';

                switch ($statusReview) {
                    case 'released':
                        $badgeClass = 'badge-light-success';
                        $statusText = 'Released';
                        break;
                    case 'hold':
                        $badgeClass = 'badge-light-warning';
                        $statusText = 'Hold';
                        break;
                    case 'rejected':
                        $badgeClass = 'badge-light-danger';
                        $statusText = 'Rejected';
                        break;
                    default:
                        $badgeClass = 'badge-light-secondary';
                        $statusText = 'Pending';
                }

                $catatanFull  = trim($row->catatan_review ?? '');
                $catatanShort = $catatanFull
                    ? \Illuminate\Support\Str::limit($catatanFull, 180)
                    : '-';
              @endphp

              <tr>
                <td>{{ $rows->firstItem() + $idx }}</td>
                <td>{{ $row->kode_batch }}</td>
                <td>{{ $row->nama_produk }}</td>
                <td>{{ $row->bulan }}</td>
                <td>{{ $row->tahun }}</td>

                {{-- FIX: hilangkan jam 00:00:00 --}}
                <td>{{ $fmtDate($row->wo_date) }}</td>
                <td>{{ $fmtDate($row->tgl_konfirmasi_produksi) }}</td>
                <td>{{ $fmtDate($row->tgl_terima_jobsheet) }}</td>

                <td>
                  <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                </td>

                <td class="col-catatan-review">
                  @if($catatanFull)
                    <div class="small text-muted" title="{{ $catatanFull }}">
                      {{ $catatanShort }}
                    </div>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-muted">
                  Belum ada riwayat Job Sheet.
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