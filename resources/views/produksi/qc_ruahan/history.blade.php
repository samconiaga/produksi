@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- HEADER --}}
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-0">Riwayat Produk Ruahan</h4>
            <p class="mb-0 text-muted">
              Menampilkan batch yang <strong>sudah Release Ruahan</strong>.
              Data di sini bersifat historis (read-only).
            </p>
          </div>

          <a href="{{ route('qc-ruahan.index') }}"
             class="btn btn-sm btn-outline-secondary">
            &laquo; Kembali ke Data Aktif
          </a>
        </div>

        {{-- FILTER BAR --}}
        <div class="card-body border-bottom">
          <form method="GET" class="row g-1">
            <div class="col-md-4">
              <input type="text"
                     name="q"
                     class="form-control"
                     placeholder="Cari produk / no batch / kode batch..."
                     value="{{ $search ?? '' }}">
            </div>

            <div class="col-md-3">
              @php $currentBulan = $bulan ?? request('bulan', 'all'); @endphp
              <select name="bulan" class="form-control">
                <option value="all" {{ $currentBulan === 'all' || $currentBulan === null ? 'selected' : '' }}>
                  Semua Bulan
                </option>
                @for($m = 1; $m <= 12; $m++)
                  @php $val = (string) $m; @endphp
                  <option value="{{ $val }}" {{ (string)$currentBulan === $val ? 'selected' : '' }}>
                    {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
                  </option>
                @endfor
              </select>
            </div>

            <div class="col-md-2">
              <input type="number"
                     name="tahun"
                     class="form-control"
                     placeholder="Tahun"
                     value="{{ $tahun ?? '' }}">
            </div>

            <div class="col-md-3">
              <button class="btn btn-outline-primary w-100">
                Filter
              </button>
            </div>
          </form>
        </div>

        {{-- TABEL RIWAYAT --}}
        <div class="table-responsive">
          <table class="table table-striped mb-0 align-middle">
            <thead>
              <tr>
                <th style="width: 40px;">#</th>
                <th>Produk</th>
                <th>No Batch</th>
                <th>Kode Batch</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>WO Date</th>
                <th>Mixing</th>
                <th class="text-center">Tgl Datang Ruahan</th>
                <th class="text-center">Tgl Analisa Ruahan</th>
                <th class="text-center">Tgl Release Ruahan</th>
              </tr>
            </thead>

            <tbody>
            @forelse($batches as $idx => $batch)
              <tr>
                <td>{{ $batches->firstItem() + $idx }}</td>
                <td>{{ $batch->produksi->nama_produk ?? $batch->nama_produk }}</td>
                <td>{{ $batch->no_batch }}</td>
                <td>{{ $batch->kode_batch }}</td>
                <td>{{ $batch->bulan }}</td>
                <td>{{ $batch->tahun }}</td>
                <td>{{ $batch->wo_date ? $batch->wo_date->format('d-m-Y') : '-' }}</td>
                <td>{{ $batch->tgl_mixing ? $batch->tgl_mixing->format('d-m-Y') : '-' }}</td>
                <td class="text-center">
                  {{ $batch->tgl_datang_ruahan
                        ? $batch->tgl_datang_ruahan->format('d-m-Y')
                        : '-' }}
                </td>
                <td class="text-center">
                  {{ $batch->tgl_analisa_ruahan
                        ? $batch->tgl_analisa_ruahan->format('d-m-Y')
                        : '-' }}
                </td>
                <td class="text-center">
                  {{ $batch->tgl_rilis_ruahan
                        ? $batch->tgl_rilis_ruahan->format('d-m-Y')
                        : '-' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="text-center text-muted">
                  Belum ada riwayat Produk Ruahan.
                </td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>

        <div class="card-body">
          {{ $batches->withQueryString()->links() }}
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
