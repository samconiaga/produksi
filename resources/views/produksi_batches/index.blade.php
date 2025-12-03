@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title">Upload Work Order (Weighing)</h4>
            <p class="mb-0 text-muted">
              Upload file Excel WO dari Produksi, lalu tanggal tiap proses bisa diedit di sistem.
            </p>
          </div>

          <form action="{{ route('permintaan.upload') }}"
                method="POST"
                enctype="multipart/form-data"
                class="d-flex align-items-center">
            @csrf
            <input type="file"
                   name="file"
                   class="form-control form-control-sm me-1"
                   required>
            <button type="submit"
                    class="btn btn-primary btn-sm"
                    style="white-space: nowrap;">
              Upload
            </button>
          </form>
        </div>

        @if(session('ok'))
          <div class="alert alert-success m-2">{{ session('ok') }}</div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger m-2">
            {{ $errors->first() }}
          </div>
        @endif

        <div class="card-body border-bottom">
          <form class="row g-1" method="GET">
            <div class="col-md-3">
              <input type="text"
                     name="q"
                     value="{{ $q }}"
                     class="form-control"
                     placeholder="Cari produksi / batch...">
            </div>

            <div class="col-md-2">
              <select name="bulan" class="form-control">
                <option value="">Semua Bulan</option>
                @for($i = 1; $i <= 12; $i++)
                  <option value="{{ $i }}" {{ (isset($bulan) && (int) $bulan === $i) ? 'selected' : '' }}>
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
              <button class="btn btn-outline-primary w-100">Filter</button>
            </div>
          </form>
        </div>

        <div class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Produksi</th>
                <th>No Batch</th>
                <th>Kode Batch</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>WO Date</th>
                <th>Expected Date</th>

                {{-- urutan proses sesuai Excel --}}
                <th>Weighing</th>
                <th>Mixing (Mulai s/d Selesai)</th>
                <th>Tgl Rilis Antara Granul</th>
                <th>Capsule Filling (Mulai s/d Selesai)</th>
                <th>Tableting (Mulai s/d Selesai)</th>
                <th>Tgl Rilis Antara Tablet</th>
                <th>Coating (Mulai s/d Selesai)</th>
                <th>Tgl Rilis Ruahan</th>
                <th>Primary Pack (Mulai s/d Selesai)</th>
                <th>Tgl Rilis Ruahan Akhir</th>
                <th>Secondary Pack (Mulai s/d Selesai)</th>

                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($rows as $idx => $row)
                <tr>
                  <td>{{ $rows->firstItem() + $idx }}</td>
                  <td>{{ $row->produksi->nama_produk ?? $row->nama_produk }}</td>
                  <td>{{ $row->no_batch }}</td>
                  <td>{{ $row->kode_batch }}</td>
                  <td>{{ $row->bulan }}</td>
                  <td>{{ $row->tahun }}</td>

                  <td>{{ optional($row->wo_date)->format('d-m-Y') }}</td>
                  <td>{{ optional($row->expected_date)->format('d-m-Y') }}</td>

                  {{-- WEIGHING: satu tanggal saja --}}
                  <td>
                    {{ optional($row->tgl_weighing)->format('d-m-Y') ?: '-' }}
                  </td>

                  {{-- MIXING: mulai s/d selesai --}}
                  <td>
                    @php
                      $mulai = optional($row->tgl_mulai_mixing)->format('d-m-Y');
                      $selesai = optional($row->tgl_mixing)->format('d-m-Y');
                    @endphp
                    @if($mulai || $selesai)
                      {{ $mulai ?: '-' }}@if($selesai) s/d {{ $selesai }}@endif
                    @else
                      -
                    @endif
                  </td>

                  {{-- QC rilis antara granul --}}
                  <td>{{ optional($row->tgl_rilis_granul)->format('d-m-Y') ?: '-' }}</td>

                  {{-- CAPSULE FILLING: mulai s/d selesai --}}
                  <td>
                    @php
                      $mulai = optional($row->tgl_mulai_capsule_filling)->format('d-m-Y');
                      $selesai = optional($row->tgl_capsule_filling)->format('d-m-Y');
                    @endphp
                    @if($mulai || $selesai)
                      {{ $mulai ?: '-' }}@if($selesai) s/d {{ $selesai }}@endif
                    @else
                      -
                    @endif
                  </td>

                  {{-- TABLETING: mulai s/d selesai --}}
                  <td>
                    @php
                      $mulai = optional($row->tgl_mulai_tableting)->format('d-m-Y');
                      $selesai = optional($row->tgl_tableting)->format('d-m-Y');
                    @endphp
                    @if($mulai || $selesai)
                      {{ $mulai ?: '-' }}@if($selesai) s/d {{ $selesai }}@endif
                    @else
                      -
                    @endif
                  </td>

                  {{-- QC rilis antara tablet --}}
                  <td>{{ optional($row->tgl_rilis_tablet)->format('d-m-Y') ?: '-' }}</td>

                  {{-- COATING: mulai s/d selesai --}}
                  <td>
                    @php
                      $mulai = optional($row->tgl_mulai_coating)->format('d-m-Y');
                      $selesai = optional($row->tgl_coating)->format('d-m-Y');
                    @endphp
                    @if($mulai || $selesai)
                      {{ $mulai ?: '-' }}@if($selesai) s/d {{ $selesai }}@endif
                    @else
                      -
                    @endif
                  </td>

                  {{-- QC rilis ruahan --}}
                  <td>{{ optional($row->tgl_rilis_ruahan)->format('d-m-Y') ?: '-' }}</td>

                  {{-- PRIMARY PACK: mulai s/d selesai --}}
                  <td>
                    @php
                      $mulai = optional($row->tgl_mulai_primary_pack)->format('d-m-Y');
                      $selesai = optional($row->tgl_primary_pack)->format('d-m-Y');
                    @endphp
                    @if($mulai || $selesai)
                      {{ $mulai ?: '-' }}@if($selesai) s/d {{ $selesai }}@endif
                    @else
                      -
                    @endif
                  </td>

                  {{-- QC rilis ruahan akhir --}}
                  <td>{{ optional($row->tgl_rilis_ruahan_akhir)->format('d-m-Y') ?: '-' }}</td>

                  {{-- SECONDARY PACK: mulai s/d selesai --}}
                  <td>
                    @php
                      $mulai = optional($row->tgl_mulai_secondary_pack_1)->format('d-m-Y');
                      $selesai = optional($row->tgl_secondary_pack_1)->format('d-m-Y');
                    @endphp
                    @if($mulai || $selesai)
                      {{ $mulai ?: '-' }}@if($selesai) s/d {{ $selesai }}@endif
                    @else
                      -
                    @endif
                  </td>

                  <td>{{ $row->status_proses ?? '-' }}</td>
                  <td>
                    <a href="{{ route('edit-permintaan', $row->id) }}"
                       class="btn btn-sm btn-outline-primary">
                      Edit
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="22" class="text-center text-muted">
                    Belum ada jadwal produksi
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="card-body">
          {{ $rows->withQueryString()->links() }}
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
