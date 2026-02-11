@extends('layouts.app')

@section('content')
<section id="multiple-column-form">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Edit Akun QC</h4>
        </div>
        <div class="card-body">
          <form class="form" action="{{ url('/show-qc/'.$qc->id) }}" method="post" enctype="multipart/form-data">
            @csrf
            @method('put')
            <div class="row">
              <div class="col-md-6 col-12">
                <div class="mb-1">
                  <label class="form-label">Nama</label>
                  <input
                    type="text"
                    class="form-control"
                    name="name"
                    value="{{ $qc->name }}"
                    placeholder="Masukkan Nama Baru"
                  />
                </div>
                @error('name') <div class="text-danger mt-1">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6 col-12">
                <div class="mb-1">
                  <label class="form-label">Email</label>
                  <input
                    type="email"
                    class="form-control"
                    name="email"
                    value="{{ $qc->email }}"
                    placeholder="Masukkan Email Baru"
                  />
                </div>
                @error('email') <div class="text-danger mt-1">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6 col-12">
                <div class="mb-1">
                  <label class="form-label">Password (opsional)</label>
                  <input
                    type="password"
                    class="form-control"
                    name="password"
                    placeholder="Masukkan Password Baru (jika ingin diganti)"
                  />
                </div>
                @error('password') <div class="text-danger mt-1">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6 col-12">
                <div class="mb-1">
                  <label class="form-label">Jabatan QC</label>
                  <select name="qc_level" class="form-select">
                    <option value="QC"      {{ $qc->qc_level === 'QC' ? 'selected' : '' }}>QC</option>
                    <option value="SPV"     {{ $qc->qc_level === 'SPV' ? 'selected' : '' }}>QC Supervisor</option>
                    <option value="MANAGER" {{ $qc->qc_level === 'MANAGER' ? 'selected' : '' }}>QC Manager</option>
                  </select>
                </div>
                @error('qc_level') <div class="text-danger mt-1">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6 col-12">
                <div class="mb-1">
                  <label class="form-label">Barcode / QR Tanda Tangan</label>
                  <input
                    type="file"
                    class="form-control"
                    name="qr_signature"
                    accept="image/*"
                  />
                  <small class="text-muted">
                    Upload ulang jika ingin mengganti barcode. Jika dikosongkan, barcode lama tetap dipakai.
                  </small>
                  @if($qc->qc_signature_path)
                    <div class="mt-1">
                      <span class="d-block mb-25">Preview saat ini:</span>
                      <img src="{{ asset('storage/'.$qc->qc_signature_path) }}" alt="QR TTD" style="max-height:120px;">
                    </div>
                  @endif
                </div>
                @error('qr_signature') <div class="text-danger mt-1">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary me-1">Submit</button>
                <button type="reset" class="btn btn-outline-secondary">Reset</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
