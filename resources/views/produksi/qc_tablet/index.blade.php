{{-- qc_tablet/index.blade --}}
@extends('layouts.app')

@section('content')
@php
  $roleLower = strtolower($user->role ?? '');
  $isAdmin   = in_array($roleLower, ['admin','administrator','superadmin'], true);

  // semua login boleh release & hold (sesuai request)
  $canRelease = !empty($user);
  $canHold    = !empty($user);

  $bulanAktif = $bulan ?? request('bulan', 'all');

  // fallback kalau route named tidak terdaftar
  $hasHoldRoute = \Illuminate\Support\Facades\Route::has('qc-tablet.hold');

@endphp

<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- HEADER --}}
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
          <div>
            <h4 class="card-title mb-0">QC Produk Antara Tablet (Data Aktif)</h4>
            <small class="text-muted">
              Input <strong>Tanggal Release</strong> lalu klik <strong>Release</strong>. (Tanpa datang/start/stop/analisa)
            </small>
          </div>

          <a href="{{ route('qc-tablet.history') }}" class="btn btn-sm btn-outline-secondary">
            Riwayat
          </a>
        </div>

        {{-- FLASH --}}
        @if(session('success'))
          <div class="alert alert-success m-2 py-1 mb-0">{{ session('success') }}</div>
        @endif
        @if($errors->any())
          <div class="alert alert-danger m-2 py-1 mb-0">{{ $errors->first() }}</div>
        @endif

        {{-- FILTER --}}
        <div class="card-body border-bottom">
          <form method="GET" action="{{ route('qc-tablet.index') }}" class="row g-1">
            <div class="col-md-4">
              <input type="text" name="q" class="form-control"
                     placeholder="Cari produk / no batch / kode batch..." value="{{ $search ?? '' }}">
            </div>

            <div class="col-md-3">
              <select name="bulan" class="form-control">
                <option value="all" {{ $bulanAktif === 'all' || $bulanAktif === null ? 'selected' : '' }}>Semua Bulan</option>
                @for($m=1;$m<=12;$m++)
                  @php $val=(string)$m; @endphp
                  <option value="{{ $val }}" {{ (string)$bulanAktif === $val ? 'selected' : '' }}>
                    {{ str_pad($m,2,'0',STR_PAD_LEFT) }}
                  </option>
                @endfor
              </select>
            </div>

            <div class="col-md-2">
              <input type="number" name="tahun" class="form-control" placeholder="Tahun" value="{{ $tahun ?? '' }}">
            </div>

            <div class="col-md-3">
              <button class="btn btn-outline-primary w-100">Filter</button>
            </div>
          </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
          <table class="table table-striped mb-0 align-middle">
            <thead>
              <tr>
                <th style="width:40px;">#</th>
                <th>Nama Produk</th>
                <th>No WO</th>
                <th>Kode Batch</th>
                <th>Tableting</th>
                <th class="text-center" style="width:180px;">Tanggal Release</th>
                <!-- Lebarkan kolom aksi sedikit supaya dua tombol muat berdampingan -->
                <th class="text-center" style="width:260px;">Aksi</th>
              </tr>
            </thead>

            <tbody>
            @forelse($batches as $idx => $batch)
              @php
                $holdUrl = $hasHoldRoute
                  ? route('qc-tablet.hold', $batch)
                  : url('/qc-tablet/'.$batch->id.'/hold');

                // nilai default tanggal (format HTML date)
                $defaultDate = optional($batch->tgl_rilis_tablet)->format('Y-m-d') ?? now()->format('Y-m-d');
              @endphp

              <tr id="row-tablet-{{ $batch->id }}">
                <td>{{ $batches->firstItem() + $idx }}</td>
                <td>{{ $batch->produksi->nama_produk ?? $batch->nama_produk ?? '-' }}</td>
                <td>{{ $batch->no_batch ?? '-' }}</td>
                <td>{{ $batch->kode_batch ?? '-' }}</td>
                <td>{{ $batch->tgl_tableting ? $batch->tgl_tableting->format('d-m-Y') : '-' }}</td>

                {{-- TANGGAL RELEASE (kolom terpisah) --}}
                <td class="text-center">
                  <input type="date"
                         id="tgl-{{ $batch->id }}"
                         class="form-control form-control-sm mx-auto"
                         style="max-width:160px;"
                         value="{{ old('tgl_rilis_tablet', $defaultDate) }}">
                </td>

                {{-- AKSI: Release & Hold sejajar --}}
                <td class="text-center">
                  {{-- gunakan flex-nowrap supaya tombol tidak turun ke baris baru --}}
                  <div class="d-flex justify-content-center align-items-center gap-2 flex-nowrap">

                    {{-- Release form: hidden input akan diisi dari date input saat submit --}}
                    <form action="{{ route('qc-tablet.release', $batch) }}" method="POST"
                          class="d-inline"
                          onsubmit="
                            (function(){
                              var d = document.getElementById('tgl-{{ $batch->id }}').value;
                              if(!d){
                                alert('Isi tanggal release terlebih dahulu.');
                                return false;
                              }
                              document.getElementById('hidden-tgl-{{ $batch->id }}').value = d;
                              return confirm('Release batch ini?');
                            })();
                          ">
                      @csrf
                      <input type="hidden" name="tgl_rilis_tablet" id="hidden-tgl-{{ $batch->id }}" value="{{ $defaultDate }}">
                      <button type="submit"
                              class="btn btn-sm btn-success"
                              style="min-width:90px;"
                              {{ $canRelease ? '' : 'disabled' }}>
                        Release
                      </button>
                    </form>

                    {{-- Hold form/button --}}
                    @if($canHold)
                      <form action="{{ $hasHoldRoute ? route('qc-tablet.hold', $batch) : url('/qc-tablet/'.$batch->id.'/hold') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit"
                                class="btn btn-sm btn-outline-danger"
                                style="min-width:90px;"
                                onclick="return confirm('Pindahkan batch ini ke Holding (QC Tablet)?');">
                          Hold
                        </button>
                      </form>
                    @else
                      <span class="text-muted">-</span>
                    @endif

                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted">Belum ada batch untuk QC Produk Antara Tablet.</td>
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
