@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-0">Job Sheet </h4>
            <p class="mb-0 text-muted">
              Isi data Job Sheet.
            </p>
          </div>

          <a href="{{ route('qc-jobsheet.index') }}" class="btn btn-sm btn-outline-secondary">
            &laquo; Kembali
          </a>
        </div>

        @if(session('ok'))
          <div class="alert alert-success m-2">{{ session('ok') }}</div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger m-2">
            {{ $errors->first() }}
          </div>
        @endif

        {{-- Info dari Review (jika ada HOLD / REJECT) --}}
        @php
          $statusReview = $batch->status_review ?? 'pending';
        @endphp

        @if(in_array($statusReview, ['hold','rejected'], true))
          <div class="alert alert-warning m-2">
            <strong>Status Review:</strong> {{ strtoupper($statusReview) }}<br>
            @if($batch->catatan_review)
              <strong>Keterangan:</strong> {{ $batch->catatan_review }}
            @else
              Dokumen diminta diperiksa kembali oleh QA.
            @endif
          </div>
        @endif

        <div class="card-body">
          <form action="{{ route('qc-jobsheet.update', $batch->id) }}" method="POST">
            @csrf

            <div class="mb-1">
              <label class="form-label">Produk</label>
              <input type="text" class="form-control"
                     value="{{ $batch->nama_produk }}" disabled>
            </div>

            <div class="mb-1">
              <label class="form-label">Kode Batch</label>
              <input type="text" class="form-control"
                     value="{{ $batch->kode_batch }}" disabled>
            </div>

            <div class="mb-1">
              <label class="form-label">Tanggal Konfirmasi Produksi</label>
              <input type="date"
                     name="tgl_konfirmasi_produksi"
                     class="form-control"
                     value="{{ $jobsheet->tgl_konfirmasi_produksi }}">
            </div>

            <div class="mb-1">
              <label class="form-label">Tanggal Terima Job Sheet</label>
              <input type="date"
                     name="tgl_terima_jobsheet"
                     class="form-control"
                     value="{{ $jobsheet->tgl_terima_jobsheet }}">
            </div>

            <div class="mt-2 d-flex justify-content-between">
              <a href="{{ route('qc-jobsheet.index') }}"
                 class="btn btn-outline-secondary">
                Kembali
              </a>
              <button class="btn btn-primary">
                Simpan Job Sheet
              </button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
