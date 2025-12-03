@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row">
    <div class="col-12">
      <div class="card">

        {{-- HEADER --}}
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-0">Detail Coating Batch</h4>
            <p class="mb-0 text-muted">
              Kelola start / stop proses Coating per step untuk batch ini.
            </p>
          </div>

          <a href="{{ route('coating.index') }}" class="btn btn-sm btn-outline-secondary">
            &laquo; Kembali ke daftar Coating
          </a>
        </div>

        <div class="card-body">

          @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif

          {{-- INFO BATCH --}}
          <div class="row mb-3">
            <div class="col-md-8">
              <h5 class="mb-1">{{ $batch->produksi->nama_produk ?? $batch->nama_produk }}</h5>
              <div class="text-muted small">
                No Batch: <strong>{{ $batch->no_batch }}</strong> |
                Kode Batch: <strong>{{ $batch->kode_batch }}</strong> |
                Bulan/Tahun: <strong>{{ $batch->bulan }}/{{ $batch->tahun }}</strong>
              </div>
              <div class="text-muted small mt-1">
                WO Date:
                <strong>{{ $batch->wo_date ? $batch->wo_date->format('d-m-Y') : '-' }}</strong> ·
                Expected:
                <strong>{{ $batch->expected_date ? $batch->expected_date->format('d-m-Y') : '-' }}</strong> ·
                Tableting selesai:
                <strong>{{ $batch->tgl_tableting ? $batch->tgl_tableting->format('d-m-Y H:i') : '-' }}</strong>
              </div>
            </div>

            <div class="col-md-4 text-md-end mt-2 mt-md-0">
              <div class="small text-muted">
                Mulai Coating (summary):<br>
                <strong>{{ $batch->tgl_mulai_coating ? $batch->tgl_mulai_coating->format('d-m-Y H:i') : '-' }}</strong>
              </div>
              <div class="small text-muted mt-1">
                Selesai Coating (summary):<br>
                <strong>{{ $batch->tgl_coating ? $batch->tgl_coating->format('d-m-Y H:i') : '-' }}</strong>
              </div>
            </div>
          </div>

          <hr>

          @if (! $isTabletSalut)
            {{-- SINGLE STEP (non tablet salut) --}}
            <div class="row">
              <div class="col-md-4">
                <div class="card border-primary">
                  <div class="card-body">
                    <h6 class="card-title mb-1">Coating</h6>
                    <p class="text-muted small mb-2">
                      Produk non tablet salut, hanya 1 kali proses Coating.
                    </p>

                    <p class="small mb-1">
                      Mulai:
                      <strong>{{ $batch->tgl_mulai_coating ? $batch->tgl_mulai_coating->format('d-m-Y H:i') : '-' }}</strong><br>
                      Selesai:
                      <strong>{{ $batch->tgl_coating ? $batch->tgl_coating->format('d-m-Y H:i') : '-' }}</strong>
                    </p>

                    <div class="d-flex gap-1 mt-2">
                      @if (! $batch->tgl_mulai_coating)
                        <form method="POST" action="{{ route('coating.start', $batch->id) }}" class="flex-fill">
                          @csrf
                          <input type="hidden" name="step" value="main">
                          <button class="btn btn-sm btn-outline-primary w-100">
                            Start
                          </button>
                        </form>
                      @elseif (! $batch->tgl_coating)
                        <form method="POST" action="{{ route('coating.stop', $batch->id) }}"
                              class="flex-fill"
                              onsubmit="return confirm('Selesai Coating untuk batch ini?');">
                          @csrf
                          <input type="hidden" name="step" value="main">
                          <button class="btn btn-sm btn-primary w-100">
                            Stop
                          </button>
                        </form>
                      @else
                        <span class="badge bg-success flex-fill text-center py-1">
                          Coating selesai
                        </span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @else
            {{-- MULTI STEP (tablet salut gula) --}}
            <div class="row g-3">

              {{-- STEP 1: INTI --}}
              <div class="col-md-3 col-sm-6">
                <div class="card h-100 border-primary">
                  <div class="card-body d-flex flex-column">
                    <h6 class="card-title mb-1">Salut Inti</h6>
                    <p class="text-muted small mb-2">Step 1</p>

                    <p class="small mb-2">
                      Mulai:
                      <strong>{{ $batch->tgl_mulai_coating_inti ? $batch->tgl_mulai_coating_inti->format('d-m-Y H:i') : '-' }}</strong><br>
                      Selesai:
                      <strong>{{ $batch->tgl_coating_inti ? $batch->tgl_coating_inti->format('d-m-Y H:i') : '-' }}</strong>
                    </p>

                    <div class="mt-auto">
                      @if (! $batch->tgl_mulai_coating_inti)
                        <form method="POST" action="{{ route('coating.start', $batch->id) }}">
                          @csrf
                          <input type="hidden" name="step" value="inti">
                          <button class="btn btn-sm btn-outline-primary w-100">Start</button>
                        </form>
                      @elseif (! $batch->tgl_coating_inti)
                        <form method="POST"
                              action="{{ route('coating.stop', $batch->id) }}"
                              onsubmit="return confirm('Selesai Salut Inti?');">
                          @csrf
                          <input type="hidden" name="step" value="inti">
                          <button class="btn btn-sm btn-primary w-100">Stop</button>
                        </form>
                      @else
                        <span class="badge bg-success w-100 text-center">Selesai</span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>

              {{-- STEP 2: DASAR --}}
              <div class="col-md-3 col-sm-6">
                <div class="card h-100 border-primary">
                  <div class="card-body d-flex flex-column">
                    <h6 class="card-title mb-1">Salut Dasar</h6>
                    <p class="text-muted small mb-2">Step 2</p>

                    <p class="small mb-2">
                      Mulai:
                      <strong>{{ $batch->tgl_mulai_coating_dasar ? $batch->tgl_mulai_coating_dasar->format('d-m-Y H:i') : '-' }}</strong><br>
                      Selesai:
                      <strong>{{ $batch->tgl_coating_dasar ? $batch->tgl_coating_dasar->format('d-m-Y H:i') : '-' }}</strong>
                    </p>

                    <div class="mt-auto">
                      @if (! $batch->tgl_mulai_coating_dasar)
                        <form method="POST" action="{{ route('coating.start', $batch->id) }}">
                          @csrf
                          <input type="hidden" name="step" value="dasar">
                          <button class="btn btn-sm btn-outline-primary w-100">Start</button>
                        </form>
                      @elseif (! $batch->tgl_coating_dasar)
                        <form method="POST"
                              action="{{ route('coating.stop', $batch->id) }}"
                              onsubmit="return confirm('Selesai Salut Dasar?');">
                          @csrf
                          <input type="hidden" name="step" value="dasar">
                          <button class="btn btn-sm btn-primary w-100">Stop</button>
                        </form>
                      @else
                        <span class="badge bg-success w-100 text-center">Selesai</span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>

              {{-- STEP 3: WARNA --}}
              <div class="col-md-3 col-sm-6">
                <div class="card h-100 border-primary">
                  <div class="card-body d-flex flex-column">
                    <h6 class="card-title mb-1">Salut Warna</h6>
                    <p class="text-muted small mb-2">Step 3</p>

                    <p class="small mb-2">
                      Mulai:
                      <strong>{{ $batch->tgl_mulai_coating_warna ? $batch->tgl_mulai_coating_warna->format('d-m-Y H:i') : '-' }}</strong><br>
                      Selesai:
                      <strong>{{ $batch->tgl_coating_warna ? $batch->tgl_coating_warna->format('d-m-Y H:i') : '-' }}</strong>
                    </p>

                    <div class="mt-auto">
                      @if (! $batch->tgl_mulai_coating_warna)
                        <form method="POST" action="{{ route('coating.start', $batch->id) }}">
                          @csrf
                          <input type="hidden" name="step" value="warna">
                          <button class="btn btn-sm btn-outline-primary w-100">Start</button>
                        </form>
                      @elseif (! $batch->tgl_coating_warna)
                        <form method="POST"
                              action="{{ route('coating.stop', $batch->id) }}"
                              onsubmit="return confirm('Selesai Salut Warna?');">
                          @csrf
                          <input type="hidden" name="step" value="warna">
                          <button class="btn btn-sm btn-primary w-100">Stop</button>
                        </form>
                      @else
                        <span class="badge bg-success w-100 text-center">Selesai</span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>

              {{-- STEP 4: POLISHING --}}
              <div class="col-md-3 col-sm-6">
                <div class="card h-100 border-primary">
                  <div class="card-body d-flex flex-column">
                    <h6 class="card-title mb-1">Polishing</h6>
                    <p class="text-muted small mb-2">Step 4</p>

                    <p class="small mb-2">
                      Mulai:
                      <strong>{{ $batch->tgl_mulai_coating_polishing ? $batch->tgl_mulai_coating_polishing->format('d-m-Y H:i') : '-' }}</strong><br>
                      Selesai:
                      <strong>{{ $batch->tgl_coating_polishing ? $batch->tgl_coating_polishing->format('d-m-Y H:i') : '-' }}</strong>
                    </p>

                    <div class="mt-auto">
                      @if (! $batch->tgl_mulai_coating_polishing)
                        <form method="POST" action="{{ route('coating.start', $batch->id) }}">
                          @csrf
                          <input type="hidden" name="step" value="polishing">
                          <button class="btn btn-sm btn-outline-primary w-100">Start</button>
                        </form>
                      @elseif (! $batch->tgl_coating_polishing)
                        <form method="POST"
                              action="{{ route('coating.stop', $batch->id) }}"
                              onsubmit="return confirm('Selesai Polishing?');">
                          @csrf
                          <input type="hidden" name="step" value="polishing">
                          <button class="btn btn-sm btn-primary w-100">Stop</button>
                        </form>
                      @else
                        <span class="badge bg-success w-100 text-center">Selesai</span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>

            </div>
          @endif

        </div>

      </div>
    </div>
  </div>
</section>
@endsection
