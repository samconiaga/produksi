@php 
    $user   = auth()->user();
    $avatar = strtoupper(substr($user->name ?? 'U', 0, 2));
    $role   = strtolower($user->role ?? '');

    $isAdmin     = in_array($role, ['admin','administrator','superadmin'], true);
    $isProduksi  = ($role === 'produksi');
    $isPPIC      = ($role === 'ppic');
    $isQA        = ($role === 'qa');
    $isQC        = ($role === 'qc');

    $brandHome = route('dashboard');

   $isProduksiMenuActive = request()->routeIs('show-permintaan')
    || request()->routeIs('mixing.*')
    || request()->routeIs('capsule-filling.*')
    || request()->routeIs('tableting.*')
    || request()->routeIs('coating.*')
    || request()->routeIs('primary-pack.*')
    || request()->routeIs('secondary-pack.*')
    || request()->routeIs('qc-granul.*')
    || request()->routeIs('qc-tablet.*')
    || request()->routeIs('qc-ruahan.*')
    || request()->routeIs('qc-ruahan-akhir.*');


    $isAfterPackMenuActive = request()->is('qty-batch*')
        || request()->routeIs('qc-jobsheet.*')
        || request()->routeIs('sampling.*')
        || request()->routeIs('coa.*')
        || request()->routeIs('review.*')
        || request()->routeIs('release.*');
@endphp

