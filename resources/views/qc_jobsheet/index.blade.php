@extends('layouts.app')

@section('content')

@php
  use Carbon\Carbon;

  // Format aman: kalau null -> '-', kalau datetime/date string -> tampil YYYY-MM-DD aja
  $fmtDate = function ($value) {
      if (empty($value)) return '-';
      try {
          return Carbon::parse($value)->format('Y-m-d');
      } catch (\Throwable $e) {
          // fallback: kalau parsing gagal, tampilkan mentah tapi tanpa jam kalau ada
          return str_replace(' 00:00:00', '', (string) $value);
      }
  };
@endphp

<style>
  .col-catatan-review { max-width: 350px; white-space: normal; }
  @media (min-width: 1400px) {
    .col-catatan-review { max-width: 460px; }
  }
</style>

<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-0">Job Sheet</h4>
            <p class="mb-0 text-muted">
              Menampilkan batch yang sudah dikonfirmasi Qty Batch dan sedang disusun Job Sheet
              atau dikembalikan dari Review (Hold).
            </p>
          </div>

          <a href="{{ route('qc-jobsheet.history') }}" class="btn btn-sm btn-outline-secondary">
            Riwayat Job Sheet
          </a>
        </div>

        {{-- Flash --}}
        @if(session('ok'))
          <div class="alert alert-success m-2">{{ session('ok') }}</div>
        @endif

        {{-- Filter --}}
        <div class="card-body border-bottom">
          <form class="row g-1" method="GET" action="{{ route('qc-jobsheet.index') }}">

            <div class="col-md-3">
              <input type="text" name="q" value="{{ $q ?? '' }}"
                     class="form-control"
                     placeholder="Cari produk / no batch / kode batch...">
            </div>

            <div class="col-md-2">
              <select name="bulan" class="form-control">
                <option value="">Semua Bulan</option>
                @for($i=1;$i<=12;$i++)
                  <option value="{{ $i }}"
                    {{ (string)($bulan ?? '') === (string)$i ? 'selected' : '' }}>
                    {{ sprintf('%02d',$i) }}
                  </option>
                @endfor
              </select>
            </div>

            <div class="col-md-2">
              <input type="number" name="tahun" class="form-control"
                     value="{{ $tahun ?? '' }}" placeholder="Tahun">
            </div>

            <div class="col-md-2">
              <button class="btn btn-outline-primary w-100">Filter</button>
            </div>

          </form>
        </div>

        {{-- TABEL --}}
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
                <th class="text-center" style="width:230px;">Aksi</th>
              </tr>
            </thead>

            <tbody>
            @forelse($rows as $idx => $row)

              @php
                // boleh konfirmasi kalau tanggal konfirmasi + terima jobsheet sudah terisi
                $canConfirm = !empty($row->tgl_konfirmasi_produksi) && !empty($row->tgl_terima_jobsheet);

                // STATUS REVIEW: hanya "kelihatan" kalau sudah pernah direview (hold / released / rejected)
                $stRev = $row->status_review;
                if (!$stRev || $stRev === 'pending') {
                    $badgeRev  = '';
                    $stRevText = '-';
                } else {
                    switch($stRev) {
                        case 'released':
                            $badgeRev = 'badge-light-success'; $stRevText='Released'; break;
                        case 'hold':
                            $badgeRev = 'badge-light-warning'; $stRevText='Hold'; break;
                        case 'rejected':
                            $badgeRev = 'badge-light-danger'; $stRevText='Rejected'; break;
                        default:
                            $badgeRev = 'badge-light-secondary'; $stRevText=$stRev;
                    }
                }

                // CATATAN REVIEW (ditampilkan hanya kalau ada)
                $catatanFull  = trim($row->catatan_review ?? '');
                $catatanShort = $catatanFull ? \Illuminate\Support\Str::limit($catatanFull, 150) : '-';
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

                {{-- STATUS REVIEW --}}
                <td>
                  @if($badgeRev)
                    <span class="badge {{ $badgeRev }}">{{ $stRevText }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>

                {{-- CATATAN REVIEW --}}
                <td class="col-catatan-review">
                  @if($catatanFull && $stRev && $stRev !== 'pending')
                    <div class="small text-muted" title="{{ $catatanFull }}">
                      {{ $catatanShort }}
                    </div>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>

                {{-- Aksi --}}
                <td class="text-center">
                  <div class="d-grid gap-50">

                    <a href="{{ route('qc-jobsheet.edit',$row->id) }}"
                       class="btn btn-sm btn-outline-secondary w-100">
                      Isi / Ubah Job Sheet
                    </a>

                    <form action="{{ route('qc-jobsheet.confirm',$row->id) }}"
                          method="POST"
                          onsubmit="return confirm('Konfirmasi Job Sheet dan kirim ke Review?');">
                      @csrf
                      <button type="submit"
                              class="btn btn-sm btn-primary w-100"
                              {{ $canConfirm ? '' : 'disabled' }}>
                        @if($row->status_review === 'hold')
                          Kirim Ulang ke Review
                        @else
                          Konfirmasi &amp; Kirim ke Review
                        @endif
                      </button>
                    </form>

                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="text-center text-muted">
                  Belum ada data job sheet untuk batch.
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