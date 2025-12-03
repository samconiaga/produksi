@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">

        {{-- HEADER --}}
        <div class="card-header">
          <h4 class="card-title mb-0">Input Qty Batch</h4>
          <p class="mb-0 text-muted">
            Isi jumlah batch setelah proses <strong>Primary Pack</strong> dikonfirmasi.
          </p>
        </div>

        {{-- FLASH MESSAGE --}}
        @if(session('ok'))
          <div class="alert alert-success m-2">{{ session('ok') }}</div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger m-2">
            {{ $errors->first() }}
          </div>
        @endif

        <div class="card-body">
          {{-- Ubah route ini sesuai route yang kamu pakai di web.php untuk qty primary --}}
          <form action="{{ route('primary-pack.qty.save', $batch->id) }}" method="POST">
            @csrf

            {{-- INFO BATCH --}}
            <div class="mb-1">
              <label class="form-label">Produk</label>
              <input type="text"
                     class="form-control"
                     value="{{ $batch->produksi->nama_produk ?? $batch->nama_produk }}"
                     disabled>
            </div>

            <div class="mb-1">
              <label class="form-label">Kode Batch</label>
              <input type="text"
                     class="form-control"
                     value="{{ $batch->kode_batch }}"
                     disabled>
            </div>

            <div class="mb-1">
              <label class="form-label">WO Date</label>
              <input type="text"
                     class="form-control"
                     value="{{ optional($batch->wo_date)->format('d-m-Y') }}"
                     disabled>
            </div>

            <div class="mb-1">
              <label class="form-label">Primary Pack Selesai</label>
              <input type="text"
                     class="form-control"
                     value="{{ optional($batch->tgl_primary_pack)->format('d-m-Y') }}"
                     disabled>
            </div>

            {{-- INPUT QTY --}}
            <div class="mb-1">
              <label class="form-label">Qty Batch (unit)</label>
              <input type="number"
                     name="qty_batch"
                     class="form-control"
                     min="0"
                     step="1"
                     value="{{ old('qty_batch', $batch->qty_batch ?? '') }}"
                     required>
            </div>

            {{-- ACTION BUTTONS --}}
            <div class="mt-2 d-flex justify-content-between">
              <a href="{{ route('primary-pack.index') }}" class="btn btn-outline-secondary">
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