<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui" />
  <title>PT. SAMCO Farma</title>
  <link rel="shortcut icon" type="image/x-icon" href="{{ asset('app-assets/images/logo/logo.png') }}" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">

  {{-- Vendor CSS --}}
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/vendors.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/charts/apexcharts.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/extensions/toastr.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/pickers/pickadate/pickadate.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/pickers/flatpickr/flatpickr.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/dataTables.bootstrap5.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/responsive.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/buttons.bootstrap5.min.css') }}">

  {{-- Theme --}}
  <link rel="stylesheet" href="{{ asset('app-assets/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/bootstrap-extended.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/colors.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/components.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/themes/dark-layout.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/themes/bordered-layout.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/themes/semi-dark-layout.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/forms/select/select2.min.css') }}">

  {{-- Page --}}
  <link rel="stylesheet" href="{{ asset('app-assets/css/core/menu/menu-types/vertical-menu.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/pages/dashboard-ecommerce.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/plugins/charts/chart-apex.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/plugins/extensions/ext-component-toastr.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/plugins/forms/form-validation.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/pages/app-user.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/plugins/forms/pickers/form-flat-pickr.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/plugins/forms/pickers/form-pickadate.min.css') }}">

  {{-- Custom --}}
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

  {{-- ================== CUSTOM (SIDEBAR SIMPLE + LIGHT/DARK + SEARCH) ================== --}}
  <style>
    :root{
      --samco-primary: #dc3545;

      /* light */
      --samco-bg: #f3f4f6;
      --samco-header-bg: #ffffff;
      --samco-header-border: #e5e7eb;
      --samco-sidebar-bg-soft: #ffffff;
      --samco-sidebar-header-bg: #f9fafb;
      --samco-sidebar-border: #e5e7eb;
      --samco-sidebar-text: #111827;
      --samco-sidebar-muted: #6b7280;
      --samco-text-main: #111827;
      --samco-text-muted: #6b7280;
    }

    body.samco-dark{
      --samco-bg: #020617;
      --samco-header-bg: #020617;
      --samco-header-border: #1f2937;
      --samco-sidebar-bg-soft: #020617;
      --samco-sidebar-header-bg: #020617;
      --samco-sidebar-border: #1f2937;
      --samco-sidebar-text: #e5e7eb;
      --samco-sidebar-muted: #9ca3af;
      --samco-text-main: #e5e7eb;
      --samco-text-muted: #9ca3af;
    }

    html, body{
      font-family: 'Montserrat', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      color: var(--samco-text-main);
    }

    body.vertical-layout{
      background: var(--samco-bg);
    }

    /* HEADER */
    .header-navbar{
      background:var(--samco-header-bg) !important;
      border-bottom:1px solid var(--samco-header-border);
      box-shadow:0 1px 4px rgba(15,23,42,.04);
      height:60px;
      position:fixed !important;
      top:0 !important;
      left:0;
      right:0;
      z-index:1040 !important;
    }
    .header-navbar .navbar-container{
      height:60px;
      padding:0 1.25rem;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:1rem;
    }

    .top-left-wrapper{
      display:flex;
      align-items:center;
      gap:.9rem;
      min-width:220px;
    }

    .samco-menu-toggle{
      display:flex;
      align-items:center;
      justify-content:center;
      width:34px;
      height:34px;
      border-radius:8px;
      border:1px solid #e5e7eb;
      background:#f9fafb;
      color:#4b5563 !important;
    }
    body.samco-dark .samco-menu-toggle{
      background:#020617;
      border-color:#1f2937;
      color:#e5e7eb !important;
    }

    .app-brand-wrapper{
      display:flex;
      align-items:center;
      gap:.7rem;
    }
    .app-brand-logo{
      width:34px;
      height:34px;
      border-radius:10px;
      background:linear-gradient(135deg,var(--samco-primary),#ff6b81);
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .app-brand-logo img{
      max-height:20px;
      width:auto;
    }
    .app-brand-texts{
      display:flex;
      flex-direction:column;
    }
    .app-brand-title{
      font-size:.9rem;
      font-weight:600;
      letter-spacing:.12em;
      text-transform:uppercase;
      color:var(--samco-text-main);
    }
    .app-brand-subtitle{
      font-size:.72rem;
      color:var(--samco-text-muted);
    }

    .top-right-wrapper{
      display:flex;
      align-items:center;
      gap:.65rem;
    }

    .header-navbar .dropdown-user-link .user-nav span{
      display:block;
      line-height:1.1;
    }
    .header-navbar .dropdown-user-link .user-name{
      font-size:.84rem;
      font-weight:600;
      color:var(--samco-text-main);
    }
    .header-navbar .dropdown-user-link .user-status{
      font-size:.7rem;
      text-transform:uppercase;
      letter-spacing:.08em;
      color:var(--samco-text-muted);
    }
    .header-navbar .avatar.bg-light-primary{
      background:#fee2e2 !important;
      color:var(--samco-primary);
    }

    /* ICON BUTTONS (THEME + NOTIF) */
    .top-icon-btn{
      position:relative;
      width:34px;
      height:34px;
      border-radius:999px;
      border:1px solid #e5e7eb;
      display:flex;
      align-items:center;
      justify-content:center;
      background:#ffffff;
      cursor:pointer;
      color:#4b5563 !important;
    }
    body.samco-dark .top-icon-btn{
      background:#020617;
      border-color:#1f2937;
      color:#e5e7eb !important;
    }
    .top-icon-badge{
      position:absolute;
      top:3px;
      right:3px;
      width:15px;
      height:15px;
      border-radius:999px;
      background:var(--samco-primary);
      color:#fff;
      font-size:.6rem;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    .theme-icon-sun{ display:none; }
    body.samco-dark .theme-icon-moon{ display:none; }
    body.samco-dark .theme-icon-sun{ display:inline-block; }

    /* HEADER SEARCH */
    .top-search{
      position:relative;
      width:260px;
      max-width:100%;
      display:flex;
      align-items:center;
    }
    .top-search-icon{
      position:absolute;
      left:0.65rem;
      top:50%;
      transform:translateY(-50%);
      pointer-events:none;
    }
    .top-search-input{
      width:100%;
      padding:.35rem .6rem .35rem 2.1rem;
      border-radius:999px;
      border:1px solid #e5e7eb;
      background:#f9fafb;
      font-size:.78rem;
      outline:none;
      color:var(--samco-text-main);
    }
    .top-search-input::placeholder{
      color:var(--samco-text-muted);
      font-size:.78rem;
    }
    body.samco-dark .top-search-input{
      background:#020617;
      border-color:#1f2937;
      color:var(--samco-text-main);
    }

    .top-search-result{
      position:absolute;
      left:0;
      right:0;
      top:100%;
      margin-top:4px;
      background:var(--samco-header-bg);
      border:1px solid #e5e7eb;
      border-radius:.75rem;
      box-shadow:0 18px 45px rgba(15,23,42,.18);
      padding:.35rem 0;
      display:none;
      z-index:2000;
    }
    body.samco-dark .top-search-result{
      border-color:#1f2937;
      background:#020617;
    }
    .top-search-result.show{
      display:block;
    }
    .top-search-item{
      width:100%;
      border:none;
      background:transparent;
      padding:.35rem .8rem;
      font-size:.78rem;
      text-align:left;
      color:var(--samco-text-main);
      cursor:pointer;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:.35rem;
    }
    .top-search-item span.label{
      flex:1;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .top-search-item span.badge{
      font-size:.68rem;
      text-transform:uppercase;
      letter-spacing:.06em;
      color:var(--samco-text-muted);
    }
    .top-search-item:hover{
      background:rgba(148,163,184,.15);
    }

    /* SIDEBAR */
    .main-menu{
      background:var(--samco-sidebar-bg-soft);
      border-right:1px solid var(--samco-sidebar-border);
      box-shadow:2px 0 14px rgba(0,0,0,.08);
      top:60px !important;
      width:260px !important;
      transform:none !important;
    }
    .main-menu .navbar-header{
      padding:.85rem 1.2rem .75rem 1.2rem;
      border-bottom:1px solid var(--samco-sidebar-border);
      background:var(--samco-sidebar-header-bg);
    }
    .main-menu .navbar-brand{
      display:flex;
      align-items:center;
      gap:.55rem;
    }
    .main-menu .brand-logo img{
      height:24px;
      width:auto;
    }
    .main-menu .navbar-header .navbar-brand .brand-text{
      font-size:.9rem;
      font-weight:600;
      letter-spacing:.14em;
      text-transform:uppercase;
      color:var(--samco-sidebar-text) !important;
    }

    .main-menu .shadow-bottom{
      display:none;
    }

    .main-menu-content{
      padding:.5rem 0 1.2rem 0;
      background:var(--samco-sidebar-bg-soft);
    }

    .navigation-main{
      padding-top:.05rem;
    }

    .navigation-header{
      padding:.5rem 1.35rem .28rem 1.35rem;
      font-size:.7rem;
      letter-spacing:.18em;
      text-transform:uppercase;
      color:var(--samco-sidebar-muted);
      font-weight:600;
    }
    .navigation-header span{
      display:inline-block !important;
    }

    .navigation-main > li.nav-item > a{
      padding:.5rem 1.35rem;
      font-size:.86rem;
      color:var(--samco-sidebar-text);
      display:flex;
      align-items:center;
    }
    .navigation-main > li.nav-item > a i{
      margin-right:.6rem;
      color:var(--samco-sidebar-muted);
    }
    .navigation-main > li.nav-item > a:hover{
      background:rgba(148,163,184,.12);
      color:var(--samco-sidebar-text);
    }

    .main-menu .navigation > li.active > a,
    .main-menu .navigation > li.open > a{
      background:rgba(220,53,69,.12) !important;
      border-left:3px solid var(--samco-primary);
      color:var(--samco-sidebar-text) !important;
    }

    /* SUBMENU (dibesarkan dikit) */
    .main-menu .navigation .menu-content{
      margin:.12rem 0 .35rem 0;
      padding-left:2.6rem;
      border-left:1px dashed rgba(148,163,184,.5);
      display:none !important;
    }
    .main-menu .navigation li.open > .menu-content{
      display:block !important;
    }
    .main-menu .navigation .menu-content > li > a{
      padding:.28rem 0;
      display:flex;
      align-items:center;
      font-size:.84rem; /* sebelumnya .8rem, sekarang sedikit lebih besar */
      color:var(--samco-sidebar-muted);
    }

    /* KONTEN */
    .header-navbar-shadow{
      display:none !important;
      height:0 !important;
    }
    body.vertical-layout .app-content.content{
      padding-top:60px !important;
      margin-top:0 !important;
      margin-left:260px;
    }
    .app-content .content-wrapper{
      padding:.6rem 1.5rem 1.75rem 1.5rem !important;
      margin-top:0 !important;
      color:var(--samco-text-main);
    }
    .card{
      border-radius:.8rem;
      border:none;
      box-shadow:0 10px 25px rgba(15,23,42,.06);
      background:#ffffff;
    }
    body.samco-dark .card{
      background:#020617;
      border:1px solid #1f2937;
    }
    .btn-primary{
      background-color:var(--samco-primary);
      border-color:var(--samco-primary);
    }
    .scroll-top{
      border-radius:999px !important;
      border:none;
      background-color:var(--samco-primary);
      box-shadow:0 14px 38px rgba(15,23,42,0.45);
    }
    .content-overlay{
      display:none !important;
    }

    @media (max-width:1199.98px){
      .header-navbar .navbar-container{
        padding:0 .9rem;
        gap:.75rem;
      }
      .top-left-wrapper{
        min-width:auto;
      }
      body.vertical-layout .app-content.content{
        padding-top:60px !important;
        margin-left:260px !important;
      }
      .app-content .content-wrapper{
        padding:.75rem 1rem 1.5rem 1rem !important;
      }
      .top-search{
        width:200px;
      }
    }

    @media (max-width:767.98px){
      .top-search{
        display:none !important;
      }
    }
  </style>
</head>

<body class="vertical-layout vertical-menu-modern navbar-floating footer-static"
      data-open="click">

  {{-- ================= HEADER ================= --}}
  <nav class="header-navbar navbar navbar-expand-lg navbar-light navbar-shadow">
    <div class="navbar-container">

      <div class="top-left-wrapper">
        <div class="samco-menu-toggle">
          <i class="ficon" data-feather="menu"></i>
        </div>

        <a href="{{ $brandHome }}" class="app-brand-wrapper text-decoration-none">
          <div class="app-brand-logo">
            <img src="{{ asset('app-assets/images/logo/logo.png') }}" alt="Logo">
          </div>
          <div class="app-brand-texts">
            <span class="app-brand-title">SAMCO FARMA</span>
            <span class="app-brand-subtitle">Manufacturing System</span>
          </div>
        </a>
      </div>

      <div class="top-right-wrapper">

        {{-- SEARCH MENU --}}
        <div class="top-search">
          <i data-feather="search" class="top-search-icon"></i>
          <input type="text"
                 id="menu-search"
                 class="top-search-input"
                 placeholder="Cari menu & modul..."
                 autocomplete="off">
          <div class="top-search-result" id="menu-search-result"></div>
        </div>

        {{-- THEME TOGGLE --}}
        <button class="top-icon-btn" id="theme-toggle" type="button" title="Toggle theme">
          <i data-feather="moon" class="theme-icon-moon"></i>
          <i data-feather="sun" class="theme-icon-sun"></i>
        </button>

        {{-- NOTIFICATION --}}
        <div class="dropdown">
          <button class="top-icon-btn" id="dropdown-notif" data-bs-toggle="dropdown" aria-expanded="false">
            <i data-feather="bell"></i>
            <span class="top-icon-badge">3</span>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-notif" style="min-width:260px;">
            <h6 class="dropdown-header">Notifications</h6>
            <a class="dropdown-item" href="#"><i class="me-50" data-feather="activity"></i> Mixing - 2 batch pending approval</a>
            <a class="dropdown-item" href="#"><i class="me-50" data-feather="archive"></i> Secondary Pack - 1 lot ready for QC</a>
            <a class="dropdown-item" href="#"><i class="me-50" data-feather="alert-circle"></i> COA - 1 document needs review</a>
          </div>
        </div>

        <ul class="nav navbar-nav align-items-center">
          <li class="nav-item dropdown dropdown-user">
            <a class="nav-link dropdown-toggle dropdown-user-link" id="dropdown-user"
               href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <div class="user-nav d-sm-flex d-none">
                <span class="user-name fw-bolder">{{ $user->name }}</span>
                <span class="user-status">{{ $user->role }}</span>
              </div>
              <span class="avatar bg-light-primary">
                <div class="avatar-content">{{ $avatar }}</div>
              </span>
            </a>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-user">
              <a class="dropdown-item" href="{{ route('show-profile') }}">
                <i class="me-50" data-feather="user"></i> Profile
              </a>
              <div class="dropdown-divider"></div>
              <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="dropdown-item">
                  <i class="me-50" data-feather="power"></i> Logout
                </button>
              </form>
            </div>
          </li>
        </ul>
      </div>

    </div>
  </nav>

  {{-- ================= SIDEBAR ================= --}}
  <div class="main-menu menu-fixed menu-accordion menu-shadow">
    <div class="navbar-header">
      <ul class="nav navbar-nav flex-row">
        <li class="nav-item me-auto">
          <a class="navbar-brand" href="{{ $brandHome }}">
            <span class="brand-logo">
              <img src="{{ asset('app-assets/images/logo/logo.png') }}" alt="">
            </span>
            <h2 class="brand-text mb-0">SAMCO FARMA</h2>
          </a>
        </li>
      </ul>
    </div>

    <div class="main-menu-content">
      <ul class="navigation navigation-main" id="main-menu-navigation">

        <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
          <a class="d-flex align-items-center" href="{{ route('dashboard') }}">
            <i data-feather="home"></i>
            <span class="menu-title text-truncate">Dashboard</span>
          </a>
        </li>

        {{-- ===================== ADMIN ===================== --}}
        @if ($isAdmin)
          <li class="navigation-header">
            <span>USER MANAGEMENT</span>
          </li>

          <li class="nav-item has-sub {{ request()->is('show-produksi*') || request()->is('show-ppic*') || request()->is('show-qc*') || request()->is('show-qa*') ? 'open' : '' }}">
            <a class="d-flex align-items-center" href="#">
              <i data-feather="user"></i>
              <span class="menu-title text-truncate">User</span>
            </a>
            <ul class="menu-content">
              <li class="{{ request()->is('show-produksi*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('show-produksi') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Produksi</span>
                </a>
              </li>
              <li class="{{ request()->is('show-ppic*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('show-ppic') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">PPIC</span>
                </a>
              </li>
              <li class="{{ request()->is('show-qc*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('show-qc') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">QC</span>
                </a>
              </li>
              <li class="{{ request()->is('show-qa*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('show-qa') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">QA</span>
                </a>
              </li>
            </ul>
          </li>

          <li class="navigation-header">
            <span>MASTER DATA</span>
          </li>

          <li class="nav-item {{ request()->routeIs('produksi.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('produksi.index') }}">
              <i data-feather="box"></i>
              <span class="menu-title text-truncate">Master Produk Produksi</span>
            </a>
          </li>

          <li class="navigation-header">
            <span>PRODUKSI</span>
          </li>

          <li class="nav-item has-sub {{ $isProduksiMenuActive ? 'active open' : '' }}">
            <a class="d-flex align-items-center" href="#">
              <i data-feather="activity"></i>
              <span class="menu-title text-truncate">Proses Produksi</span>
            </a>
            <ul class="menu-content">
              <li class="{{ request()->routeIs('show-permintaan') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('show-permintaan') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Weighing (WO)</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('mixing.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('mixing.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Mixing</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('capsule-filling.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('capsule-filling.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Capsule Filling</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('tableting.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('tableting.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Tableting</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('coating.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('coating.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Coating</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('primary-pack.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('primary-pack.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Primary Pack</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('secondary-pack.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('secondary-pack.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Secondary Pack</span>
                </a>
              </li>
<li class="{{ request()->routeIs('qc-granul.*') ? 'active' : '' }}">
  <a class="d-flex align-items-center" href="{{ route('qc-granul.index') }}">
    <i data-feather="circle"></i>
    <span class="menu-item text-truncate">Produk Antara Granul</span>
  </a>
</li>

<li class="{{ request()->routeIs('qc-tablet.*') ? 'active' : '' }}">
  <a class="d-flex align-items-center" href="{{ route('qc-tablet.index') }}">
    <i data-feather="circle"></i>
    <span class="menu-item text-truncate">Produk Antara Tablet</span>
  </a>
</li>

<li class="{{ request()->routeIs('qc-ruahan.*') ? 'active' : '' }}">
  <a class="d-flex align-items-center" href="{{ route('qc-ruahan.index') }}">
    <i data-feather="circle"></i>
    <span class="menu-item text-truncate">Produk Ruahan</span>
  </a>
</li>

<li class="{{ request()->routeIs('qc-ruahan-akhir.*') ? 'active' : '' }}">
  <a class="d-flex align-items-center" href="{{ route('qc-ruahan-akhir.index') }}">
    <i data-feather="circle"></i>
    <span class="menu-item text-truncate">Produk Ruahan Akhir</span>
  </a>
</li>

            </ul>
          </li>

          <li class="navigation-header">
            <span>BARANG SETELAH PACK</span>
          </li>

          <li class="nav-item has-sub {{ $isAfterPackMenuActive ? 'active open' : '' }}">
            <a class="d-flex align-items-center" href="#">
              <i data-feather="archive"></i>
              <span class="menu-title text-truncate">After Secondary Pack</span>
            </a>
            <ul class="menu-content">
              <li class="{{ request()->routeIs('qty-batch.index') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('qty-batch.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Qty Batch</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('qc-jobsheet.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('qc-jobsheet.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Job Sheet QC</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('sampling.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('sampling.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Sampling</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('coa.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('coa.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">COA QC/QA</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('review.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('review.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Review &amp; Release</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('release.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('release.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Release</span>
                </a>
              </li>
            </ul>
          </li>
        @endif

        {{-- ===================== PRODUKSI NON-ADMIN ===================== --}}
        @if ($isProduksi && !$isAdmin)
          <li class="navigation-header">
            <span>PRODUKSI</span>
          </li>
          <li class="nav-item has-sub {{ $isProduksiMenuActive ? 'active open' : '' }}">
            <a class="d-flex align-items-center" href="#">
              <i data-feather="activity"></i>
              <span class="menu-title text-truncate">Proses Produksi</span>
            </a>
            <ul class="menu-content">
              <li class="{{ request()->routeIs('show-permintaan') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('show-permintaan') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Jadwal Produksi (WO)</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('mixing.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('mixing.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Mixing</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('capsule-filling.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('capsule-filling.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Capsule Filling</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('tableting.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('tableting.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Tableting</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('coating.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('coating.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Coating</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('primary-pack.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('primary-pack.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Primary Pack</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('secondary-pack.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('secondary-pack.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Secondary Pack</span>
                </a>
              </li>
            </ul>
          </li>

          <li class="navigation-header">
            <span>BARANG SETELAH PACK</span>
          </li>
          <li class="nav-item has-sub {{ $isAfterPackMenuActive ? 'active open' : '' }}">
            <a class="d-flex align-items-center" href="#">
              <i data-feather="archive"></i>
              <span class="menu-title text-truncate">After Secondary Pack</span>
            </a>
            <ul class="menu-content">
              <li class="{{ request()->routeIs('qty-batch.index') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('qty-batch.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Qty Batch</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('qc-jobsheet.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('qc-jobsheet.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Job Sheet QC</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('sampling.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('sampling.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Sampling</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('coa.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('coa.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">COA QC/QA</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('review.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('review.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Review &amp; Release</span>
                </a>
              </li>
              <li class="{{ request()->routeIs('release.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('release.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate">Release</span>
                </a>
              </li>
            </ul>
          </li>
        @endif

        {{-- ===================== QA NON-ADMIN ===================== --}}
        @if ($isQA && !$isAdmin)
          <li class="navigation-header">
            <span>PRODUKSI</span>
          </li>
          <li class="nav-item has-sub
    {{ request()->routeIs('qc-granul.*')
        || request()->routeIs('qc-tablet.*')
        || request()->routeIs('qc-ruahan.*')
        || request()->routeIs('qc-ruahan-akhir.*')
        ? 'active open'
        : '' }}">
  <a class="d-flex align-items-center" href="#">
    <i data-feather="check-circle"></i>
    <span class="menu-title text-truncate">QC Release per Tahap</span>
  </a>
  <ul class="menu-content">
    <li class="{{ request()->routeIs('qc-granul.*') ? 'active' : '' }}">
      <a class="d-flex align-items-center" href="{{ route('qc-granul.index') }}">
        <i data-feather="circle"></i>
        <span class="menu-item text-truncate">Produk Antara Granul</span>
      </a>
    </li>
    <li class="{{ request()->routeIs('qc-tablet.*') ? 'active' : '' }}">
      <a class="d-flex align-items-center" href="{{ route('qc-tablet.index') }}">
        <i data-feather="circle"></i>
        <span class="menu-item text-truncate">Produk Antara Tablet</span>
      </a>
    </li>
    <li class="{{ request()->routeIs('qc-ruahan.*') ? 'active' : '' }}">
      <a class="d-flex align-items-center" href="{{ route('qc-ruahan.index') }}">
        <i data-feather="circle"></i>
        <span class="menu-item text-truncate">Produk Ruahan</span>
      </a>
    </li>
    <li class="{{ request()->routeIs('qc-ruahan-akhir.*') ? 'active' : '' }}">
      <a class="d-flex align-items-center" href="{{ route('qc-ruahan-akhir.index') }}">
        <i data-feather="circle"></i>
        <span class="menu-item text-truncate">Produk Ruahan Akhir</span>
      </a>
    </li>
  </ul>
</li>


          <li class="navigation-header">
            <span>DOKUMEN QA</span>
          </li>
          <li class="nav-item {{ request()->routeIs('coa.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('coa.index') }}">
              <i data-feather="file-text"></i>
              <span class="menu-title text-truncate">COA QC/QA</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('review.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('review.index') }}">
              <i data-feather="check-square"></i>
              <span class="menu-title text-truncate">Review</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('release.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('release.index') }}">
              <i data-feather="check-circle"></i>
              <span class="menu-title text-truncate">Release</span>
            </a>
          </li>
        @endif

      </ul>
    </div>
  </div>

  {{-- ================= CONTENT ================= --}}
  <div class="app-content content">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper container-xxl p-0">
      <div class="content-header row"></div>
      <div class="content-body">
        @yield('content')

        <button class="btn btn-primary btn-icon scroll-top" type="button">
          <i data-feather="arrow-up"></i>
        </button>
      </div>
    </div>
  </div>

  {{-- Vendor JS --}}
  <script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/tables/datatable/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/tables/datatable/dataTables.bootstrap5.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/tables/datatable/dataTables.responsive.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/tables/datatable/responsive.bootstrap4.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.buttons.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/forms/validation/jquery.validate.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/charts/apexcharts.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/forms/select/select2.full.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/pickers/pickadate/picker.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/pickers/pickadate/picker.date.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/pickers/pickadate/picker.time.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/pickers/pickadate/legacy.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/pickers/flatpickr/flatpickr.min.js') }}"></script>

  {{-- Theme JS (TANPA app-menu) --}}
  {{-- <script src="{{ asset('app-assets/js/core/app-menu.min.js') }}"></script> --}}
  <script src="{{ asset('app-assets/js/core/app.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/customizer.min.js') }}"></script>

  {{-- Page JS --}}
  <script src="{{ asset('app-assets/js/scripts/cards/card-analytics.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/forms/form-select2.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/pages/dashboard-ecommerce.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/pages/app-user-list.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/tables/table-datatables-advanced.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/forms/pickers/form-pickers.min.js') }}"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const body = document.body;

      // INIT FEATHER ICONS
      if (window.feather) {
        window.feather.replace({ width: 16, height: 16 });
      }

      // PAKSA SIDEBAR FULL
      body.classList.remove('menu-collapsed', 'menu-hide', 'menu-open');
      body.classList.add('menu-expanded');

      // THEME INIT (light/dark)
      const savedTheme = localStorage.getItem('samcoTheme') || 'light';
      if (savedTheme === 'dark') {
        body.classList.add('samco-dark');
      } else {
        body.classList.remove('samco-dark');
      }

      const themeBtn = document.getElementById('theme-toggle');
      if (themeBtn) {
        themeBtn.addEventListener('click', function () {
          body.classList.toggle('samco-dark');
          const isDark = body.classList.contains('samco-dark');
          localStorage.setItem('samcoTheme', isDark ? 'dark' : 'light');
        });
      }

      // SUBMENU TOGGLE SEDERHANA
      const nav = document.getElementById('main-menu-navigation');
      if (nav) {
        const parents = nav.querySelectorAll('li.nav-item.has-sub > a');

        parents.forEach(function (link) {
          link.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const li = this.parentElement;

            // accordion: tutup parent lain
            nav.querySelectorAll('li.nav-item.has-sub.open').forEach(function (item) {
              if (item !== li) item.classList.remove('open');
            });

            li.classList.toggle('open');
          });
        });
      }

      // ================= MENU SEARCH =================
      const searchInput  = document.getElementById('menu-search');
      const searchResult = document.getElementById('menu-search-result');
      const menuItems    = [];

      if (nav && searchInput && searchResult) {
        // Kumpulkan semua link menu & submenu
        nav.querySelectorAll('li.nav-item a').forEach(function (a) {
          const href = a.getAttribute('href');
          if (!href || href === '#') return;

          const titleEl = a.querySelector('.menu-title, .menu-item');
          let label = titleEl ? titleEl.innerText : a.textContent;
          label = (label || '').trim();
          if (!label) return;

          // Cari section-nya dari navigation-header sebelumnya
          let section = '';
          let li = a.closest('li.nav-item, li');
          while (li && !section) {
            const prevHeader = li.previousElementSibling;
            if (prevHeader && prevHeader.classList.contains('navigation-header')) {
              section = prevHeader.innerText.trim();
              break;
            }
            li = li.parentElement;
          }

          menuItems.push({
            label: label,
            section: section,
            href: href
          });
        });

        function renderResults(list) {
          searchResult.innerHTML = '';
          if (!list.length) {
            searchResult.classList.remove('show');
            return;
          }

          list.forEach(function (item) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'top-search-item';

            const spanLabel = document.createElement('span');
            spanLabel.className = 'label';
            spanLabel.textContent = item.label;

            const spanBadge = document.createElement('span');
            spanBadge.className = 'badge';
            spanBadge.textContent = item.section || 'Menu';

            btn.appendChild(spanLabel);
            btn.appendChild(spanBadge);

            btn.addEventListener('click', function () {
              window.location.href = item.href;
            });

            searchResult.appendChild(btn);
          });

          searchResult.classList.add('show');
        }

        function doSearch() {
          const q = searchInput.value.trim().toLowerCase();
          if (!q) {
            searchResult.classList.remove('show');
            searchResult.innerHTML = '';
            return;
          }

          const filtered = menuItems
            .filter(function (item) {
              return item.label.toLowerCase().includes(q);
            })
            .slice(0, 10);

          renderResults(filtered);
        }

        searchInput.addEventListener('input', doSearch);

        searchInput.addEventListener('keydown', function (e) {
          if (e.key === 'Enter') {
            const first = searchResult.querySelector('.top-search-item');
            if (first) {
              e.preventDefault();
              first.click();
            }
          } else if (e.key === 'Escape') {
            searchResult.classList.remove('show');
          }
        });

        searchInput.addEventListener('focus', function () {
          if (this.value.trim()) {
            doSearch();
          }
        });

        // Klik luar untuk menutup dropdown
        document.addEventListener('click', function (e) {
          if (!searchResult.contains(e.target) && e.target !== searchInput) {
            searchResult.classList.remove('show');
          }
        });
      }
    });
  </script>
</body>
</html>
