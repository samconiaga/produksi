@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- HEADER --}}
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-0">Coating</h4>
            <p class="mb-0 text-muted">
              Proses realtime Coating per batch (Start / Stop).
              Data diambil dari batch yang sudah selesai Tableting.
            </p>
          </div>

          <div class="d-flex gap-1">
            <a href="{{ route('coating.history') }}" class="btn btn-sm btn-outline-secondary">
              Riwayat Coating
            </a>
          </div>
        </div>

        <div class="card-body">

          @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif

          {{-- FILTER --}}
          <form method="GET" action="{{ route('coating.index') }}" class="row g-2 mb-3">
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
                @for ($m = 1; $m <= 12; $m++)
                  @php $val = (string) $m; @endphp
                  <option value="{{ $val }}" {{ (string) $currentBulan === $val ? 'selected' : '' }}>
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

          {{-- TABEL COATING --}}
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
                  <th>Tableting Selesai</th>
                  <th>Mulai Coating</th>
                  <th>Selesai Coating</th>
                  <th style="min-width: 230px;">Aksi</th>
                </tr>
              </thead>

              <tbody>
              @forelse ($batches as $index => $batch)
                @php
                  $produk        = $batch->produksi;
                  $namaProduk    = $produk->nama_produk ?? $batch->nama_produk;
                  $bentukSediaan = $produk->bentuk_sediaan ?? null;

                  // Hanya tablet salut gula yang multi-step
                  $isTabletSalutGula = $bentukSediaan &&
                      \Illuminate\Support\Str::contains(
                          \Illuminate\Support\Str::lower($bentukSediaan),
                          'salut gula'
                      );

                  $isEaz       = \Illuminate\Support\Str::contains($batch->kode_batch, 'EAZ-');
                  $canSplitEaz =
                      \Illuminate\Support\Str::contains($batch->kode_batch, 'EA-') &&
                      ! $isEaz;

                  $tabletingDone = $batch->tgl_tableting
                    ? $batch->tgl_tableting->format('d-m-Y H:i')
                    : '-';

                  $mulai = $batch->tgl_mulai_coating
                    ? $batch->tgl_mulai_coating->format('d-m-Y H:i')
                    : '-';

                  $selesai = $batch->tgl_coating
                    ? $batch->tgl_coating->format('d-m-Y H:i')
                    : '-';
                @endphp

                <tr>
                  <td>{{ $batches->firstItem() + $index }}</td>
                  <td>{{ $namaProduk }}</td>
                  <td>{{ $batch->no_batch }}</td>
                  <td>{{ $batch->kode_batch }}</td>
                  <td>{{ $batch->bulan }}</td>
                  <td>{{ $batch->tahun }}</td>
                  <td>{{ $batch->wo_date ? $batch->wo_date->format('d-m-Y') : '-' }}</td>
                  <td>{{ $batch->expected_date ? $batch->expected_date->format('d-m-Y') : '-' }}</td>
                  <td>{{ $tabletingDone }}</td>
                  <td>{{ $mulai }}</td>
                  <td>{{ $selesai }}</td>

                  <td>
                    <div class="d-flex flex-wrap gap-1 align-items-center">

                      {{-- MESIN 2 (EAZ) --}}
                      @if ($canSplitEaz)
                        <form action="{{ route('coating.split-eaz', $batch->id) }}"
                              method="POST"
                              onsubmit="return confirm('Buat batch mesin Coating 2 (EAZ)?');">
                          @csrf
                          <button type="submit"
                                  class="btn btn-sm btn-outline-primary"
                                  style="white-space: nowrap;">
                            + EAZ
                          </button>
                        </form>
                      @endif

                      {{-- AKSI COATING --}}
                      @if ($isTabletSalutGula)
                        {{-- Tablet Salut Gula → Kelola multi-step --}}
                        <a href="{{ route('coating.show', $batch->id) }}"
                           class="btn btn-sm btn-primary"
                           style="white-space: nowrap;">
                          Kelola Coating
                        </a>
                      @else
                        {{-- Produk lain → langsung Start/Stop 1 step (main) --}}
                        <form id="coating-start-{{ $batch->id }}"
                              action="{{ route('coating.start', $batch) }}"
                              method="POST"
                              class="d-inline">
                          @csrf
                          <input type="hidden" name="step" value="main">
                        </form>

                        <form id="coating-stop-{{ $batch->id }}"
                              action="{{ route('coating.stop', $batch) }}"
                              method="POST"
                              class="d-inline">
                          @csrf
                          <input type="hidden" name="step" value="main">
                        </form>

                        @if(is_null($batch->tgl_mulai_coating))
                          <button type="submit"
                                  form="coating-start-{{ $batch->id }}"
                                  class="btn btn-sm btn-outline-primary"
                                  style="white-space: nowrap;">
                            Start
                          </button>
                        @elseif(is_null($batch->tgl_coating))
                          <button type="submit"
                                  form="coating-stop-{{ $batch->id }}"
                                  class="btn btn-sm btn-primary"
                                  style="white-space: nowrap;"
                                  onclick="return confirm('Stop / selesai Coating untuk batch ini?');">
                            Stop
                          </button>
                        @else
                          <span class="badge bg-light text-muted"
                                style="white-space: nowrap;">
                            Coating Selesai
                          </span>
                        @endif
                      @endif

                      {{-- HAPUS EAZ --}}
                      @if ($isEaz)
                        <form action="{{ route('coating.destroy-eaz', $batch->id) }}"
                              method="POST"
                              onsubmit="return confirm('Hapus baris mesin 2 (EAZ) ini?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit"
                                  class="btn btn-sm btn-outline-danger"
                                  style="white-space: nowrap;">
                            Hapus EAZ
                          </button>
                        </form>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="12" class="text-center">
                    Tidak ada data batch untuk Coating.
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
