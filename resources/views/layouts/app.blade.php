{{-- resources/views/layouts/app.blade.php --}}
@php
  $user   = auth()->user();
  $avatar = strtoupper(substr($user->name ?? 'U', 0, 2));
  $role   = strtolower((string)($user->role ?? ''));

  // ===== ROLE FLAGS =====
  $isAdmin     = in_array($role, ['admin','administrator','superadmin'], true);
  $isProduksi  = ($role === 'produksi');
  $isPPIC      = ($role === 'ppic');
  $isQA        = ($role === 'qa');
  $isQC        = ($role === 'qc');
  $isGudang    = ($role === 'gudang');

  // ===== PRODUKSI LEVEL (ADMIN / SPV / OPERATOR) =====
  $prodLevelRaw = (string)($user->produksi_role
                    ?? $user->level_produksi
                    ?? $user->production_level
                    ?? $user->level
                    ?? '');
  $prodLevel = strtoupper(trim($prodLevelRaw));
  $isProdAdminSpv = $isProduksi && in_array($prodLevel, ['ADMIN','SPV'], true);
  $isProdOperator = $isProduksi && (!$isProdAdminSpv);

  $brandHome = route('dashboard');

  // =========================
  // ACTIVE MENU HELPERS
  // =========================
  $routesProduksiNonQC = [
      'show-permintaan',
      'tracking-batch.*',
      'weighing.*',
      'mixing.*',
      'tableting.*',
      'capsule-filling.*',
      'coating.*',
      'primary-pack.*',
      'secondary-pack.*',
  ];

  $routesQCOnly = [
      'qc-granul.*',
      'qc-tablet.*',
      'qc-ruahan.*',
      'qc-ruahan-akhir.*',
  ];

  $routesHolding = ['holding.*'];

  if ($isAdmin) {
      $isProduksiMenuActive = collect(array_merge($routesProduksiNonQC, $routesQCOnly, $routesHolding))
          ->contains(fn ($r) => request()->routeIs($r));
  } elseif ($isProduksi) {
      $base = array_merge($routesProduksiNonQC, $routesHolding);
      $isProduksiMenuActive = collect($base)->contains(fn ($r) => request()->routeIs($r));
  } elseif ($isQC || $isQA) {
      // note: keep computing for QC; QA won't see the Proses Produksi section per rules below
      $isProduksiMenuActive = collect(array_merge($routesQCOnly, $routesHolding))
          ->contains(fn ($r) => request()->routeIs($r));
  } else {
      $isProduksiMenuActive = collect(array_merge($routesProduksiNonQC, $routesQCOnly, $routesHolding))
          ->contains(fn ($r) => request()->routeIs($r));
  }

  $isAfterPackMenuActive =
      request()->routeIs('qc-jobsheet.*')
      || request()->routeIs('sampling.*')
      || request()->routeIs('coa.*')
      || request()->routeIs('review.*')
      || request()->routeIs('release.*')
      || request()->routeIs('qc-release.*')
      || request()->routeIs('uji-coa.*');

  // NOTIFIKASI: PROGRESS BATCH
  $notifItems = [];
  $notifCount = 0;

  $canProduksiNotif  = $isAdmin || $isProduksi || $isPPIC;
  $canQCNotif        = $isAdmin || $isQC || $isQA;
  $canAfterPackNotif = $isAdmin || $isQA || $isPPIC;

  $makeLink = function(string $routeName, ?string $q = null) {
    try{
      if (\Illuminate\Support\Facades\Route::has($routeName)) {
        $url = route($routeName);
        if ($q) $url .= '?q=' . urlencode($q);
        return $url;
      }
    }catch(\Throwable $e){}
    return '#';
  };

  $stageToRoute = [
    'weighing'          => 'weighing.index',
    'mixing'            => 'mixing.index',
    'tableting'         => 'tableting.index',
    'capsule_filling'   => 'capsule-filling.index',
    'capsulefilling'    => 'capsule-filling.index',
    'coating'           => 'coating.index',
    'primary_pack'      => 'primary-pack.index',
    'secondary_pack'    => 'secondary-pack.index',
    'secondary_pack_1'  => 'secondary-pack.index',
    'secondary_pack_2'  => 'secondary-pack.index',
    'qc_granul'         => 'qc-granul.index',
    'qc_tablet'         => 'qc-tablet.index',
    'qc_ruahan'         => 'qc-ruahan.index',
    'qc_ruahan_akhir'   => 'qc-ruahan-akhir.index',
    'holding'           => 'holding.index',
  ];

  try {
    $cacheKey = 'samco_notif_progress_' . ($user->id ?? '0') . '_' . date('YmdHi');

    $notifItems = \Illuminate\Support\Facades\Cache::remember($cacheKey, 55, function() use (
      $canProduksiNotif,
      $canQCNotif,
      $canAfterPackNotif,
      $makeLink,
      $stageToRoute
    ) {
      $items = [];

      $batchCode = function($b){
        $kode = trim((string)($b->kode_batch ?? ''));
        if ($kode !== '') return $kode;
        $no = trim((string)($b->no_batch ?? ''));
        return $no !== '' ? $no : ('#'.$b->id);
      };

      $pauseHref = function($b) use ($stageToRoute, $makeLink, $batchCode){
        $raw = (string)($b->paused_stage ?? '');
        $key = strtolower(trim($raw));
        $key = str_replace([' ', '-'], '_', $key);
        $route = $stageToRoute[$key] ?? null;
        if ($route) return $makeLink($route, $batchCode($b));
        return $makeLink('dashboard');
      };

      // PAUSE
      $paused = \App\Models\ProduksiBatch::query()
        ->where(function($q){
          $q->where('is_paused', 1)->orWhere('is_paused', true);
        })
        ->orderByDesc('paused_at')
        ->limit(4)
        ->get(['id','no_batch','kode_batch','nama_produk','paused_stage','paused_reason','paused_at','tipe_alur']);

      foreach ($paused as $b) {
        $code = $batchCode($b);
        $stage = trim((string)($b->paused_stage ?? ''));
        if ($stage === '') $stage = 'PROSES';
        $reason = trim((string)($b->paused_reason ?? ''));
        if ($reason === '') $reason = '-';

        $items[] = [
          'icon' => 'pause-circle',
          'text' => "PAUSE • {$code} ({$b->nama_produk}) — {$stage} | {$reason}",
          'href' => $pauseHref($b),
        ];
      }

      // HOLD
      $holding = \App\Models\ProduksiBatch::query()
        ->where(function($q){
          $q->where('is_holding', 1)->orWhere('is_holding', true);
        })
        ->orderByDesc('holding_at')
        ->limit(4)
        ->get(['id','no_batch','kode_batch','nama_produk','holding_stage','holding_reason','holding_at','tipe_alur']);

      foreach ($holding as $b) {
        $code = $batchCode($b);
        $stage = trim((string)($b->holding_stage ?? ''));
        if ($stage === '') $stage = 'HOLDING';
        $reason = trim((string)($b->holding_reason ?? ''));
        if ($reason === '') $reason = '-';

        $items[] = [
          'icon' => 'alert-octagon',
          'text' => "HOLD • {$code} ({$b->nama_produk}) — {$stage} | {$reason}",
          'href' => $makeLink('holding.index', $code),
        ];
      }

      // NEXT PRODUKSI (non-QC)
      if ($canProduksiNotif) {
        $readyMixing = \App\Models\ProduksiBatch::query()
          ->whereNotNull('tgl_weighing')
          ->whereNull('tgl_mixing')
          ->where(function($q){ $q->whereNull('is_paused')->orWhere('is_paused', false); })
          ->where(function($q){ $q->whereNull('is_holding')->orWhere('is_holding', false); })
          ->orderByDesc('tgl_weighing')
          ->limit(3)
          ->get(['id','no_batch','kode_batch','nama_produk','tgl_weighing','tipe_alur']);

        foreach ($readyMixing as $b) {
          $code = $batchCode($b);
          $items[] = [
            'icon' => 'arrow-right-circle',
            'text' => "NEXT • {$code} ({$b->nama_produk}) — Weighing selesai → siap Mixing",
            'href' => $makeLink('mixing.index', $code),
          ];
        }

        $readyCapsule = \App\Models\ProduksiBatch::query()
          ->where('tipe_alur', 'KAPSUL')
          ->whereNotNull('tgl_mixing')
          ->whereNull('tgl_capsule_filling')
          ->where(function($q){ $q->whereNull('is_paused')->orWhere('is_paused', false); })
          ->where(function($q){ $q->whereNull('is_holding')->orWhere('is_holding', false); })
          ->orderByDesc('tgl_mixing')
          ->limit(3)
          ->get(['id','no_batch','kode_batch','nama_produk','tgl_mixing','tipe_alur']);

        foreach ($readyCapsule as $b) {
          $code = $batchCode($b);
          $items[] = [
            'icon' => 'arrow-right-circle',
            'text' => "NEXT • {$code} ({$b->nama_produk}) — Mixing selesai → siap Capsule Filling",
            'href' => $makeLink('capsule-filling.index', $code),
          ];
        }

        $readyTableting = \App\Models\ProduksiBatch::query()
          ->where(function($q){
            $q->whereNull('tipe_alur')->orWhere('tipe_alur', '!=', 'KAPSUL');
          })
          ->whereNotNull('tgl_mixing')
          ->whereNull('tgl_tableting')
          ->where(function($q){ $q->whereNull('is_paused')->orWhere('is_paused', false); })
          ->where(function($q){ $q->whereNull('is_holding')->orWhere('is_holding', false); })
          ->orderByDesc('tgl_mixing')
          ->limit(3)
          ->get(['id','no_batch','kode_batch','nama_produk','tgl_mixing','tipe_alur']);

        foreach ($readyTableting as $b) {
          $code = $batchCode($b);
          $items[] = [
            'icon' => 'arrow-right-circle',
            'text' => "NEXT • {$code} ({$b->nama_produk}) — Mixing selesai → siap Tableting",
            'href' => $makeLink('tableting.index', $code),
          ];
        }

        $readyCoating = \App\Models\ProduksiBatch::query()
          ->where('tipe_alur', 'TABLET_SALUT')
          ->whereNotNull('tgl_tableting')
          ->whereNull('tgl_coating')
          ->where(function($q){ $q->whereNull('is_paused')->orWhere('is_paused', false); })
          ->where(function($q){ $q->whereNull('is_holding')->orWhere('is_holding', false); })
          ->orderByDesc('tgl_tableting')
          ->limit(3)
          ->get(['id','no_batch','kode_batch','nama_produk','tgl_tableting','tipe_alur']);

        foreach ($readyCoating as $b) {
          $code = $batchCode($b);
          $items[] = [
            'icon' => 'arrow-right-circle',
            'text' => "NEXT • {$code} ({$b->nama_produk}) — Tableting selesai → siap Coating",
            'href' => $makeLink('coating.index', $code),
          ];
        }

        $readyPrimary = \App\Models\ProduksiBatch::query()
          ->whereNull('tgl_primary_pack')
          ->where(function($q){
            $q->where(function($a){
              $a->where('tipe_alur','KAPSUL')->whereNotNull('tgl_capsule_filling');
            })
            ->orWhere(function($a){
              $a->where('tipe_alur','!=','KAPSUL')
                ->whereNotNull('tgl_tableting')
                ->where(function($b){
                  $b->where('tipe_alur','!=','TABLET_SALUT')
                    ->orWhereNotNull('tgl_coating');
                });
            });
          })
          ->where(function($q){ $q->whereNull('is_paused')->orWhere('is_paused', false); })
          ->where(function($q){ $q->whereNull('is_holding')->orWhere('is_holding', false); })
          ->orderByDesc('updated_at')
          ->limit(3)
          ->get(['id','no_batch','kode_batch','nama_produk','tipe_alur','updated_at']);

        foreach ($readyPrimary as $b) {
          $code = $batchCode($b);
          $items[] = [
            'icon' => 'arrow-right-circle',
            'text' => "NEXT • {$code} ({$b->nama_produk}) — Tahap inti selesai → siap Primary Pack",
            'href' => $makeLink('primary-pack.index', $code),
          ];
        }

        $readySec1 = \App\Models\ProduksiBatch::query()
          ->whereNotNull('tgl_primary_pack')
          ->whereNull('tgl_secondary_pack_1')
          ->where(function($q){ $q->whereNull('is_paused')->orWhere('is_paused', false); })
          ->where(function($q){ $q->whereNull('is_holding')->orWhere('is_holding', false); })
          ->orderByDesc('tgl_primary_pack')
          ->limit(3)
          ->get(['id','no_batch','kode_batch','nama_produk','tgl_primary_pack','tipe_alur']);

        foreach ($readySec1 as $b) {
          $code = $batchCode($b);
          $items[] = [
            'icon' => 'arrow-right-circle',
            'text' => "NEXT • {$code} ({$b->nama_produk}) — Primary Pack selesai → siap Secondary Pack",
            'href' => $makeLink('secondary-pack.index', $code),
          ];
        }

        $readySec2 = \App\Models\ProduksiBatch::query()
          ->whereNotNull('tgl_secondary_pack_1')
          ->whereNull('tgl_secondary_pack_2')
          ->where(function($q){ $q->whereNull('is_paused')->orWhere('is_paused', false); })
          ->where(function($q){ $q->whereNull('is_holding')->orWhere('is_holding', false); })
          ->orderByDesc('tgl_secondary_pack_1')
          ->limit(3)
          ->get(['id','no_batch','kode_batch','nama_produk','tgl_secondary_pack_1','tipe_alur']);

        foreach ($readySec2 as $b) {
          $code = $batchCode($b);
          $items[] = [
            'icon' => 'check-circle',
            'text' => "NEXT • {$code} ({$b->nama_produk}) — Secondary Pack 1 selesai → siap Finish (Secondary 2)",
            'href' => $makeLink('secondary-pack.index', $code),
          ];
        }
      }

      // NOTIF QC (ringkas)
      if ($canQCNotif) {
        $qcGranulWaiting = \App\Models\ProduksiBatch::query()
          ->whereNotNull('tgl_mixing')
          ->whereNull('tgl_datang_granul')
          ->where(function($q){ $q->whereNull('is_paused')->orWhere('is_paused', false); })
          ->where(function($q){ $q->whereNull('is_holding')->orWhere('is_holding', false); })
          ->orderByDesc('tgl_mixing')
          ->limit(2)
          ->get(['id','no_batch','kode_batch','nama_produk','tgl_mixing','tgl_datang_granul']);

        foreach ($qcGranulWaiting as $b) {
          $code = $batchCode($b);
          $items[] = [
            'icon' => 'check-square',
            'text' => "QC • {$code} ({$b->nama_produk}) — Mixing selesai → menunggu QC Granul Datang",
            'href' => $makeLink('qc-granul.index', $code),
          ];
        }
      }

      return array_slice($items, 0, 10);
    });

  } catch (\Throwable $e) {
    $notifItems = [];
  }

  $notifCount = is_array($notifItems) ? count($notifItems) : 0;

  // VISIBILITY RULES (MENU)
  $canSeeMasterProdukProduksi = $isAdmin || $isProdAdminSpv;
  // REVISI: jangan tampilkan menu PROSES QC di bagian PRODUKSI untuk QA
  $canSeeQcMenusInProses = $isAdmin || $isQC; // removed $isQA so QA won't see these under Proses Produksi
  $canManageUsersProduksi = $isAdmin || $isProdAdminSpv;

  // === REVISI: menu GUDANG visibility granular ===
  // Goal for this request:
  // - QA should only see Dashboard + After Secondary Pack
  // - therefore exclude QA from PRODUKSI and GUDANG items
  // - keep After Secondary Pack visible to QA

  // show produksi section only for admin / produksi / qc (QA excluded)
  $showProduksiSection = $isAdmin || $isProduksi || $isQC;

  // granular flags per submenu (exclude QA from Bahan Jadi items)
  $canSeeSecondaryRelease = $isAdmin || $isGudang || $isPPIC || $isProduksi;
  $canSeeSpvReview        = $isAdmin || $isPPIC || $isProduksi;
  $canSeeGoj              = $isAdmin || $isGudang || $isPPIC;

  // show header only if any submenu visible
  $showGudangHeader = $canSeeSecondaryRelease || $canSeeSpvReview || $canSeeGoj;
