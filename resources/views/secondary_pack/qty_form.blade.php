@extends('layouts.app')

@section('content')
<section class="app-user">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">

        <div class="card-header">
          <h4 class="card-title mb-0">Input Qty Batch</h4>
          <p class="mb-0 text-muted">
            Isi jumlah batch setelah proses <strong>Secondary Pack</strong> selesai.
            Wadah otomatis dari <strong>Master Produk</strong>.
          </p>
        </div>

        @if(session('success'))
          <div class="alert alert-success m-2 py-1 mb-0">{{ session('success') }}</div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger m-2 py-1 mb-0">{{ $errors->first() }}</div>
        @endif

        <div class="card-body">
          <form action="{{ route('secondary-pack.qty.save', $batch->id) }}" method="POST">
            @csrf

            <div class="mb-1">
              <label class="form-label">Produk</label>
              <input type="text" class="form-control"
                     value="{{ $batch->produksi->nama_produk ?? $batch->nama_produk }}" disabled>
            </div>

            <div class="mb-1">
              <label class="form-label">Kode Batch</label>
              <input type="text" class="form-control"
                     value="{{ $batch->kode_batch ?? $batch->no_batch }}" disabled>
            </div>

            <div class="mb-1">
              <label class="form-label">WO Date</label>
              <input type="text" class="form-control"
                     value="{{ optional($batch->wo_date)->format('d-m-Y') }}" disabled>
            </div>

            <div class="mb-1">
              <label class="form-label">Secondary Pack Selesai</label>
              <input type="text" class="form-control"
                     value="{{ optional($batch->tgl_secondary_pack_1)->format('d-m-Y H:i') }}" disabled>
            </div>

            <div class="mb-1">
              <label class="form-label">Wadah (otomatis dari Master)</label>
              <input type="text" class="form-control"
                     value="{{ $wadah !== '' ? $wadah : '-' }}" disabled>
            </div>

            <div class="mb-1">
              <label class="form-label">Qty Batch (unit)</label>
              <input type="number"
                     name="qty_batch"
                     class="form-control"
                     min="0"
                     step="1"
                     value="{{ old('qty_batch', $batch->qty_batch ?? '') }}"
                     required>
              <div class="small text-muted mt-1">
                Unit mengikuti wadah: <strong>{{ $wadah !== '' ? $wadah : '-' }}</strong>
              </div>
            </div>

            <div class="mt-2 d-flex justify-content-between">
              <a href="{{ route('secondary-pack.index') }}" class="btn btn-outline-secondary">
                Kembali
              </a>
              <button type="submit" class="btn btn-primary">
                Simpan Qty Batch
              </button>
            </div>

          </form>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection