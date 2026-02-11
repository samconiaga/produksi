@extends('layouts.app')

@section('content')
<section class="app-user">
  <div class="card">

    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
      <div>
        <h4 class="card-title mb-0">Riwayat Tableting</h4>
        <small class="text-muted">
          Daftar batch yang sudah selesai proses Tableting (termasuk Rekon saat STOP).
        </small>
      </div>

      <a href="{{ route('tableting.index') }}" class="btn btn-sm btn-outline-primary">
        Kembali ke Tableting Aktif
      </a>
    </div>

    <div class="card-body">

      @php
        $bulanAktif   = $bulan ?? 'all';
        $perPageAktif = request('per_page', $perPage ?? 25);
        $namaBulan = [
          1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
          5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
          9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
      @endphp

      {{-- FILTER --}}
      <form method="get" class="row g-1 g-md-2 align-items-center mb-2">
        <div class="col-12 col-md-4 col-lg-4">
          <input type="text"
                 name="q"
                 class="form-control form-control-sm"
                 placeholder="Cari produk / batch / kode..."
                 value="{{ $search }}">
        </div>

        <div class="col-6 col-md-3 col-lg-3">
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

        <div class="col-6 col-md-2 col-lg-2">
          <input type="number"
                 name="tahun"
                 class="form-control form-control-sm"
                 placeholder="Tahun"
                 value="{{ $tahun }}">
        </div>

        <div class="col-6 col-md-2 col-lg-2">
          <select name="per_page" class="form-select form-select-sm">
            @foreach([25, 50, 100] as $opt)
              <option value="{{ $opt }}" {{ (int)$perPageAktif === $opt ? 'selected' : '' }}>
                {{ $opt }} / halaman
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-1 col-lg-1 text-end">
          <button class="btn btn-sm btn-primary w-100">Filter</button>
        </div>
      </form>

      @if(session('success'))
        <div class="alert alert-success py-1 mb-2">{!! session('success') !!}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger py-1 mb-2">{{ $errors->first() }}</div>
      @endif

      {{-- TABLE --}}
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light">
            <tr class="text-nowrap">
              <th style="width:40px;">#</th>
              <th>Nama Produk</th>
              <th>No WO</th>
              <th>Mixing Selesai</th>
              <th>Tableting Awal</th>
              <th>Tableting Akhir</th>
              <th class="text-end" style="width:170px;">Rekon</th>
              <th style="width:160px;">Diinput</th>
            </tr>
          </thead>

          <tbody>
          @forelse($batches as $i => $batch)
            @php
              $rekonQty = $batch->tableting_rekon_qty;
              $rekonAt  = $batch->tableting_rekon_at;

              $rekonText = '-';
              if ($rekonQty !== null && $rekonQty !== '') {
                $rekonText = number_format((int)$rekonQty, 0, ',', '.');
              }
            @endphp

            <tr>
              <td>{{ $batches->firstItem() + $i }}</td>
              <td>{{ $batch->produksi->nama_produk ?? $batch->nama_produk }}</td>
              <td>{{ $batch->kode_batch ?? $batch->no_batch }}</td>

              <td>{{ $batch->tgl_mixing ? $batch->tgl_mixing->format('d-m-Y H:i') : '-' }}</td>
              <td>{{ $batch->tgl_mulai_tableting ? $batch->tgl_mulai_tableting->format('d-m-Y H:i') : '-' }}</td>
              <td>{{ $batch->tgl_tableting ? $batch->tgl_tableting->format('d-m-Y H:i') : '-' }}</td>

              <td class="text-end">
                <span class="fw-semibold">{{ $rekonText }}</span>
              </td>

              <td>
                @if($rekonAt)
                  {{ \Carbon\Carbon::parse($rekonAt)->format('d-m-Y H:i') }}
                @else
                  -
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted">Belum ada riwayat Tableting.</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

    </div>

    <div class="card-body">
      <div class="d-flex justify-content-center">
        {{ $batches->withQueryString()->links('pagination::bootstrap-4') }}
      </div>
    </div>

  </div>
</section>
@endsection