@extends('layouts.app')

@section('content')
<section class="app-user">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h4 class="card-title mb-0">QC Release (Tanggal Datang / Analisa / Release)</h4>
        <p class="mb-0 text-muted">
          Input tanggal QC per tahap (Granul → Tablet → Ruahan → Ruahan Akhir)
          mengikuti urutan proses produksi &amp; tipe alur produk.
        </p>
      </div>

      <div>
        <a href="{{ route('qc-release.history') }}"
           class="btn btn-sm btn-outline-secondary">
          Riwayat QC Release
        </a>
      </div>
    </div>

    <div class="card-body">

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      {{-- Filter --}}
      <form method="GET" action="{{ route('qc-release.index') }}" class="row g-2 mb-3">
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

      {{-- Tabel QC Release --}}
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            {{-- Header baris pertama: grup kolom --}}
            <tr>
              <th rowspan="2">#</th>
              <th rowspan="2">Produk</th>
              <th rowspan="2">No Batch</th>
              <th rowspan="2">Kode Batch</th>
              <th rowspan="2">Bulan</th>
              <th rowspan="2">Tahun</th>
              <th rowspan="2">WO Date</th>
              <th rowspan="2">Mixing</th>

              <th colspan="3" class="text-center bg-light">Produk Antara Granul</th>
              <th colspan="3" class="text-center bg-light">Produk Antara Tablet</th>
              <th colspan="3" class="text-center bg-light">Produk Ruahan</th>
              <th colspan="3" class="text-center bg-light">Produk Ruahan Akhir</th>

              <th rowspan="2">Simpan</th>
            </tr>

            {{-- Header baris kedua: sub-kolom --}}
            <tr>
              <th class="text-center">Tgl Datang</th>
              <th class="text-center">Tgl Analisa</th>
              <th class="text-center">Tgl Release</th>

              <th class="text-center">Tgl Datang</th>
              <th class="text-center">Tgl Analisa</th>
              <th class="text-center">Tgl Release</th>

              <th class="text-center">Tgl Datang</th>
              <th class="text-center">Tgl Analisa</th>
              <th class="text-center">Tgl Release</th>

              <th class="text-center">Tgl Datang</th>
              <th class="text-center">Tgl Analisa</th>
              <th class="text-center">Tgl Release</th>
            </tr>
          </thead>

          <tbody>
          @forelse($batches as $index => $batch)
            @php
              $formId = 'qc-form-' . $batch->id;

              $tipeAlur = $batch->produksi->tipe_alur ?? $batch->tipe_alur ?? '';

              // Mapping flow QC per tipe_alur
              $hasGranul      = false;
              $hasTablet      = false;
              $hasRuahan      = false;
              $hasRuahanAkhir = false;

              switch ($tipeAlur) {
                  case 'CLO':
                      $hasRuahanAkhir = true;
                      break;

                  case 'CAIRAN_LUAR':
                      $hasRuahan = true;
                      break;

                  case 'DRY_SYRUP':
                      $hasRuahan      = true;
                      $hasRuahanAkhir = true;
                      break;

                  case 'TABLET_NON_SALUT':
                  case 'TABLET_SALUT':
                      $hasGranul = $hasTablet = $hasRuahan = true;
                      break;

                  case 'KAPSUL':
                      $hasGranul = $hasRuahan = true;
                      break;

                  default:
                      $hasRuahanAkhir = true;
                      break;
              }

              $qcGranulReleased = !empty($batch->tgl_rilis_granul);
              $qcTabletReleased = !empty($batch->tgl_rilis_tablet);
              $qcRuahanReleased = !empty($batch->tgl_rilis_ruahan);

              $canGranul = $hasGranul && !empty($batch->tgl_mixing);

              $canTablet = $hasTablet
                           && !empty($batch->tgl_tableting)
                           && (!$hasGranul || $qcGranulReleased);

              $canRuahanProcess = !empty($batch->tgl_coating)
                                  || !empty($batch->tgl_capsule_filling)
                                  || !empty($batch->tgl_mixing);

              $prevQcForRuahanOk = $hasTablet
                  ? $qcTabletReleased
                  : ($hasGranul ? $qcGranulReleased : true);

              $canRuahan = $hasRuahan && $canRuahanProcess && $prevQcForRuahanOk;

              $prevQcForRuahanAkhirOk = $hasRuahan ? $qcRuahanReleased : true;

              $canRuahanAkhir = $hasRuahanAkhir
                                && !empty($batch->tgl_primary_pack)
                                && $prevQcForRuahanAkhirOk;
            @endphp

            <tr>
              <td>{{ $batches->firstItem() + $index }}</td>
              <td>{{ $batch->produksi->nama_produk ?? $batch->nama_produk }}</td>
              <td>{{ $batch->no_batch }}</td>
              <td>{{ $batch->kode_batch }}</td>
              <td>{{ $batch->bulan }}</td>
              <td>{{ $batch->tahun }}</td>
              <td>{{ optional($batch->wo_date)->format('d-m-Y') }}</td>
              <td>{{ optional($batch->tgl_mixing)->format('d-m-Y') }}</td>

              {{-- ========= GRANUL ========= --}}

              {{-- TGL DATANG + CHECK --}}
              <td>
                @if (! $hasGranul || ! $canGranul)
                  {!! '&nbsp;' !!}
                @else
                  <div class="d-flex align-items-center gap-1">
                    <input type="date"
                           name="tgl_datang_granul"
                           form="{{ $formId }}"
                           value="{{ old('tgl_datang_granul', optional($batch->tgl_datang_granul)->format('Y-m-d')) }}"
                           class="form-control form-control-sm">

                    <button type="submit"
                            form="{{ $formId }}"
                            name="action"
                            value="save"
                            class="btn btn-sm btn-outline-success"
                            title="Konfirmasi kedatangan"
                            onclick="return confirm('Konfirmasi tanggal datang granul?');">
                      ✓
                    </button>
                  </div>
                @endif
              </td>

              {{-- ANALISA Start / Stop --}}
              <td class="text-center">
                @if (! $hasGranul || ! $canGranul)
                  {!! '&nbsp;' !!}
                @else
                  <div class="d-flex flex-column gap-1 align-items-center">
                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="start_analisa_granul"
                            class="btn btn-sm btn-outline-primary py-0"
                            {{ $batch->tgl_analisa_granul ? 'disabled' : '' }}>
                      Start
                    </button>

                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="stop_analisa_granul"
                            class="btn btn-sm btn-outline-secondary py-0"
                            {{ $batch->tgl_analisa_granul ? '' : 'disabled' }}>
                      Stop
                    </button>
                  </div>
                @endif
              </td>

              {{-- RELEASE Start / Stop --}}
              <td class="text-center">
                @if (! $hasGranul || ! $canGranul)
                  {!! '&nbsp;' !!}
                @else
                  <div class="d-flex flex-column gap-1 align-items-center">
                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="start_release_granul"
                            class="btn btn-sm btn-outline-success py-0"
                            {{ $batch->tgl_rilis_granul ? 'disabled' : '' }}>
                      Start
                    </button>

                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="stop_release_granul"
                            class="btn btn-sm btn-outline-secondary py-0"
                            {{ $batch->tgl_rilis_granul ? '' : 'disabled' }}>
                      Stop
                    </button>
                  </div>
                @endif
              </td>

              {{-- ========= TABLET ========= --}}

              {{-- TGL DATANG + CHECK --}}
              <td>
                @if (! $hasTablet || ! $canTablet)
                  {!! '&nbsp;' !!}
                @else
                  <div class="d-flex align-items-center gap-1">
                    <input type="date"
                           name="tgl_datang_tablet"
                           form="{{ $formId }}"
                           value="{{ old('tgl_datang_tablet', optional($batch->tgl_datang_tablet)->format('Y-m-d')) }}"
                           class="form-control form-control-sm">

                    <button type="submit"
                            form="{{ $formId }}"
                            name="action"
                            value="save"
                            class="btn btn-sm btn-outline-success"
                            title="Konfirmasi kedatangan"
                            onclick="return confirm('Konfirmasi tanggal datang tablet?');">
                      ✓
                    </button>
                  </div>
                @endif
              </td>

              {{-- ANALISA Start / Stop --}}
              <td class="text-center">
                @if (! $hasTablet || ! $canTablet)
                  {!! '&nbsp;' !!}
                @else
                  <div class="d-flex flex-column gap-1 align-items-center">
                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="start_analisa_tablet"
                            class="btn btn-sm btn-outline-primary py-0"
                            {{ $batch->tgl_analisa_tablet ? 'disabled' : '' }}>
                      Start
                    </button>

                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="stop_analisa_tablet"
                            class="btn btn-sm btn-outline-secondary py-0"
                            {{ $batch->tgl_analisa_tablet ? '' : 'disabled' }}>
                      Stop
                    </button>
                  </div>
                @endif
              </td>

              {{-- RELEASE Start / Stop --}}
              <td class="text-center">
                @if (! $hasTablet || ! $canTablet)
                  {!! '&nbsp;' !!}
                @else
                  <div class="d-flex flex-column gap-1 align-items-center">
                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="start_release_tablet"
                            class="btn btn-sm btn-outline-success py-0"
                            {{ $batch->tgl_rilis_tablet ? 'disabled' : '' }}>
                      Start
                    </button>

                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="stop_release_tablet"
                            class="btn btn-sm btn-outline-secondary py-0"
                            {{ $batch->tgl_rilis_tablet ? '' : 'disabled' }}>
                      Stop
                    </button>
                  </div>
                @endif
              </td>

              {{-- ========= RUAHAN ========= --}}

              {{-- TGL DATANG + CHECK --}}
              <td>
                @if (! $hasRuahan || ! $canRuahan)
                  {!! '&nbsp;' !!}
                @else
                  <div class="d-flex align-items-center gap-1">
                    <input type="date"
                           name="tgl_datang_ruahan"
                           form="{{ $formId }}"
                           value="{{ old('tgl_datang_ruahan', optional($batch->tgl_datang_ruahan)->format('Y-m-d')) }}"
                           class="form-control form-control-sm">

                    <button type="submit"
                            form="{{ $formId }}"
                            name="action"
                            value="save"
                            class="btn btn-sm btn-outline-success"
                            title="Konfirmasi kedatangan"
                            onclick="return confirm('Konfirmasi tanggal datang ruahan?');">
                      ✓
                    </button>
                  </div>
                @endif
              </td>

              {{-- ANALISA Start / Stop --}}
              <td class="text-center">
                @if (! $hasRuahan || ! $canRuahan)
                  {!! '&nbsp;' !!}
                @else
                  <div class="d-flex flex-column gap-1 align-items-center">
                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="start_analisa_ruahan"
                            class="btn btn-sm btn-outline-primary py-0"
                            {{ $batch->tgl_analisa_ruahan ? 'disabled' : '' }}>
                      Start
                    </button>

                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="stop_analisa_ruahan"
                            class="btn btn-sm btn-outline-secondary py-0"
                            {{ $batch->tgl_analisa_ruahan ? '' : 'disabled' }}>
                      Stop
                    </button>
                  </div>
                @endif
              </td>

              {{-- RELEASE Start / Stop --}}
              <td class="text-center">
                @if (! $hasRuahan || ! $canRuahan)
                  {!! '&nbsp;' !!}
                @else
                  <div class="d-flex flex-column gap-1 align-items-center">
                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="start_release_ruahan"
                            class="btn btn-sm btn-outline-success py-0"
                            {{ $batch->tgl_rilis_ruahan ? 'disabled' : '' }}>
                      Start
                    </button>

                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="stop_release_ruahan"
                            class="btn btn-sm btn-outline-secondary py-0"
                            {{ $batch->tgl_rilis_ruahan ? '' : 'disabled' }}>
                      Stop
                    </button>
                  </div>
                @endif
              </td>

              {{-- ========= RUAHAN AKHIR ========= --}}

              {{-- TGL DATANG + CHECK --}}
              <td>
                @if (! $hasRuahanAkhir || ! $canRuahanAkhir)
                  {!! '&nbsp;' !!}
                @else
                  <div class="d-flex align-items-center gap-1">
                    <input type="date"
                           name="tgl_datang_ruahan_akhir"
                           form="{{ $formId }}"
                           value="{{ old('tgl_datang_ruahan_akhir', optional($batch->tgl_datang_ruahan_akhir)->format('Y-m-d')) }}"
                           class="form-control form-control-sm">

                    <button type="submit"
                            form="{{ $formId }}"
                            name="action"
                            value="save"
                            class="btn btn-sm btn-outline-success"
                            title="Konfirmasi kedatangan"
                            onclick="return confirm('Konfirmasi tanggal datang ruahan akhir?');">
                      ✓
                    </button>
                  </div>
                @endif
              </td>

              {{-- ANALISA Start / Stop --}}
              <td class="text-center">
                @if (! $hasRuahanAkhir || ! $canRuahanAkhir)
                  {!! '&nbsp;' !!}
                @else
                  <div class="d-flex flex-column gap-1 align-items-center">
                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="start_analisa_ruahan_akhir"
                            class="btn btn-sm btn-outline-primary py-0"
                            {{ $batch->tgl_analisa_ruahan_akhir ? 'disabled' : '' }}>
                      Start
                    </button>

                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="stop_analisa_ruahan_akhir"
                            class="btn btn-sm btn-outline-secondary py-0"
                            {{ $batch->tgl_analisa_ruahan_akhir ? '' : 'disabled' }}>
                      Stop
                    </button>
                  </div>
                @endif
              </td>

              {{-- RELEASE Start / Stop --}}
              <td class="text-center">
                @if (! $hasRuahanAkhir || ! $canRuahanAkhir)
                  {!! '&nbsp;' !!}
                @else
                  <div class="d-flex flex-column gap-1 align-items-center">
                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="start_release_ruahan_akhir"
                            class="btn btn-sm btn-outline-success py-0"
                            {{ $batch->tgl_rilis_ruahan_akhir ? 'disabled' : '' }}>
                      Start
                    </button>

                    <button type="submit"
                            form="{{ $formId }}"
                            name="qc_action"
                            value="stop_release_ruahan_akhir"
                            class="btn btn-sm btn-outline-secondary py-0"
                            {{ $batch->tgl_rilis_ruahan_akhir ? '' : 'disabled' }}>
                      Stop
                    </button>
                  </div>
                @endif
              </td>

              {{-- FORM SIMPAN --}}
              <td>
                <form id="{{ $formId }}"
                      action="{{ route('qc-release.update', $batch) }}"
                      method="POST">
                  @csrf
                  @method('PUT')

                  <button type="submit"
                          name="action"
                          value="save"
                          class="btn btn-sm btn-outline-primary w-100">
                    Simpan
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="22" class="text-center">
                Tidak ada batch yang menunggu rilis QC.
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

      {{ $batches->links() }}
    </div>
  </div>
</section>
@endsection
