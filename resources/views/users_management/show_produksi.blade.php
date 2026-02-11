@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- HEADER --}}
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title">Daftar Akun Produksi</h4>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inlineForm">
            Tambah Akun Produksi
          </button>
        </div>

        {{-- MODAL TAMBAH PRODUKSI --}}
        <div class="modal fade text-start" id="inlineForm" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

              <div class="modal-header">
                <h4 class="modal-title">Tambahkan Akun Produksi</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>

              <form action="{{ url('/show-produksi') }}" method="POST">
                @csrf
                <div class="modal-body">

                  <label>Nama</label>
                  <input type="text" name="name" class="form-control mb-1" placeholder="Masukkan Nama" required>
                  @error('name') <small class="text-danger">{{ $message }}</small> @enderror

                  <label>Email</label>
                  <input type="email" name="email" class="form-control mb-1" placeholder="Masukkan Email" required>
                  @error('email') <small class="text-danger">{{ $message }}</small> @enderror

                  <label>Password</label>
                  <input type="password" name="password" class="form-control mb-1" placeholder="Masukkan Password" required>
                  @error('password') <small class="text-danger">{{ $message }}</small> @enderror

                  {{-- 🔥 ROLE PRODUKSI --}}
                  <label>Role Produksi</label>
                  <select name="produksi_role" class="form-control" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="ADMIN">Admin</option>
                    <option value="SPV">SPV</option>
                    <option value="OPERATOR">Operator</option>
                  </select>
                  @error('produksi_role') <small class="text-danger">{{ $message }}</small> @enderror

                </div>

                <div class="modal-footer">
                  <button type="submit" class="btn btn-primary">Daftar</button>
                </div>
              </form>

            </div>
          </div>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <th>Level Produksi</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>
              @forelse ($users as $user)
                <tr>
                  <td>{{ $user->name }}</td>
                  <td>{{ $user->email }}</td>

                  <td>
                    <span class="badge rounded-pill badge-light-primary">
                      {{ $user->role }}
                    </span>
                  </td>

                  <td>
                    <span class="badge rounded-pill
                      @if($user->produksi_role=='ADMIN') badge-light-danger
                      @elseif($user->produksi_role=='SPV') badge-light-warning
                      @else badge-light-success
                      @endif">
                      {{ $user->produksi_role }}
                    </span>
                  </td>

                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                        <i data-feather="more-vertical"></i>
                      </button>
                      <div class="dropdown-menu">

                        <a class="dropdown-item" href="{{ route('edit-produksi', $user->id) }}">
                          <i data-feather="edit-2" class="me-50"></i>
                          <span>Edit</span>
                        </a>

                        <form action="{{ route('delete-produksi', $user->id) }}"
                              method="POST"
                              onsubmit="return confirm('Hapus akun Produksi ini?')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="dropdown-item">
                            <i data-feather="trash" class="me-50"></i>
                            <span>Delete</span>
                          </button>
                        </form>

                      </div>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted">
                    Belum ada akun Produksi
                  </td>
                </tr>
              @endforelse
            </tbody>

          </table>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
