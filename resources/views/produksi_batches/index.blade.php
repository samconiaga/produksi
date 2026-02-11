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

        {{-- FILTER + ACTION BAR --}}
        <div class="card-body border-bottom">
          <form class="row g-1 align-items-center" method="GET" id="filterForm">
            <div class="col-md-3">
              <input type="text"
                     name="q"
                     value="{{ $q }}"
                     class="form-control"
                     placeholder="Cari produksi / batch / kode...">
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

            {{-- tombol filter --}}
            <div class="col-md-2">
              <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
            </div>

            {{-- tombol masuk mode hapus --}}
            <div class="col-md-3 d-flex gap-1 justify-content-end">
              <button type="button" class="btn btn-outline-danger w-100" id="btnDeleteMode">
                Hapus
              </button>

              {{-- tombol hapus terpilih (muncul saat delete mode) --}}
              <button type="button"
                      class="btn btn-danger w-100 d-none"
                      id="btnDeleteSelected"
                      style="white-space: nowrap;">
                Hapus Terpilih
              </button>

              {{-- tombol batal (muncul saat delete mode) --}}
              <button type="button"
                      class="btn btn-outline-secondary w-100 d-none"
                      id="btnCancelDelete"
                      style="white-space: nowrap;">
                Batal
              </button>
            </div>
          </form>

          {{-- info kecil saat mode hapus --}}
          <div class="mt-2 text-muted d-none" id="deleteHint" style="font-size: 13px;">
            Mode hapus aktif — centang data yang mau dihapus, lalu klik <b>Hapus Terpilih</b>.
          </div>
        </div>

        {{-- BULK DELETE FORM (tombol submitnya kita trigger via JS) --}}
        <form action="{{ route('permintaan.bulk-delete') }}" method="POST" id="bulkDeleteForm">
          @csrf

          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  {{-- checkbox all (muncul saat delete mode) --}}
                  <th style="width:40px;">
                    <input type="checkbox" id="checkAll" class="d-none">
                  </th>

                  <th>#</th>
                  <th>Produksi</th>
                  <th>No WO</th>
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
                </tr>
              </thead>

              <tbody>
                @forelse($rows as $idx => $row)
                  <tr>
                    {{-- checkbox per row (muncul saat delete mode) --}}
                    <td>
                      <input type="checkbox"
                             name="ids[]"
                             value="{{ $row->id }}"
                             class="row-check d-none">
                    </td>

                    <td>{{ $rows->firstItem() + $idx }}</td>
                    <td>{{ $row->produksi->nama_produk ?? $row->nama_produk }}</td>
                    <td>{{ $row->no_batch }}</td>
                    <td>{{ $row->kode_batch }}</td>
                    <td>{{ $row->bulan }}</td>
                    <td>{{ $row->tahun }}</td>

                    <td>{{ $row->wo_date ? \Carbon\Carbon::parse($row->wo_date)->format('d-m-Y') : '-' }}</td>
                    <td>{{ $row->expected_date ? \Carbon\Carbon::parse($row->expected_date)->format('d-m-Y') : '-' }}</td>

                    <td>{{ $row->tgl_weighing ? \Carbon\Carbon::parse($row->tgl_weighing)->format('d-m-Y') : '-' }}</td>

                    {{-- MIXING --}}
                    <td>
                      @php
                        $mulai = $row->tgl_mulai_mixing ? \Carbon\Carbon::parse($row->tgl_mulai_mixing)->format('d-m-Y') : null;
                        $selesai = $row->tgl_mixing ? \Carbon\Carbon::parse($row->tgl_mixing)->format('d-m-Y') : null;
                      @endphp
                      @if($mulai || $selesai)
                        {{ $mulai ?: '-' }}@if($selesai) s/d {{ $selesai }}@endif
                      @else
                        -
                      @endif
                    </td>

                    <td>{{ $row->tgl_rilis_granul ? \Carbon\Carbon::parse($row->tgl_rilis_granul)->format('d-m-Y') : '-' }}</td>

                    {{-- CAPSULE FILLING --}}
                    <td>
                      @php
                        $mulai = $row->tgl_mulai_capsule_filling ? \Carbon\Carbon::parse($row->tgl_mulai_capsule_filling)->format('d-m-Y') : null;
                        $selesai = $row->tgl_capsule_filling ? \Carbon\Carbon::parse($row->tgl_capsule_filling)->format('d-m-Y') : null;
                      @endphp
                      @if($mulai || $selesai)
                        {{ $mulai ?: '-' }}@if($selesai) s/d {{ $selesai }}@endif
                      @else
                        -
                      @endif
                    </td>

                    {{-- TABLETING --}}
                    <td>
                      @php
                        $mulai = $row->tgl_mulai_tableting ? \Carbon\Carbon::parse($row->tgl_mulai_tableting)->format('d-m-Y') : null;
                        $selesai = $row->tgl_tableting ? \Carbon\Carbon::parse($row->tgl_tableting)->format('d-m-Y') : null;
                      @endphp
                      @if($mulai || $selesai)
                        {{ $mulai ?: '-' }}@if($selesai) s/d {{ $selesai }}@endif
                      @else
                        -
                      @endif
                    </td>

                    <td>{{ $row->tgl_rilis_tablet ? \Carbon\Carbon::parse($row->tgl_rilis_tablet)->format('d-m-Y') : '-' }}</td>

                    {{-- COATING --}}
                    <td>
                      @php
                        $mulai = $row->tgl_mulai_coating ? \Carbon\Carbon::parse($row->tgl_mulai_coating)->format('d-m-Y') : null;
                        $selesai = $row->tgl_coating ? \Carbon\Carbon::parse($row->tgl_coating)->format('d-m-Y') : null;
                      @endphp
                      @if($mulai || $selesai)
                        {{ $mulai ?: '-' }}@if($selesai) s/d {{ $selesai }}@endif
                      @else
                        -
                      @endif
                    </td>

                    <td>{{ $row->tgl_rilis_ruahan ? \Carbon\Carbon::parse($row->tgl_rilis_ruahan)->format('d-m-Y') : '-' }}</td>

                    {{-- PRIMARY PACK --}}
                    <td>
                      @php
                        $mulai = $row->tgl_mulai_primary_pack ? \Carbon\Carbon::parse($row->tgl_mulai_primary_pack)->format('d-m-Y') : null;
                        $selesai = $row->tgl_primary_pack ? \Carbon\Carbon::parse($row->tgl_primary_pack)->format('d-m-Y') : null;
                      @endphp
                      @if($mulai || $selesai)
                        {{ $mulai ?: '-' }}@if($selesai) s/d {{ $selesai }}@endif
                      @else
                        -
                      @endif
                    </td>

                    <td>{{ $row->tgl_rilis_ruahan_akhir ? \Carbon\Carbon::parse($row->tgl_rilis_ruahan_akhir)->format('d-m-Y') : '-' }}</td>

                    {{-- SECONDARY PACK --}}
                    <td>
                      @php
                        $mulai = $row->tgl_mulai_secondary_pack_1 ? \Carbon\Carbon::parse($row->tgl_mulai_secondary_pack_1)->format('d-m-Y') : null;
                        $selesai = $row->tgl_secondary_pack_1 ? \Carbon\Carbon::parse($row->tgl_secondary_pack_1)->format('d-m-Y') : null;
                      @endphp
                      @if($mulai || $selesai)
                        {{ $mulai ?: '-' }}@if($selesai) s/d {{ $selesai }}@endif
                      @else
                        -
                      @endif
                    </td>

                    {{-- STATUS --}}
                    <td>
                      @php
                        $raw = $row->status_proses;
                        $label = $raw ? ucwords(strtolower(str_replace('_', ' ', $raw))) : '-';
                      @endphp
                      {{ $label }}
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
        </form>

        <div class="card-body">
          <div class="d-flex justify-content-center">
            {{ $rows->withQueryString()->links('pagination::bootstrap-4') }}
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const btnDeleteMode     = document.getElementById('btnDeleteMode');
  const btnDeleteSelected = document.getElementById('btnDeleteSelected');
  const btnCancelDelete   = document.getElementById('btnCancelDelete');
  const deleteHint        = document.getElementById('deleteHint');

  const checkAll = document.getElementById('checkAll');
  const checks   = () => document.querySelectorAll('.row-check');

  let deleteMode = false;

  function setDeleteMode(on) {
    deleteMode = on;

    // toggle tombol
    btnDeleteMode.classList.toggle('d-none', on);
    btnDeleteSelected.classList.toggle('d-none', !on);
    btnCancelDelete.classList.toggle('d-none', !on);
    deleteHint.classList.toggle('d-none', !on);

    // toggle checkbox header
    checkAll.classList.toggle('d-none', !on);
    checkAll.checked = false;

    // toggle checkbox per row
    checks().forEach(ch => {
      ch.classList.toggle('d-none', !on);
      ch.checked = false;
    });
  }

  btnDeleteMode.addEventListener('click', function () {
    setDeleteMode(true);
  });

  btnCancelDelete.addEventListener('click', function () {
    setDeleteMode(false);
  });

  // check all
  checkAll.addEventListener('change', function () {
    checks().forEach(ch => ch.checked = checkAll.checked);
  });

  // submit bulk delete
  btnDeleteSelected.addEventListener('click', function () {
    const selected = Array.from(checks()).filter(ch => ch.checked);

    if (selected.length === 0) {
      alert('Pilih minimal 1 data untuk dihapus.');
      return;
    }

    if (!confirm('Yakin hapus ' + selected.length + ' data yang dipilih?')) {
      return;
    }

    document.getElementById('bulkDeleteForm').submit();
  });

  // default: off
  setDeleteMode(false);
});
</script>
@endsection
