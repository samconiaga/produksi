@extends('layouts.app')

@section('content')
<section id="multiple-column-form">
  <div class="row">
    <div class="col-12">
      <div class="card">

        <div class="card-header">
          <h4 class="card-title">Edit Akun Produksi</h4>
        </div>

        <div class="card-body">
          {{-- 🔥 FIX ROUTE DI SINI --}}
          <form action="{{ url('/show-produksi/'.$produksi->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">

              <div class="col-md-6 col-12 mb-1">
                <label>Nama</label>
                <input
                  type="text"
                  name="name"
                  class="form-control"
                  value="{{ old('name', $produksi->name) }}"
                  required
                >
              </div>

              <div class="col-md-6 col-12 mb-1">
                <label>Email</label>
                <input
                  type="email"
                  name="email"
                  class="form-control"
                  value="{{ old('email', $produksi->email) }}"
                  required
                >
              </div>

              <div class="col-md-6 col-12 mb-1">
                <label>Password (Opsional)</label>
                <input
                  type="password"
                  name="password"
                  class="form-control"
                  placeholder="Kosongkan jika tidak diubah"
                >
              </div>

              <div class="col-md-6 col-12 mb-1">
                <label>Level Produksi</label>
                <select name="produksi_role" class="form-control" required>
                  <option value="ADMIN" {{ $produksi->produksi_role=='ADMIN' ? 'selected' : '' }}>
                    Admin
                  </option>
                  <option value="SPV" {{ $produksi->produksi_role=='SPV' ? 'selected' : '' }}>
                    SPV
                  </option>
                  <option value="OPERATOR" {{ $produksi->produksi_role=='OPERATOR' ? 'selected' : '' }}>
                    Operator
                  </option>
                </select>
              </div>

              <div class="col-12 text-center mt-2">
                <button type="submit" class="btn btn-primary me-1">
                  Update
                </button>
                <a href="{{ url('/show-produksi') }}" class="btn btn-outline-secondary">
                  Batal
                </a>
              </div>

            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