@endphp

<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PT. SAMCO Farma</title>

  <link rel="shortcut icon" type="image/x-icon" href="{{ asset('app-assets/images/logo/logo.png') }}" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">

  {{-- Small script to pre-apply theme class to avoid "half" flash on first paint --}}
  <script>
    (function(){
      try{
        var t = localStorage.getItem('samcoTheme');
        if(t === 'dark'){
          // add a generic root class early; CSS below will use both body.samco-dark and .samco-dark
          document.documentElement.classList.add('samco-dark');
          // also set a data-attr so CSS can target early (if needed)
          document.documentElement.setAttribute('data-samco-theme', 'dark');
        } else {
          document.documentElement.removeAttribute('data-samco-theme');
        }
      }catch(e){}
    })();
  </script>

  {{-- Vendor CSS --}}
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/vendors.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/charts/apexcharts.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/extensions/toastr.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/pickers/pickadate/pickadate.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/pickers/flatpickr/flatpickr.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/dataTables.bootstrap5.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/responsive.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/buttons.bootstrap5.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/forms/select/select2.min.css') }}">

  {{-- Theme --}}
  <link rel="stylesheet" href="{{ asset('app-assets/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/bootstrap-extended.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/colors.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/components.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/themes/dark-layout.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/themes/bordered-layout.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/themes/semi-dark-layout.min.css') }}">

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

  {{-- CUSTOM UI --}}
  <style>
    /* ---------------------------
       Theme variables (light)
       --------------------------- */
    :root{
      --samco-primary: #dc3545;
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
      --samco-card-bg: #ffffff;
      --samco-shadow: 0 10px 25px rgba(15,23,42,.06);
      --samco-border-weak: rgba(0,0,0,.06);
    }

    /* ---------------------------
       Dark variables (applies when .samco-dark root present or body.samco-dark)
       --------------------------- */
    body.samco-dark, .samco-dark {
      --samco-bg: #020617;
      --samco-header-bg: #020617;
      --samco-header-border: #1f2937;
      --samco-sidebar-bg-soft: #030616;
      --samco-sidebar-header-bg: #020617;
      --samco-sidebar-border: #111827; /* subtle */
      --samco-sidebar-text: #e5e7eb;
      --samco-sidebar-muted: #9ca3af;
      --samco-text-main: #e5e7eb;
      --samco-text-muted: #9ca3af;
      --samco-card-bg: #071225;
      --samco-shadow: none;
      --samco-border-weak: rgba(255,255,255,0.04);
    }

    html, body{
      font-family: 'Montserrat', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      color: var(--samco-text-main);
      background: var(--samco-bg);
    }

    /* Ensure full-area dark background when samco-dark is present on html or body */
    body.samco-dark .app-content,
    .samco-dark .app-content,
    body.samco-dark .content-wrapper,
    .samco-dark .content-wrapper,
    body.samco-dark .content,
    .samco-dark .content,
    body.samco-dark .content-body,
    .samco-dark .content-body,
    body.samco-dark .app-content .content-wrapper,
    .samco-dark .app-content .content-wrapper {
      background-color: var(--samco-bg) !important;
      color: var(--samco-text-main) !important;
      /* ensure full viewport coverage for pages with sparse content */
      min-height: calc(100vh - 60px);
    }

    /* HEADER */
    .header-navbar{
      background:var(--samco-header-bg) !important;
      border-bottom:1px solid var(--samco-header-border) !important;
      box-shadow:0 1px 6px rgba(15,23,42,.06);
      height:60px;
      position:fixed !important;
      top:0 !important;
      left:0;
      right:0;
      z-index:1040 !important;
      padding:0 !important;
    }
    body.samco-dark .header-navbar, .samco-dark .header-navbar {
      box-shadow: none;
      background: var(--samco-header-bg) !important;
    }
    .header-navbar .navbar-container{
      height:60px;
      padding:0 1.15rem;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:1rem;
    }

    .top-left-wrapper{ display:flex; align-items:center; gap:.85rem; min-width:260px; }
    .samco-menu-toggle{
      display:flex; align-items:center; justify-content:center;
      width:34px; height:34px; border-radius:10px;
      border:1px solid var(--samco-header-border);
      background:var(--samco-header-bg);
      color:var(--samco-text-muted) !important;
      cursor:pointer; user-select:none;
    }
    body.samco-dark .samco-menu-toggle, .samco-dark .samco-menu-toggle{ background:transparent; border-color:var(--samco-header-border); color:var(--samco-text-main) !important; }

    /* Logo SAMCO — ukuran dikurangi sedikit (request) */
    .samco-logo-badge{
      width:auto;
      height:auto;
      border-radius:0;
      background:transparent;
      box-shadow:none;
      border:none;
      padding:0;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    /* Logo di HEADER (lebih kecil) */
    .samco-logo-badge img{
      height:26px; /* dikurangi dari 30px */
      width:auto;
      object-fit:contain;
      filter:none;
      display:block;
    }

    /* Logo di SIDEBAR (lebih kecil) */
    .main-menu .samco-logo-badge img{
      height:22px; /* dikurangi dari 24px */
    }

    .app-brand-wrapper{
      display:flex; align-items:center; gap:.7rem;
      text-decoration:none !important;
    }
    .app-brand-texts{ display:flex; flex-direction:column; line-height:1.05; }
    .app-brand-title{
      font-size:.9rem; font-weight:700; letter-spacing:.12em;
      text-transform:uppercase; color:var(--samco-text-main);
    }
    .app-brand-subtitle{ font-size:.72rem; color:var(--samco-text-muted); }

    .top-right-wrapper{ display:flex; align-items:center; gap:.6rem; }

    .top-clock{
      display:flex; align-items:center; gap:.5rem;
      padding:.3rem .75rem;
      border-radius:999px;
      border:1px solid var(--samco-header-border);
      background:var(--samco-header-bg);
      font-size:.78rem;
      color:var(--samco-text-main);
      white-space:nowrap; user-select:none;
    }
    body.samco-dark .top-clock, .samco-dark .top-clock{ background:transparent; border-color:var(--samco-header-border); }
    .top-clock .clock-time{ font-weight:800; font-variant-numeric: tabular-nums; }

    .top-icon-btn{
      position:relative;
      width:34px; height:34px; border-radius:999px;
      border:1px solid var(--samco-header-border);
      display:flex; align-items:center; justify-content:center;
      background:var(--samco-header-bg);
      cursor:pointer;
      color:var(--samco-text-muted) !important;
    }
    body.samco-dark .top-icon-btn, .samco-dark .top-icon-btn{ background:transparent; border-color:var(--samco-header-border); color:var(--samco-text-main) !important; }
    .top-icon-badge{
      position:absolute; top:3px; right:3px;
      width:15px; height:15px; border-radius:999px;
      background:var(--samco-primary); color:#fff; font-size:.6rem;
      display:flex; align-items:center; justify-content:center;
    }

    .theme-icon-sun{ display:none; }
    body.samco-dark .theme-icon-moon, .samco-dark .theme-icon-moon{ display:none; }
    body.samco-dark .theme-icon-sun, .samco-dark .theme-icon-sun{ display:inline-block; }

    .top-search{ position:relative; width:280px; max-width:100%; display:flex; align-items:center; }
    .top-search-icon{ position:absolute; left:0.65rem; top:50%; transform:translateY(-50%); pointer-events:none; color:var(--samco-text-muted); }
    .top-search-input{
      width:100%;
      padding:.35rem .6rem .35rem 2.1rem;
      border-radius:999px;
      border:1px solid var(--samco-header-border);
      background:var(--samco-header-bg);
      font-size:.78rem;
      outline:none;
      color:var(--samco-text-main);
    }
    .top-search-input::placeholder{ color:var(--samco-text-muted); font-size:.78rem; }
    body.samco-dark .top-search-input, .samco-dark .top-search-input{ background:transparent; border-color:var(--samco-header-border); color:var(--samco-text-main); }

    .top-search-result{
      position:absolute; left:0; right:0; top:100%; margin-top:6px;
      background:var(--samco-header-bg);
      border:1px solid var(--samco-header-border);
      border-radius:.75rem;
      box-shadow:0 18px 45px rgba(15,23,42,.18);
      padding:.35rem 0;
      display:none;
      z-index:2000;
      overflow:hidden;
    }
    body.samco-dark .top-search-result, .samco-dark .top-search-result{ border-color:var(--samco-header-border); background:transparent; box-shadow:none; }
    .top-search-result.show{ display:block; }
    .top-search-item{
      width:100%; border:none; background:transparent;
      padding:.45rem .85rem;
      font-size:.78rem;
      text-align:left;
      color:var(--samco-text-main);
      cursor:pointer;
      display:flex; align-items:center; justify-content:space-between; gap:.35rem;
    }
    .top-search-item span.label{ flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .top-search-item span.badge{
      font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:var(--samco-text-muted);
    }
    .top-search-item:hover{ background: rgba(0,0,0,0.04); }
    body.samco-dark .top-search-item:hover, .samco-dark .top-search-item:hover{ background: rgba(255,255,255,0.03); }

    /* SIDEBAR */
    .main-menu{
      background:var(--samco-sidebar-bg-soft);
      border-right:1px solid var(--samco-sidebar-border);
      box-shadow:2px 0 14px rgba(0,0,0,.08);
      top:60px !important;
      width:260px !important;
      transform:none !important;
    }
    body.samco-dark .main-menu, .samco-dark .main-menu {
      background: var(--samco-sidebar-bg-soft) !important;
      border-right:1px solid var(--samco-sidebar-border) !important;
      box-shadow: none;
    }
    .main-menu .navbar-header{
      padding:.9rem 1.15rem .8rem 1.15rem;
      border-bottom:1px solid var(--samco-sidebar-border);
      background:var(--samco-sidebar-header-bg);
    }
    .main-menu .navbar-brand{
      display:flex; align-items:center; gap:.7rem;
      text-decoration:none !important;
    }
    .main-menu .navbar-header .navbar-brand .brand-text{
      font-size:.9rem; font-weight:800; letter-spacing:.14em; text-transform:uppercase;
      color:var(--samco-sidebar-text) !important;
      margin-bottom:0;
    }
    .main-menu .shadow-bottom{ display:none; }
    .main-menu-content{ padding:.5rem 0 1.2rem 0; background:var(--samco-sidebar-bg-soft); }
    .navigation-main{ padding-top:.05rem; }

    .navigation-header{
      padding:.5rem 1.35rem .28rem 1.35rem;
      font-size:.7rem; letter-spacing:.18em; text-transform:uppercase;
      color:var(--samco-sidebar-muted); font-weight:700;
    }
    .navigation-header span{ display:inline-block !important; }

    .navigation-main > li.nav-item > a{
      padding:.5rem 1.35rem;
      font-size:.86rem;
      color:var(--samco-sidebar-text);
      display:flex;
      align-items:center;
      gap:.6rem;
    }
    .navigation-main > li.nav-item > a i{
      margin-right:0 !important;
      color:var(--samco-sidebar-muted);
    }
    .navigation-main > li.nav-item > a:hover{ background:rgba(0,0,0,0.04); color:var(--samco-sidebar-text); }

    .main-menu .navigation > li.active > a,
    .main-menu .navigation > li.open > a{
      background:rgba(220,53,69,.12) !important;
      border-left:3px solid var(--samco-primary);
      color:var(--samco-sidebar-text) !important;
    }

    .main-menu .navigation .menu-content{
      margin:.12rem 0 .35rem 0;
      padding-left:2.6rem;
      border-left:1px dashed var(--samco-border-weak);
      display:none !important;
    }
    .main-menu .navigation li.open > .menu-content{ display:block !important; }
    .main-menu .navigation .menu-content > li > a{
      padding:.28rem 0;
      display:flex;
      align-items:center;
      font-size:.84rem;
      color:var(--samco-sidebar-muted);
      gap:.55rem;
    }

    /* CONTENT */
    .header-navbar-shadow{ display:none !important; height:0 !important; }
    body.vertical-layout .app-content.content{
      padding-top:60px !important;
      margin-top:0 !important;
      margin-left:260px;
      background: transparent !important;
    }
    .app-content .content-wrapper{
      padding:.7rem 1.5rem 1.75rem 1.5rem !important;
      margin-top:0 !important;
      color:var(--samco-text-main);
      background: transparent;
    }

    .card{
      border-radius:.85rem;
      border:1px solid var(--samco-border-weak);
      box-shadow:var(--samco-shadow);
      background:var(--samco-card-bg);
    }
    body.samco-dark .card, .samco-dark .card{ background:var(--samco-card-bg); border:1px solid var(--samco-border-weak); box-shadow:none; }

    .btn-primary{ background-color:var(--samco-primary); border-color:var(--samco-primary); }
    .scroll-top{
      border-radius:999px !important;
      border:none;
      background-color:var(--samco-primary);
      box-shadow:0 14px 38px rgba(15,23,42,0.45);
    }

    .content-overlay{ display:none !important; }

    /* TABLE / DATATABLE */
    table.dataTable, .table {
      color: var(--samco-text-main);
      background: transparent;
    }
    .table thead th {
      color: var(--samco-text-main);
      background: rgba(255,255,255,0.02);
      border-bottom:1px solid var(--samco-border-weak);
    }
    body.samco-dark .table thead th, .samco-dark .table thead th {
      background: rgba(255,255,255,0.02) !important;
      border-bottom:1px solid var(--samco-border-weak) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
      background: transparent;
      border: 1px solid transparent;
      color: var(--samco-text-main);
    }

    /* DROPDOWN, MODAL, SELECT2 */
    .dropdown-menu {
      background: var(--samco-card-bg);
      border: 1px solid var(--samco-border-weak);
      color: var(--samco-text-main);
    }
    body.samco-dark .dropdown-menu, .samco-dark .dropdown-menu {
      background: var(--samco-card-bg);
      border: 1px solid var(--samco-border-weak);
    }

    .select2-container--default .select2-selection--single {
      background: transparent;
      border: 1px solid var(--samco-border-weak);
      color: var(--samco-text-main);
    }

    /* AVATAR */
    .avatar.bg-light-primary { background: rgba(220,53,69,0.08); color:var(--samco-primary); }
    body.samco-dark .avatar.bg-light-primary { background: rgba(220,53,69,0.08); color:var(--samco-primary); }

    /* small responsive tweaks */
    @media (max-width:1199.98px){
      .header-navbar .navbar-container{ padding:0 .9rem; gap:.75rem; }
      body.vertical-layout .app-content.content{ padding-top:60px !important; margin-left:260px !important; }
      .app-content .content-wrapper{ padding:.75rem 1rem 1.5rem 1rem !important; }
      .top-search{ width:210px; }
      .top-clock{ display:none; }
    }
    @media (max-width:767.98px){
      .top-search{ display:none !important; }
    }
  </style>

  @stack('styles')
</head>

<body class="vertical-layout vertical-menu-modern navbar-floating footer-static" data-open="click">

  {{-- HEADER --}}
  <nav class="header-navbar navbar navbar-expand-lg navbar-light navbar-shadow">
    <div class="navbar-container">

      <div class="top-left-wrapper">
        <div class="samco-menu-toggle" id="samco-menu-toggle">
          <i class="ficon" data-feather="menu"></i>
        </div>

        <a href="{{ $brandHome }}" class="app-brand-wrapper">
          <div class="samco-logo-badge">
            <img src="{{ asset('app-assets/images/logo/Samco.png') }}" alt="Logo">
          </div>
          <div class="app-brand-texts">
            <span class="app-brand-title">SAMCO FARMA</span>
            <span class="app-brand-subtitle">Production Tracking System</span>
          </div>
        </a>
      </div>

      <div class="top-right-wrapper">

        {{-- SEARCH MENU --}}
        <div class="top-search">
          <i data-feather="search" class="top-search-icon"></i>
          <input type="text" id="menu-search" class="top-search-input" placeholder="Cari menu & modul..." autocomplete="off">
          <div class="top-search-result" id="menu-search-result"></div>
        </div>

        {{-- CLOCK --}}
        <div class="top-clock" id="samco-clock" title="Waktu Asia/Jakarta">
          <span class="clock-time" id="samco-clock-time">--:--:--</span>
        </div>

        {{-- THEME TOGGLE --}}
        <button class="top-icon-btn" id="theme-toggle" type="button" title="Toggle theme">
          <i data-feather="moon" class="theme-icon-moon"></i>
          <i data-feather="sun" class="theme-icon-sun"></i>
        </button>

        {{-- NOTIFICATION --}}
        <div class="dropdown">
          <button class="top-icon-btn" id="dropdown-notif" data-bs-toggle="dropdown" aria-expanded="false" title="Notifikasi Progress Batch">
            <i data-feather="bell"></i>
            @if($notifCount > 0)
              <span class="top-icon-badge">{{ $notifCount }}</span>
            @endif
          </button>

          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-notif" style="min-width:360px;">
            <h6 class="dropdown-header">Batch Notifications</h6>

            @if($notifCount === 0)
              <div class="px-2 py-1 text-muted" style="font-size:.8rem;">
                Tidak ada notifikasi progress batch saat ini.
              </div>
            @else
              @foreach($notifItems as $n)
                <a class="dropdown-item" href="{{ $n['href'] ?? '#' }}" style="white-space:normal;">
                  <i class="me-50" data-feather="{{ $n['icon'] ?? 'bell' }}"></i>
                  {{ $n['text'] ?? '-' }}
                </a>
              @endforeach
              <div class="dropdown-divider"></div>
              <a class="dropdown-item text-center" href="{{ route('dashboard') }}">
                <i class="me-50" data-feather="home"></i> Buka Dashboard
              </a>
            @endif
          </div>
        </div>

        {{-- USER MENU --}}
        <ul class="nav navbar-nav align-items-center">
          <li class="nav-item dropdown dropdown-user">
            <a class="nav-link dropdown-toggle dropdown-user-link" id="dropdown-user"
               href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <div class="user-nav d-sm-flex d-none">
                <span class="user-name fw-bolder">{{ $user->name }}</span>
                <span class="user-status">
                  {{ $user->role }}
                  @if($isProduksi && $prodLevel)
                    • {{ $prodLevel }}
                  @endif
                </span>
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

  {{-- SIDEBAR --}}
  <div class="main-menu menu-fixed menu-accordion menu-shadow">
    <div class="navbar-header">
      <ul class="nav navbar-nav flex-row">
        <li class="nav-item me-auto">
          <a class="navbar-brand" href="{{ $brandHome }}">
            <span class="samco-logo-badge" style="width:30px;height:30px;border-radius:9px;box-shadow:none;">
              <img src="{{ asset('app-assets/images/logo/Samco.png') }}" alt="">
            </span>
            <h2 class="brand-text mb-0">SAMCO FARMA</h2>
          </a>
        </li>
      </ul>
    </div>

    <div class="main-menu-content">
      <ul class="navigation navigation-main" id="main-menu-navigation">

        {{-- DASHBOARD --}}
        <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
          <a class="d-flex align-items-center" href="{{ route('dashboard') }}">
            <i data-feather="home"></i>
            <span class="menu-title text-truncate">Dashboard</span>
          </a>
        </li>

        {{-- USER MANAGEMENT --}}
        @if ($canManageUsersProduksi)
          <li class="navigation-header"><span>USER MANAGEMENT</span></li>

          @php
            $userMgmtOpen = request()->is('show-produksi*')
              || ($isAdmin && (request()->is('show-ppic*') || request()->is('show-qc*') || request()->is('show-qa*')));
          @endphp

          <li class="nav-item has-sub {{ $userMgmtOpen ? 'open' : '' }}">
            <a class="d-flex align-items-center" href="#">
              <i data-feather="user"></i>
              <span class="menu-title text-truncate">User</span>
            </a>
            <ul class="menu-content">
              @if(\Illuminate\Support\Facades\Route::has('show-produksi'))
                <li class="{{ request()->is('show-produksi*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('show-produksi') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">Produksi</span>
                  </a>
                </li>
              @endif

              @if($isAdmin)
                @if(\Illuminate\Support\Facades\Route::has('show-ppic'))
                  <li class="{{ request()->is('show-ppic*') ? 'active' : '' }}">
                    <a class="d-flex align-items-center" href="{{ route('show-ppic') }}">
                      <i data-feather="circle"></i>
                      <span class="menu-item text-truncate">PPIC</span>
                    </a>
                  </li>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('show-qc'))
                  <li class="{{ request()->is('show-qc*') ? 'active' : '' }}">
                    <a class="d-flex align-items-center" href="{{ route('show-qc') }}">
                      <i data-feather="circle"></i>
                      <span class="menu-item text-truncate">OPERATOR</span>
                    </a>
                  </li>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('show-qa'))
                  <li class="{{ request()->is('show-qa*') ? 'active' : '' }}">
                    <a class="d-flex align-items-center" href="{{ route('show-qa') }}">
                      <i data-feather="circle"></i>
                      <span class="menu-item text-truncate">QA</span>
                    </a>
                  </li>
                @endif
              @endif
            </ul>
          </li>
        @endif

        {{-- MASTER DATA --}}
        @if($canSeeMasterProdukProduksi)
          <li class="navigation-header"><span>MASTER DATA</span></li>

          <li class="nav-item {{ request()->routeIs('produksi.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('produksi.index') }}">
              <i data-feather="box"></i>
              <span class="menu-title text-truncate">Master Produk Produksi</span>
            </a>
          </li>
        @endif

        {{-- PRODUKSI --}}
        @if($showProduksiSection)
          <li class="navigation-header"><span>PRODUKSI</span></li>

          <li class="nav-item has-sub {{ $isProduksiMenuActive ? 'active open' : '' }}">
            <a class="d-flex align-items-center" href="#">
              <i data-feather="activity"></i>
              <span class="menu-title text-truncate">Proses Produksi</span>
            </a>

            <ul class="menu-content">
              @if($isAdmin || $isProduksi)
                <li class="{{ request()->routeIs('show-permintaan') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('show-permintaan') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">Upload WO (Jadwal Produksi)</span>
                  </a>
                </li>

                <li class="{{ request()->routeIs('weighing.*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('weighing.index') }}">
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

                <li class="{{ request()->routeIs('tableting.*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('tableting.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">Tableting</span>
                  </a>
                </li>

                <li class="{{ request()->routeIs('capsule-filling.*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('capsule-filling.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">Capsule Filling</span>
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
              @endif

              @if($canSeeQcMenusInProses)
                <li class="{{ request()->routeIs('qc-granul.*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('qc-granul.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">Produk Antara Granul </span>
                  </a>
                </li>

                <li class="{{ request()->routeIs('qc-tablet.*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('qc-tablet.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">Produk Antara Tablet </span>
                  </a>
                </li>

                <li class="{{ request()->routeIs('qc-ruahan.*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('qc-ruahan.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">Produk Ruahan </span>
                  </a>
                </li>

                <li class="{{ request()->routeIs('qc-ruahan-akhir.*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('qc-ruahan-akhir.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">Produk Ruahan Akhir </span>
                  </a>
                </li>
              @endif

              @if($isAdmin || $isProduksi || $isQC || $isQA)
                <li class="{{ request()->routeIs('holding.*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('holding.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">Holding</span>
                  </a>
                </li>

                @if(\Illuminate\Support\Facades\Route::has('tracking-batch.index'))
                  <li class="{{ request()->routeIs('tracking-batch.*') ? 'active' : '' }}">
                    <a class="d-flex align-items-center" href="{{ route('tracking-batch.index') }}">
                      <i data-feather="circle"></i>
                      <span class="menu-item text-truncate">Tracking Batch</span>
                    </a>
                  </li>
                @endif
              @endif

            </ul>
          </li>
        @endif

        {{-- AFTER SECONDARY PACK --}}
        @if($isAdmin || $isQA || $isQC)
          <li class="navigation-header"><span>BARANG SETELAH PACK</span></li>

          <li class="nav-item has-sub {{ $isAfterPackMenuActive ? 'active open' : '' }}">
            <a class="d-flex align-items-center" href="#">
              <i data-feather="archive"></i>
              <span class="menu-title text-truncate">After Secondary Pack</span>
            </a>
            <ul class="menu-content">
              @if(($isAdmin || $isQA) && \Illuminate\Support\Facades\Route::has('qc-jobsheet.index'))
                <li class="{{ request()->is('qc-jobsheet.*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('qc-jobsheet.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">Job Sheet</span>
                  </a>
                </li>
              @endif

              @if(($isAdmin || $isQA || $isQC) && \Illuminate\Support\Facades\Route::has('sampling.index'))
                <li class="{{ request()->routeIs('sampling.*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('sampling.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">Sampling</span>
                  </a>
                </li>
              @endif

              @if(($isAdmin || $isQA) && \Illuminate\Support\Facades\Route::has('coa.index'))
                <li class="{{ request()->routeIs('coa.*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('coa.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">COA</span>
                  </a>
                </li>
              @endif

              @if(($isAdmin || $isQA) && \Illuminate\Support\Facades\Route::has('review.index'))
                <li class="{{ request()->routeIs('review.*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('review.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">Review</span>
                  </a>
                </li>
              @endif

              @if(($isAdmin || $isQA) && \Illuminate\Support\Facades\Route::has('release.index'))
                <li class="{{ request()->routeIs('release.*') ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('release.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate">Release</span>
                  </a>
                </li>
              @endif

            </ul>
          </li>
        @endif

        {{-- GUDANG --}}
        @if($showGudangHeader)
          <li class="navigation-header"><span>Bahan Jadi</span></li>

          @if(!$isPPIC)
            @if($canSeeSecondaryRelease && \Illuminate\Support\Facades\Route::has('gudang-release.index'))
              <li class="nav-item {{ request()->routeIs('gudang-release.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('gudang-release.index') }}">
                  <i data-feather="package"></i>
                  <span class="menu-title text-truncate">Secondary Release</span>
                </a>
              </li>
            @endif

            @if($canSeeSpvReview && \Illuminate\Support\Facades\Route::has('spv.index'))
              <li class="nav-item {{ request()->routeIs('spv.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('spv.index') }}">
                  <i data-feather="file"></i>
                  <span class="menu-title text-truncate">SPV Review</span>
                </a>
              </li>
            @endif
          @endif

          @if($canSeeGoj && \Illuminate\Support\Facades\Route::has('goj.index'))
            <li class="nav-item {{ request()->routeIs('goj.*') ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('goj.index') }}">
                <i data-feather="file-text"></i>
                <span class="menu-title text-truncate">Gudang</span>
              </a>
            </li>
          @endif
        @endif

      </ul>
    </div>
  </div>

  {{-- CONTENT --}}
  <div class="app-content content">
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
  <script src="{{ asset('app-assets/vendors/js/pickers/flatpickr/flatpickr.min.js') }}"></script>

  {{-- Theme JS --}}
  <script src="{{ asset('app-assets/js/core/app.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/customizer.min.js') }}"></script>

  {{-- Page JS --}}
  <script src="{{ asset('app-assets/js/scripts/cards/card-analytics.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/forms/form-select2.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/pages/dashboard-ecommerce.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/pages/app-user-list.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/tables/table-datatables-advanced.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/forms/pickers/form-pickers.min.js') }}"></script>

  {{-- CSRF helper --}}
  <script>
    window.samcoCsrf = function () {
      const el = document.querySelector('meta[name="csrf-token"]');
      return el ? el.getAttribute('content') : '';
    };

    window.samcoFetch = function (url, options = {}) {
      const headers = options.headers || {};
      headers['X-CSRF-TOKEN'] = window.samcoCsrf();
      headers['X-Requested-With'] = 'XMLHttpRequest';
      headers['Accept'] = headers['Accept'] || 'application/json';
      return fetch(url, { ...options, headers, credentials: 'same-origin' });
    };
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const body = document.body;

      // If root had pre-applied samco-dark (via <html> from head script), mirror to body to keep existing CSS logic
      if (document.documentElement.classList.contains('samco-dark') && !body.classList.contains('samco-dark')) {
        body.classList.add('samco-dark');
      }

      // INIT FEATHER
      if (window.feather) window.feather.replace({ width: 16, height: 16 });

      // SIDEBAR FULL
      body.classList.remove('menu-collapsed', 'menu-hide', 'menu-open');
      body.classList.add('menu-expanded');

      // THEME INIT
      const savedTheme = localStorage.getItem('samcoTheme') || (document.documentElement.getAttribute('data-samco-theme') === 'dark' ? 'dark' : 'light');
      if (savedTheme === 'dark') body.classList.add('samco-dark');
      else body.classList.remove('samco-dark');

      const themeBtn = document.getElementById('theme-toggle');
      if (themeBtn) {
        themeBtn.addEventListener('click', function () {
          body.classList.toggle('samco-dark');
          // keep html root in sync for pages that pre-apply class early
          if(body.classList.contains('samco-dark')) {
            document.documentElement.classList.add('samco-dark');
            document.documentElement.setAttribute('data-samco-theme', 'dark');
            localStorage.setItem('samcoTheme', 'dark');
          } else {
            document.documentElement.classList.remove('samco-dark');
            document.documentElement.removeAttribute('data-samco-theme');
            localStorage.setItem('samcoTheme', 'light');
          }
        });
      }

      // CLOCK (Asia/Jakarta)
      const elTime = document.getElementById('samco-clock-time');
      function renderClock(){
        try{
          const now = new Date();
          const fmt = new Intl.DateTimeFormat('id-ID', {
            timeZone: 'Asia/Jakarta',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
          });
          const raw = fmt.format(now);
          if (elTime) elTime.textContent = raw.replace(/\./g, ':');
        }catch(e){
          const now = new Date();
          const pad2 = (n) => String(n).padStart(2,'0');
          if (elTime) elTime.textContent = `${pad2(now.getHours())}:${pad2(now.getMinutes())}:${pad2(now.getSeconds())}`;
        }
      }
      renderClock();
      setInterval(renderClock, 1000);

      // SUBMENU TOGGLE
      const nav = document.getElementById('main-menu-navigation');
      if (nav) {
        nav.querySelectorAll('li.nav-item.has-sub > a').forEach(function (link) {
          link.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const li = this.parentElement;

            nav.querySelectorAll('li.nav-item.has-sub.open').forEach(function (item) {
              if (item !== li) item.classList.remove('open');
            });

            li.classList.toggle('open');
          });
        });
      }

      // BUTTON MENU TOGGLE
      const toggleBtn = document.getElementById('samco-menu-toggle');
      if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
          body.classList.toggle('samco-sidebar-collapsed');
        });
      }

      // MENU SEARCH
      const searchInput  = document.getElementById('menu-search');
      const searchResult = document.getElementById('menu-search-result');
      const menuItems    = [];

      if (nav && searchInput && searchResult) {
        nav.querySelectorAll('a').forEach(function (a) {
          const href = a.getAttribute('href');
          if (!href || href === '#') return;

          const titleEl = a.querySelector('.menu-title, .menu-item');
          let label = titleEl ? titleEl.innerText : a.textContent;
          label = (label || '').trim();
          if (!label) return;

          let section = '';
          let li = a.closest('li');
          while (li && !section) {
            let prev = li.previousElementSibling;
            if (prev && prev.classList && prev.classList.contains('navigation-header')) {
              section = (prev.innerText || '').trim();
              break;
            }
            li = li.parentElement;
          }

          menuItems.push({ label, section, href });
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
            .filter(item => item.label.toLowerCase().includes(q))
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
          if (this.value.trim()) doSearch();
        });

        document.addEventListener('click', function (e) {
          if (!searchResult.contains(e.target) && e.target !== searchInput) {
            searchResult.classList.remove('show');
          }
        });
      }
    });
  </script>

  @stack('scripts')
</body>
</html>