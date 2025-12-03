@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- HEADER --}}
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-0">QC Produk Antara Tablet (Data Aktif)</h4>
            <p class="mb-0 text-muted">
              Konfirmasi <strong>Tanggal Datang Tablet</strong>, mulai <strong>Analisa</strong> (Start),
              dan akhiri dengan <strong>Release</strong> (Stop). Data yang sudah release
              akan pindah ke menu <em>Riwayat Produk Antara Tablet</em>.
            </p>
          </div>

          <a href="{{ route('qc-tablet.history') }}"
             class="btn btn-sm btn-outline-secondary">
            Riwayat Produk Antara Tablet
          </a>
        </div>

        {{-- FLASH MESSAGE --}}
        @if(session('success'))
          <div class="alert alert-success m-2">
            {{ session('success') }}
          </div>
        @endif

        {{-- FILTER BAR --}}
        <div class="card-body border-bottom">
          <form method="GET" action="{{ route('qc-tablet.index') }}" class="row g-1">

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

        {{-- TABEL --}}
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
                <th class="text-center">Tgl Datang Tablet</th>
                <th class="text-center">Tgl Analisa Tablet</th>
                <th class="text-center">Tgl Release Tablet</th>
              </tr>
            </thead>

            <tbody>
            @forelse($batches as $idx => $batch)
              @php $formId = 'form-tablet-' . $batch->id; @endphp

              <tr>
                <td>{{ $batches->firstItem() + $idx }}</td>
                <td>{{ $batch->produksi->nama_produk ?? $batch->nama_produk }}</td>
                <td>{{ $batch->no_batch }}</td>
                <td>{{ $batch->kode_batch }}</td>
                <td>{{ $batch->bulan }}</td>
                <td>{{ $batch->tahun }}</td>
                <td>{{ $batch->wo_date ? $batch->wo_date->format('d-m-Y') : '-' }}</td>
                <td>{{ $batch->tgl_mixing ? $batch->tgl_mixing->format('d-m-Y') : '-' }}</td>

                {{-- TGL DATANG + CONFIRM --}}
                <td class="text-center">
                  <div class="d-flex justify-content-center align-items-center gap-50">
                    <input type="date"
                           name="tgl_datang_tablet"
                           form="{{ $formId }}"
                           value="{{ optional($batch->tgl_datang_tablet)->format('Y-m-d') }}"
                           class="form-control form-control-sm"
                           style="max-width: 150px;">

                    <button type="submit"
                            form="{{ $formId }}"
                            name="action"
                            value="confirm_datang"
                            class="btn btn-sm btn-outline-success"
                            title="Konfirmasi tanggal datang">
                      <i data-feather="check"></i>
                    </button>
                  </div>
                </td>

                {{-- ANALISA (START) --}}
                <td class="text-center">
                  <div class="d-flex flex-column gap-25 align-items-center">
                    <span class="small text-muted">
                      {{ $batch->tgl_analisa_tablet
                            ? $batch->tgl_analisa_tablet->format('d-m-Y')
                            : '-' }}
                    </span>

                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="start_analisa"
                            class="btn btn-sm btn-outline-primary py-0"
                            {{ $batch->tgl_analisa_tablet ? 'disabled' : '' }}>
                      Start
                    </button>
                  </div>
                </td>

                {{-- RELEASE (STOP) --}}
                <td class="text-center">
                  <div class="d-flex flex-column gap-25 align-items-center">
                    <span class="small text-muted">
                      {{ $batch->tgl_rilis_tablet
                            ? $batch->tgl_rilis_tablet->format('d-m-Y')
                            : '-' }}
                    </span>

                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="stop_release"
                            class="btn btn-sm btn-outline-danger py-0"
                            {{ $batch->tgl_rilis_tablet ? 'disabled' : '' }}>
                      Stop
                    </button>
                  </div>
                </td>
              </tr>

              {{-- FORM HIDDEN PER BARIS --}}
              <form id="{{ $formId }}"
                    action="{{ route('qc-tablet.update', $batch) }}"
                    method="POST" class="d-none">
                @csrf
                @method('PUT')
              </form>

            @empty
              <tr>
                <td colspan="11" class="text-center text-muted">
                  Belum ada batch untuk QC Produk Antara Tablet.
                </td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>

        {{-- PAGINATION --}}
        <div class="card-body">
          {{ $batches->withQueryString()->links() }}
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
