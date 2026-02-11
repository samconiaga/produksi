@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- HEADER --}}
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title mb-0">Daftar Akun QC</h4>
          <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inlineForm">
              Tambah Akun QC
            </button>
          </div>
        </div>

        {{-- FLASH MESSAGE --}}
        <div class="card-body pt-1 pb-0">
          @if(session('success'))
            <div class="alert alert-success py-1 mb-1">{{ session('success') }}</div>
          @endif

          @if($errors->any())
            <div class="alert alert-danger py-1 mb-1">{{ $errors->first() }}</div>
          @endif
        </div>

        {{-- Modal Tambah QC --}}
        <div class="modal fade text-start" id="inlineForm" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

              <div class="modal-header">
                <h4 class="modal-title">Tambahkan Akun QC</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>

              <form action="{{ url('/show-qc') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">

                  <label>Nama:</label>
                  <div class="mb-1">
                    <input type="text" name="name" class="form-control" />
                  </div>

                  <label>Email:</label>
                  <div class="mb-1">
                    <input type="email" name="email" class="form-control" />
                  </div>

                  <label>Password:</label>
                  <div class="mb-1">
                    <input type="password" name="password" class="form-control" />
                  </div>

                  <label>Jabatan QC:</label>
                  <div class="mb-1">
                    <select name="qc_level" class="form-select">
                      <option value="QC">QC</option>
                      <option value="SPV">QC Supervisor</option>
                      <option value="MANAGER">QC Manager</option>
                    </select>
                  </div>

                  <label>Barcode / QR (opsional):</label>
                  <div class="mb-1">
                    <input type="file" name="qr_signature" class="form-control" accept="image/*" />
                  </div>

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
                <th>Level QC</th>
                <th>Barcode TTD</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>

              @forelse($users as $user)
              <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>

                <td>
                  <span class="badge rounded-pill badge-light-primary">{{ $user->role }}</span>
                </td>

                <td>
                  @if($user->qc_level === 'MANAGER')
                    <span class="badge rounded-pill badge-light-success">QC Manager</span>
                  @elseif($user->qc_level === 'SPV')
                    <span class="badge rounded-pill badge-light-info">QC Supervisor</span>
                  @elseif($user->qc_level === 'QC')
                    <span class="badge rounded-pill badge-light-secondary">QC</span>
                  @else
                    <span class="badge rounded-pill badge-light-dark">-</span>
                  @endif
                </td>

                {{-- BUTTON Lihat Barcode → buka modal --}}
                <td>
                  @if($user->qc_signature_path)
                    <button class="badge rounded-pill badge-light-primary border-0"
                            data-bs-toggle="modal"
                            data-bs-target="#barcodeModal-{{ $user->id }}">
                      Lihat Barcode
                    </button>
                  @else
                    <span class="badge rounded-pill badge-light-secondary">Belum ada</span>
                  @endif
                </td>

                <td>
                  <div class="dropdown">
                    <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                      <i data-feather="more-vertical"></i>
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="{{ route('edit-qc', $user->id) }}">
                        <i data-feather="edit-2" class="me-50"></i> Edit
                      </a>

                      <form action="{{ route('delete-qc', $user->id) }}" method="POST"
                            onsubmit="return confirm('Hapus akun QC ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="dropdown-item">
                          <i data-feather="trash" class="me-50"></i> Delete
                        </button>
                      </form>
                    </div>
                  </div>
                </td>
              </tr>

              {{-- Modal Barcode --}}
              @if($user->qc_signature_path)
              <div class="modal fade" id="barcodeModal-{{ $user->id }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                  <div class="modal-content">

                    <div class="modal-header">
                      <h5 class="modal-title">Barcode - {{ $user->name }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body text-center">
                      <img src="{{ asset('storage/'.$user->qc_signature_path) }}"
                           class="img-fluid mb-1"
                           alt="Barcode {{ $user->name }}">
                      <small class="text-muted">
                        @if($user->qc_level === 'MANAGER')
                          QC Manager
                        @elseif($user->qc_level === 'SPV')
                          QC Supervisor
                        @elseif($user->qc_level === 'QC')
                          QC
                        @else
                          -
                        @endif
                      </small>
                    </div>

                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    </div>

                  </div>
                </div>
              </div>
              @endif

              @empty
              <tr>
                <td colspan="6" class="text-center text-muted">Belum ada akun QC</td>
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
