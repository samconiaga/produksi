@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Edit Jadwal Produksi Batch</h4>
        </div>

        <div class="card-body">
          <form action="{{ route('update-permintaan', $batch->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-1">
              <div class="col-md-4">
                <label class="form-label">Produksi</label>
                <input type="text" class="form-control"
                       value="{{ $batch->produksi->nama_produk ?? $batch->nama_produk }}" disabled>
              </div>
              <div class="col-md-2">
                <label class="form-label">No Batch</label>
                <input type="text" class="form-control" value="{{ $batch->no_batch }}" disabled>
              </div>
              <div class="col-md-2">
                <label class="form-label">Kode Batch</label>
                <input type="text" class="form-control" value="{{ $batch->kode_batch }}" disabled>
              </div>
            </div>

            <hr>

            <div class="row g-1">
              <div class="col-md-3">
                <label class="form-label">WO Date</label>
                <input type="date" name="wo_date" class="form-control"
                       value="{{ optional($batch->wo_date)->format('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Expected Date</label>
                <input type="date" name="expected_date" class="form-control"
                       value="{{ optional($batch->expected_date)->format('Y-m-d') }}">
              </div>
            </div>

            <hr>

            {{-- urutan proses sama dengan tabel WO --}}
            <div class="row g-1">
              <div class="col-md-3">
                <label class="form-label">Weighing</label>
                <input type="date" name="tgl_weighing" class="form-control"
                       value="{{ optional($batch->tgl_weighing)->format('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Mixing (Mulai)</label>
                <input type="date" name="tgl_mulai_mixing" class="form-control"
                       value="{{ optional($batch->tgl_mulai_mixing)->format('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Mixing (Selesai)</label>
                <input type="date" name="tgl_mixing" class="form-control"
                       value="{{ optional($batch->tgl_mixing)->format('Y-m-d') }}">
              </div>
            </div>

            <div class="row g-1 mt-1">
              <div class="col-md-3">
                <label class="form-label">Tgl Rilis Antara Granul</label>
                <input type="date" name="tgl_rilis_granul" class="form-control"
                       value="{{ optional($batch->tgl_rilis_granul)->format('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Capsule Filling (Mulai)</label>
                <input type="date" name="tgl_mulai_capsule_filling" class="form-control"
                       value="{{ optional($batch->tgl_mulai_capsule_filling)->format('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Capsule Filling (Selesai)</label>
                <input type="date" name="tgl_capsule_filling" class="form-control"
                       value="{{ optional($batch->tgl_capsule_filling)->format('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Tableting (Mulai)</label>
                <input type="date" name="tgl_mulai_tableting" class="form-control"
                       value="{{ optional($batch->tgl_mulai_tableting)->format('Y-m-d') }}">
              </div>
            </div>

            <div class="row g-1 mt-1">
              <div class="col-md-3">
                <label class="form-label">Tableting (Selesai)</label>
                <input type="date" name="tgl_tableting" class="form-control"
                       value="{{ optional($batch->tgl_tableting)->format('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Tgl Rilis Antara Tablet</label>
                <input type="date" name="tgl_rilis_tablet" class="form-control"
                       value="{{ optional($batch->tgl_rilis_tablet)->format('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Coating (Mulai)</label>
                <input type="date" name="tgl_mulai_coating" class="form-control"
                       value="{{ optional($batch->tgl_mulai_coating)->format('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Coating (Selesai)</label>
                <input type="date" name="tgl_coating" class="form-control"
                       value="{{ optional($batch->tgl_coating)->format('Y-m-d') }}">
              </div>
            </div>

            <div class="row g-1 mt-1">
              <div class="col-md-3">
                <label class="form-label">Tgl Rilis Ruahan</label>
                <input type="date" name="tgl_rilis_ruahan" class="form-control"
                       value="{{ optional($batch->tgl_rilis_ruahan)->format('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Primary Pack (Mulai)</label>
                <input type="date" name="tgl_mulai_primary_pack" class="form-control"
                       value="{{ optional($batch->tgl_mulai_primary_pack)->format('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Primary Pack (Selesai)</label>
                <input type="date" name="tgl_primary_pack" class="form-control"
                       value="{{ optional($batch->tgl_primary_pack)->format('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Tgl Rilis Ruahan Akhir</label>
                <input type="date" name="tgl_rilis_ruahan_akhir" class="form-control"
                       value="{{ optional($batch->tgl_rilis_ruahan_akhir)->format('Y-m-d') }}">
              </div>
            </div>

            <div class="row g-1 mt-1">
              <div class="col-md-3">
                <label class="form-label">Secondary Pack (Mulai)</label>
                <input type="date" name="tgl_mulai_secondary_pack_1" class="form-control"
                       value="{{ optional($batch->tgl_mulai_secondary_pack_1)->format('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Secondary Pack (Selesai)</label>
                <input type="date" name="tgl_secondary_pack_1" class="form-control"
                       value="{{ optional($batch->tgl_secondary_pack_1)->format('Y-m-d') }}">
              </div>
            </div>

            <div class="row g-1 mt-1">
              <div class="col-md-2">
                <label class="form-label">Hari Kerja</label>
                <input type="number" name="hari_kerja" class="form-control"
                       value="{{ $batch->hari_kerja }}">
              </div>
              <div class="col-md-4">
                <label class="form-label">Status Proses</label>
                <input type="text" name="status_proses" class="form-control"
                       value="{{ $batch->status_proses }}">
              </div>
            </div>

            <div class="mt-2">
              <button class="btn btn-primary">Simpan</button>
              <a href="{{ route('show-permintaan') }}" class="btn btn-outline-secondary">Kembali</a>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
