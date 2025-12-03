@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-0">Tableting</h4>
            <p class="mb-0 text-muted">
              Menampilkan batch tablet yang sudah selesai Mixing namun belum selesai Tableting.
            </p>
          </div>

          <a href="{{ route('tableting.history') }}" class="btn btn-sm btn-outline-secondary">
            Riwayat Tableting
          </a>
        </div>

        <div class="card-body">

          @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif

          @if($errors->any())
            <div class="alert alert-danger">
              {{ $errors->first() }}
            </div>
          @endif

          {{-- Filter --}}
          <form method="GET" action="{{ route('tableting.index') }}" class="row g-2 mb-3">
            <div class="col-md-4">
              <input type="text"
                     name="q"
                     class="form-control"
                     placeholder="Cari produk / no batch / kode batch"
                     value="{{ $search ?? request('q') }}">
            </div>

            <div class="col-md-3">
              @php
                $currentBulan = $bulan ?? request('bulan', 'all');
              @endphp
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
                     value="{{ $tahun ?? request('tahun') }}">
            </div>

            <div class="col-md-3">
              <button class="btn btn-primary w-100">Filter</button>
            </div>
          </form>

          {{-- Tabel Tableting --}}
          <div class="table-responsive">
            <table class="table table-striped align-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Produk</th>
                  <th>No Batch</th>
                  <th>Kode Batch</th>
                  <th>Bulan</th>
                  <th>Tahun</th>
                  <th>WO Date</th>
                  <th>Expected Date</th>
                  <th>Mixing Selesai</th>
                  <th>Mulai Tableting</th>
                  <th>Selesai Tableting</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>

              @forelse($batches as $index => $batch)
                @php
                  $mixDone = $batch->tgl_mixing
                    ? $batch->tgl_mixing->format('d-m-Y H:i')
                    : '-';

                  $mulai = $batch->tgl_mulai_tableting
                    ? $batch->tgl_mulai_tableting->format('d-m-Y H:i')
                    : '-';

                  $selesai = $batch->tgl_tableting
                    ? $batch->tgl_tableting->format('d-m-Y H:i')
                    : '-';
                @endphp

                <tr>
                  <td>{{ $batches->firstItem() + $index }}</td>

                  <td>{{ $batch->produksi->nama_produk ?? $batch->nama_produk }}</td>
                  <td>{{ $batch->no_batch }}</td>
                  <td>{{ $batch->kode_batch }}</td>
                  <td>{{ $batch->bulan }}</td>
                  <td>{{ $batch->tahun }}</td>

                  <td>{{ $batch->wo_date ? $batch->wo_date->format('d-m-Y') : '-' }}</td>
                  <td>{{ $batch->expected_date ? $batch->expected_date->format('d-m-Y') : '-' }}</td>
                  <td>{{ $mixDone }}</td>
                  <td>{{ $mulai }}</td>
                  <td>{{ $selesai }}</td>

                  <td class="text-center">

                    {{-- form START --}}
                    <form id="tableting-start-{{ $batch->id }}"
                          action="{{ route('tableting.start', $batch) }}"
                          method="POST"
                          class="d-inline">
                      @csrf
                    </form>

                    {{-- form STOP --}}
                    <form id="tableting-stop-{{ $batch->id }}"
                          action="{{ route('tableting.stop', $batch) }}"
                          method="POST"
                          class="d-inline">
                      @csrf
                    </form>

                    @if(is_null($batch->tgl_mulai_tableting))
                      {{-- Belum mulai --}}
                      <button type="submit"
                              form="tableting-start-{{ $batch->id }}"
                              class="btn btn-sm btn-outline-primary">
                        Start
                      </button>
                    @elseif(is_null($batch->tgl_tableting))
                      {{-- Sudah mulai, belum selesai --}}
                      <button type="submit"
                              form="tableting-stop-{{ $batch->id }}"
                              class="btn btn-sm btn-primary"
                              onclick="return confirm('Stop / selesai Tableting untuk batch ini?');">
                        Stop
                      </button>
                    @endif

                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="12" class="text-center">
                    Tidak ada data batch yang menunggu proses Tableting.
                  </td>
                </tr>
              @endforelse

              </tbody>
            </table>
          </div>

          {{ $batches->links() }}

        </div>
      </div>
    </div>
  </div>
</section>
@endsection
