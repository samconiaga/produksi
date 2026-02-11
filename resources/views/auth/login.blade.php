{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.auth')

@section('content')
<div class="app-content content">
  {{-- content-overlay ada tapi CSS layout utama menonaktifkannya (tidak memblok) --}}
  <div class="content-overlay"></div>
  <div class="header-navbar-shadow"></div>

  <div class="content-wrapper">
    <div class="content-header row"></div>

    <div class="content-body">
      <div class="auth-wrapper auth-v2">
        <div class="auth-inner row m-0">

          {{-- BRAND LOGO --}}
          <a class="brand-logo d-flex align-items-center text-decoration-none mb-3" href="#">
            <img src="{{ asset('app-assets/images/logo/logo.png') }}"
                 alt="Logo"
                 class="me-2"
                 style="height:100px; width:auto;">
            <h2 class="brand-text text-danger mb-0">PT. Samco Farma</h2>
          </a>

          {{-- LEFT IMAGE (hidden on small) --}}
          <div class="d-none d-lg-flex col-lg-8 align-items-center p-5">
            <div class="w-100 d-lg-flex align-items-center justify-content-center px-5">
              <img
                class="img-fluid"
                src="{{ asset('app-assets/images/illustrator/bahanbaku.png') }}"
                alt="Illustration"
              />
            </div>
          </div>

          {{-- RIGHT FORM --}}
          <div class="d-flex col-lg-4 align-items-center auth-bg px-2 p-lg-5">
            <div class="col-12 col-sm-8 col-md-6 col-lg-12 px-xl-2 mx-auto">

              <h2 class="card-title fw-bold mb-1 text-danger">Selamat Datang</h2>
              <p class="card-text mb-2">Masukkan email dan password Anda</p>

              {{-- ERROR GLOBAL --}}
              @if ($errors->any())
                <div class="alert alert-danger">
                  <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                  </ul>
                </div>
              @endif

              <form class="auth-register-form mt-2" action="{{ route('login') }}" method="POST" autocomplete="off">
                @csrf

                {{-- EMAIL --}}
                <div class="mb-1">
                  <label class="form-label" for="email">Email</label>
                  <input
                    class="form-control @error('email') is-invalid @enderror"
                    id="email"
                    type="email"
                    name="email"
                    placeholder="asep@example.com"
                    value="{{ old('email') }}"
                    required
                    autofocus
                  >
                  @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                {{-- PASSWORD --}}
                <div class="mb-2">
                  <label class="form-label" for="password">Password</label>
                  <div class="input-group input-group-merge form-password-toggle">
                    <input
                      class="form-control form-control-merge @error('password') is-invalid @enderror"
                      id="password"
                      type="password"
                      name="password"
                      placeholder="••••••••"
                      required
                      autocomplete="current-password"
                    >
                    <span class="input-group-text cursor-pointer" id="togglePassword" role="button" title="Tampilkan / sembunyikan password">
                      <i data-feather="eye"></i>
                    </span>
                  </div>
                  @error('password')
                    <div class="text-danger mt-1">{{ $message }}</div>
                  @enderror
                </div>

                {{-- REMEMBER ME --}}
                <div class="mb-2 form-check">
                  <input class="form-check-input" type="checkbox" id="remember" name="remember">
                  <label class="form-check-label" for="remember">
                    Ingat saya
                  </label>
                </div>

                {{-- SUBMIT --}}
                <button class="btn btn-danger w-100 mt-1" type="submit">
                  Login
                </button>
              </form>

            </div>
          </div>
          {{-- /RIGHT FORM --}}

        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (window.feather) feather.replace();

    const toggle = document.getElementById('togglePassword');
    const input  = document.getElementById('password');

    if (toggle && input) {
      toggle.addEventListener('click', function () {
        input.type = input.type === 'password' ? 'text' : 'password';
      });
    }
  });
</script>
@endpush
@endsection