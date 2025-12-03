@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- HEADER --}}
        <div class="card-header">
          <div class="row align-items-center w-100">
            <div class="col-md-8">
              <h4 class="card-title mb-0">Primary Pack</h4>
              <p class="mb-0 text-muted">
                Proses <strong>Primary Pack</strong> per batch dengan mode
                <strong>Start / Stop</strong> (realtime tanggal &amp; jam).
                Data di bawah adalah batch yang sudah melewati proses sebelum Primary
                (Coating / Ruahan) dan belum selesai Primary Pack.
              </p>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
              <a href="{{ route('primary-pack.history') }}"
                 class="btn btn-sm btn-outline-secondary">
                Riwayat Primary Pack
              </a>
            </div>
          </div>
        </div>

        {{-- FLASH MESSAGE --}}
        @if(session('ok'))
          <div class="alert alert-success m-2 py-1 mb-0">{{ session('ok') }}</div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger m-2 py-1 mb-0">
            {{ $errors->first() }}
          </div>
        @endif

        {{-- FILTER --}}
        <div class="card-body border-bottom">
          <form class="row g-2 align-items-center"
                method="GET"
                action="{{ route('primary-pack.index') }}">

            <div class="col-md-4">
              <input type="text"
                     name="q"
                     value="{{ $q }}"
                     class="form-control"
                     placeholder="Cari produk / no batch / kode batch...">
            </div>

            <div class="col-md-2">
              @php $currentBulan = $bulan ?? request('bulan', ''); @endphp
              <select name="bulan" class="form-select">
                <option value="">Semua Bulan</option>
                @for($i = 1; $i <= 12; $i++)
                  <option value="{{ $i }}" {{ (string)$currentBulan === (string)$i ? 'selected' : '' }}>
                    {{ sprintf('%02d', $i) }}
                  </option>
                @endfor
              </select>
            </div>

            <div class="col-md-2">
              <input type="number"
                     name="tahun"
                     value="{{ $tahun }}"
                     class="form-control"
                     placeholder="Tahun">
            </div>

            <div class="col-md-2">
              <button class="btn btn-outline-primary w-100">
                Filter
              </button>
            </div>

            <div class="col-md-2">
              <a href="{{ route('primary-pack.index') }}"
                 class="btn btn-outline-secondary w-100">
                Reset
              </a>
            </div>
          </form>
        </div>

        {{-- TABEL --}}
        <div class="table-responsive">
          <table class="table mb-0 align-middle">
            <thead>
              <tr class="text-muted">
                <th width="50">#</th>
                <th width="120">Kode Batch</th>
                <th>Nama Produk</th>
                <th width="80">Bulan</th>
                <th width="90">Tahun</th>
                <th width="120">WO Date</th>
                <th width="160">Coating (Selesai)</th>
                <th width="180">Primary (Mulai)</th>
                <th width="180">Primary (Selesai)</th>
                <th width="240" class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
            @forelse($rows as $idx => $row)
              @php
                $started  = !empty($row->tgl_mulai_primary_pack);
                $finished = !empty($row->tgl_primary_pack);
              @endphp
              <tr>
                <td>{{ $rows->firstItem() + $idx }}</td>
                <td>{{ $row->kode_batch }}</td>
                <td>{{ $row->produksi->nama_produk ?? $row->nama_produk }}</td>
                <td>{{ $row->bulan }}</td>
                <td>{{ $row->tahun }}</td>
                <td>{{ optional($row->wo_date)->format('d-m-Y') }}</td>
                <td>{{ optional($row->tgl_coating)->format('d-m-Y H:i') }}</td>

                <td>
                  @if($row->tgl_mulai_primary_pack)
                    {{ $row->tgl_mulai_primary_pack->format('d-m-Y H:i') }}
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>

                <td>
                  @if($row->tgl_primary_pack)
                    {{ $row->tgl_primary_pack->format('d-m-Y H:i') }}
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>

                <td class="text-end">
                  <div class="d-flex justify-content-end align-items-center gap-50">

                    {{-- Status kecil --}}
                    <div class="me-1">
                      @if($finished)
                        <span class="badge rounded-pill bg-success-subtle text-success">
                          Selesai
                        </span>
                      @elseif($started)
                        <span class="badge rounded-pill bg-warning-subtle text-warning">
                          Sedang proses
                        </span>
                      @else
                        <span class="badge rounded-pill bg-secondary-subtle text-muted">
                          Belum mulai
                        </span>
                      @endif
                    </div>

                    {{-- Tombol Start / Stop --}}
                    @if(!$started && !$finished)
                      {{-- Belum mulai: hanya tombol START --}}
                      <form action="{{ route('primary-pack.start', $row->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="btn btn-sm btn-success">
                          Start
                        </button>
                      </form>
                    @elseif($started && !$finished)
                      {{-- Sedang proses: Start disabled, ada STOP --}}
                      <form class="me-25">
                        <button type="button"
                                class="btn btn-sm btn-outline-success"
                                disabled>
                          Started
                        </button>
                      </form>

                      <form action="{{ route('primary-pack.stop', $row->id) }}"
                            method="POST"
                            onsubmit="return confirm('Hentikan Primary Pack untuk batch ini?');">
                        @csrf
                        <button type="submit"
                                class="btn btn-sm btn-danger">
                          Stop
                        </button>
                      </form>
                    @else
                      {{-- Selesai: tidak ada aksi --}}
                      <span class="text-muted small">Tidak ada aksi</span>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-muted">
                  Belum ada data untuk Primary Pack.
                </td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>

        {{-- PAGINATION --}}
        <div class="card-body">
          {{ $rows->links() }}
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
