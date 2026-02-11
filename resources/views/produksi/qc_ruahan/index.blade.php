@extends('layouts.app')

@section('content')
@php
  $bulanAktif = $bulan ?? request('bulan', 'all');

  $namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
  ];

  function dmy($v){
    if(empty($v)) return '-';
    try{
      if($v instanceof \Carbon\CarbonInterface) return $v->format('d-m-Y');
      return \Carbon\Carbon::parse($v)->format('d-m-Y');
    }catch(\Throwable $e){
      return (string)$v;
    }
  }

  function triggerRuahan($batch){
    if (($batch->tipe_alur ?? '') === 'TABLET_NON_SALUT') return dmy($batch->tgl_tableting ?? null);
    if (($batch->tipe_alur ?? '') === 'TABLET_SALUT')     return dmy($batch->tgl_coating ?? null);
    if (($batch->tipe_alur ?? '') === 'KAPSUL')           return dmy($batch->tgl_capsule_filling ?? null);
    return dmy($batch->tgl_mixing ?? null); // CAIRAN_LUAR / DRY_SYRUP
  }

  // optional: dropdown per halaman (biar sama kayak granul)
  $perPage = (int) request('per_page', 20);
  if (!in_array($perPage, [10,20,50,100], true)) $perPage = 20;
@endphp

<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- HEADER (mirip granul) --}}
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
          <div>
            <h4 class="card-title mb-0">QC Produk Ruahan (Data Aktif)</h4>
            <small class="text-muted">
              Menampilkan batch aktif (belum <strong>Release Ruahan</strong>) sesuai alur proses.
            </small>
          </div>

          <a href="{{ route('qc-ruahan.history') }}" class="btn btn-sm btn-outline-secondary">
            Riwayat Produk Ruahan
          </a>
        </div>

        {{-- FLASH --}}
        @if(session('success'))
          <div class="alert alert-success m-2 py-1 mb-0">{{ session('success') }}</div>
        @endif
        @if($errors->any())
          <div class="alert alert-danger m-2 py-1 mb-0">{{ $errors->first() }}</div>
        @endif

        {{-- FILTER BAR (layout dibuat sama kayak granul) --}}
        <div class="card-body border-bottom">
          <form method="GET" action="{{ route('qc-ruahan.index') }}" class="row g-1 align-items-center">
            <div class="col-12 col-md-4">
              <input type="text" name="q" class="form-control form-control-sm"
                     placeholder="Cari produk / no batch / kode batch..." value="{{ $search ?? '' }}">
            </div>

            <div class="col-6 col-md-3">
              <select name="bulan" class="form-select form-select-sm">
                <option value="all" {{ $bulanAktif === 'all' || $bulanAktif === '' ? 'selected' : '' }}>
                  Semua Bulan
                </option>
                @foreach($namaBulan as $num => $label)
                  <option value="{{ $num }}" {{ (int)$bulanAktif === $num ? 'selected' : '' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-6 col-md-2">
              <input type="number" name="tahun" class="form-control form-control-sm"
                     placeholder="Tahun" value="{{ $tahun ?? '' }}">
            </div>

            {{-- PER PAGE (optional) --}}
            <div class="col-6 col-md-2">
              <select name="per_page" class="form-select form-select-sm">
                @foreach([10,20,50,100] as $n)
                  <option value="{{ $n }}" {{ $perPage===$n ? 'selected':'' }}>{{ $n }} / halaman</option>
                @endforeach
              </select>
            </div>

            <div class="col-6 col-md-1">
              <button class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
          </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr class="text-nowrap">
                <th style="width:40px;">#</th>
                <th>Nama Produk</th>
                <th>No WO</th>
                <th>Kode Batch</th>
                <th>Trigger Proses</th>
                <th class="text-center" style="width:180px;">Tanggal Release</th>
                <th class="text-center" style="width:210px;">Aksi</th>
              </tr>
            </thead>

            <tbody>
            @forelse($batches as $idx => $batch)
              @php
                $formRelease = 'rel-ruahan-'.$batch->id;
                $formHold    = 'hold-ruahan-'.$batch->id;
              @endphp

              <tr id="row-ruahan-{{ $batch->id }}">
                <td>{{ $batches->firstItem() + $idx }}</td>
                <td>{{ $batch->produksi->nama_produk ?? $batch->nama_produk }}</td>
                <td>{{ $batch->no_batch }}</td>
                <td>{{ $batch->kode_batch }}</td>
                <td>{{ triggerRuahan($batch) }}</td>

                <td class="text-center">
                  <input type="date"
                         name="tgl_rilis_ruahan"
                         form="{{ $formRelease }}"
                         class="form-control form-control-sm"
                         style="min-width:150px"
                         value="{{ old('tgl_rilis_ruahan', now()->toDateString()) }}"
                         required>
                </td>

                <td class="text-center">
                  <div class="d-flex gap-1 justify-content-center flex-wrap">
                    <button type="submit"
                            form="{{ $formRelease }}"
                            class="btn btn-sm btn-success"
                            onclick="return confirm('Release Ruahan untuk batch ini?');">
                      Release
                    </button>

                    <button type="submit"
                            form="{{ $formHold }}"
                            class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('Hold batch ini?');">
                      Hold
                    </button>
                  </div>

                  {{-- forms --}}
                  <form id="{{ $formRelease }}" action="{{ route('qc-ruahan.release', $batch) }}" method="POST" class="d-none">
                    @csrf
                    {{-- kalau controller release kamu validasi date, ini wajib --}}
                    {{-- input date sudah form="..." jadi ngikut form ini --}}
                  </form>

                  <form id="{{ $formHold }}" action="{{ route('qc-ruahan.hold', $batch) }}" method="POST" class="d-none">
                    @csrf
                    <input type="hidden" name="reason" value="Hold dari QC Ruahan">
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted">
                  Belum ada batch untuk QC Produk Ruahan.
                </td>
              </tr>
            @endforelse
            </tbody>

          </table>
        </div>

        {{-- PAGINATION --}}
        <div class="card-body">
          <div class="d-flex justify-content-center">
            {{ $batches->withQueryString()->links('pagination::bootstrap-4') }}
          </div>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection