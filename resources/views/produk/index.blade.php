@extends('layouts.app')

@section('content')
@php
  $q        = $q ?? request('q','');
  $kategori = $kategori ?? request('kategori','all');
  $perPage  = $perPage ?? request('per_page', 15);

  $mapWadahLabel = function($v){
    $v = (string)$v;
    return match($v){
      'Btl' => 'Botol',
      'Top' => 'Toples',
      'Dus' => 'Dus',
      default => $v !== '' ? $v : '-'
    };
  };

  // helper format angka
  $nf = function($v){
    return ($v !== null && $v !== '') ? number_format((int)$v,0,',','.') : null;
  };

  // helper render badge target rekon per modul
  $rekonBadges = function($r) use ($nf){
    $items = [
      ['key' => 'WEI',  'val' => $r->target_rekon_weighing        ?? null],
      ['key' => 'MIX',  'val' => $r->target_rekon_mixing          ?? null],
      ['key' => 'TAB',  'val' => $r->target_rekon_tableting       ?? null],
      ['key' => 'CAP',  'val' => $r->target_rekon_capsule_filling ?? null],
      ['key' => 'COAT', 'val' => $r->target_rekon_coating         ?? null],
      ['key' => 'PP',   'val' => $r->target_rekon_primary_pack    ?? null],
      ['key' => 'SP',   'val' => $r->target_rekon_secondary_pack  ?? null],
    ];

    // tampilkan hanya yang ada nilainya, tapi kalau semuanya null -> tampilkan "-"
    $hasAny = false;
    foreach($items as $it){
      if ($it['val'] !== null && $it['val'] !== '') { $hasAny = true; break; }
    }

    if (!$hasAny) {
      return '<span class="text-muted">-</span>';
    }

    $html = '<div class="rekon-wrap">';
    foreach($items as $it){
      $v = $nf($it['val']);
      if ($v === null) continue;

      $html .= '<span class="badge rekon-badge">'.$it['key'].' '.$v.'</span>';
    }
    $html .= '</div>';

    return $html;
  };
@endphp

<style>
  .card-pro{border-radius:16px;border:0;box-shadow:0 12px 32px rgba(15,23,42,.08);}
  .pro-head{padding:18px 20px 10px;border-bottom:0;}
  .pro-body{padding:14px 20px 18px;}
  .pro-title{font-weight:800;margin:0}
  .pro-sub{font-size:.82rem;color:#6b7280;margin-top:2px}

  .filter-wrap{
    background:#f8fafc;border:1px solid #eef2f7;border-radius:14px;
    padding:10px 12px;
    display:flex !important;
    flex-wrap:nowrap !important;
    align-items:flex-end !important;
    gap:8px !important;
    overflow-x:auto !important;
    overflow-y:hidden !important;
    -webkit-overflow-scrolling:touch;
  }
  body.samco-dark .filter-wrap{background:#020617;border:1px solid #1f2937;}
  .filter-wrap > *{flex:0 0 auto !important;}

  .fl{font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin:0 0 4px}
  .filter-block.search{min-width:360px; flex:1 0 360px !important;}
  .filter-block.kategori{min-width:200px;}
  .filter-block.perpage{min-width:130px;}
  .filter-block.actions{min-width:200px;}

  .filter-wrap .btn,
  .filter-wrap a.btn,
  .filter-wrap button.btn{
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    width:max-content !important;
    flex:0 0 auto !important;
    white-space:nowrap !important;
    overflow-wrap:normal !important;
    word-break:keep-all !important;
    hyphens:none !important;
    padding:.42rem .85rem !important;
    line-height:1.1 !important;
  }

  .table-wrap{overflow-x:auto;}
  .table-no-squeeze{width:max-content !important;min-width:100% !important;}

  .table thead th{
    font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;
    white-space:nowrap;
    color:#6b7280;background:#f9fafb;border-bottom-color:#e5e7eb
  }
  body.samco-dark .table thead th{background:#0b1220;border-bottom-color:#1f2937;color:#9ca3af}
  .table tbody td{vertical-align:middle;font-size:.86rem}

  .badge-mini{font-size:.7rem;border-radius:999px;padding:.25rem .6rem;}
  .cell-trunc{max-width:280px}
  .cell-trunc > span{max-width:260px}

  th.th-aksi, td.td-aksi{
    min-width:190px !important;
    width:190px !important;
    white-space:nowrap !important;
  }
  .aksi-wrap{
    display:inline-flex !important;
    flex-wrap:nowrap !important;
    align-items:center !important;
    justify-content:center !important;
    gap:6px !important;
    white-space:nowrap !important;
  }
  .aksi-wrap form{display:inline-flex !important;margin:0 !important;}
  .aksi-wrap .btn{
    min-width:70px !important;
    padding:.30rem .70rem !important;
    line-height:1.1 !important;
  }

  /* ✅ Rekon badge ringkas */
  .rekon-wrap{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
    justify-content:flex-end;
  }
  .rekon-badge{
    font-size:.70rem;
    padding:.22rem .55rem;
    border-radius:999px;
    background:rgba(99,102,241,.12);
    color:#3730a3;
    border:1px solid rgba(99,102,241,.25);
    font-weight:700;
  }
  body.samco-dark .rekon-badge{
    background:rgba(99,102,241,.18);
    color:#c7d2fe;
    border-color:rgba(99,102,241,.28);
  }
</style>

<div class="row">
  <div class="col-12">
    <div class="card card-pro">

      <div class="pro-head">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-1">
          <div>
            <h4 class="pro-title">Master Produk Produksi</h4>
            <div class="pro-sub">Kelola daftar produk, kategori, estimasi qty, target rekon per modul, wadah, dan tipe alur produksi.</div>
          </div>

          <div class="d-flex align-items-end">
            <a href="{{ route('produksi.create') }}" class="btn btn-sm btn-primary" style="white-space:nowrap">
              <i data-feather="plus" class="me-25" style="width:14px;height:14px;"></i> Tambah Produk
            </a>
          </div>
        </div>

        <form method="GET" class="filter-wrap mt-1">
          <div class="filter-block search">
            <div class="fl">Cari Produk</div>
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Cari kode / nama / kategori / bentuk / wadah / tipe alur..."
                   value="{{ $q }}">
          </div>

          <div class="filter-block kategori">
            <div class="fl">Kategori</div>
            <select name="kategori" class="form-select form-select-sm">
              <option value="all" {{ $kategori=='all'?'selected':'' }}>Semua Kategori</option>
              @foreach(($kategoriOptions ?? []) as $kv => $label)
                <option value="{{ $kv }}" {{ (string)$kategori===(string)$kv?'selected':'' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div class="filter-block perpage">
            <div class="fl">Per Halaman</div>
            <select name="per_page" class="form-select form-select-sm">
              @foreach([10,15,25,50,100] as $n)
                <option value="{{ $n }}" {{ (string)$perPage===(string)$n?'selected':'' }}>{{ $n }}/hal</option>
              @endforeach
            </select>
          </div>

          <div class="filter-block actions d-flex" style="gap:8px; flex-wrap:nowrap;">
            <button class="btn btn-sm btn-primary" type="submit">
              <i data-feather="search" class="me-25" style="width:14px;height:14px;"></i> Cari
            </button>

            <a href="{{ route('produksi.index') }}" class="btn btn-sm btn-outline-secondary">
              <i data-feather="refresh-ccw" class="me-25" style="width:14px;height:14px;"></i> Reset
            </a>
          </div>
        </form>
      </div>

      <div class="pro-body">
        @if(session('ok'))
          <div class="alert alert-success mb-1">{{ session('ok') }}</div>
        @endif

        <div class="table-responsive table-wrap">
          <table class="table table-hover align-middle mb-0 table-no-squeeze">
            <thead>
              <tr>
                <th style="width:55px">#</th>
                <th style="width:140px">Kode</th>
                <th>Nama Produk</th>
                <th style="width:140px">Kategori</th>

                <th class="text-end" style="width:110px">Est Qty</th>

                {{-- ✅ diganti jadi per modul (ringkas) --}}
                <th class="text-end" style="width:320px">Target Rekon (Modul)</th>

                <th style="width:110px">Wadah</th>
                <th style="width:150px">Bentuk</th>
                <th style="width:190px">Tipe Alur Produksi</th>
                <th class="text-end" style="width:130px">Leadtime</th>
                <th class="text-end" style="width:120px">Expired</th>

                {{-- NEW: Split --}}
                <th style="width:110px">Split</th>

                <th style="width:90px">Status</th>
                <th style="width:120px">Dibuat</th>
                <th class="text-center th-aksi">Actions</th>
              </tr>
            </thead>

            <tbody>
              @forelse($rows as $i=>$r)
                @php
                  $est    = $r->est_qty;
                  $wadah  = $r->wadah;
                  $dibuat = $r->created_at ?? null;
                @endphp

                <tr>
                  <td>{{ $rows->firstItem() + $i }}</td>
                  <td><span class="fw-semibold">{{ $r->kode_produk ?? '-' }}</span></td>

                  <td class="cell-trunc">
                    <span class="text-truncate d-inline-block">{{ $r->nama_produk ?? '-' }}</span>
                  </td>

                  <td>{{ $r->kategori_produk ?? '-' }}</td>

                  <td class="text-end">
                    {{ $est !== null && $est !== '' ? number_format((float)$est,0,',','.') : '-' }}
                  </td>

                  <td class="text-end">
                    {!! $rekonBadges($r) !!}
                  </td>

                  <td>{{ $mapWadahLabel($wadah) }}</td>

                  <td>{{ $r->bentuk_sediaan ?? '-' }}</td>

                  <td class="cell-trunc">
                    <span class="text-truncate d-inline-block">{{ $r->tipe_alur ?? '-' }}</span>
                  </td>

                  <td class="text-end">{{ $r->leadtime_target ? (int)$r->leadtime_target.' hari' : '-' }}</td>
                  <td class="text-end">{{ $r->expired_years ? (int)$r->expired_years.' thn' : '-' }}</td>

                  {{-- Split column --}}
                  <td>
                    @if((bool)($r->is_split ?? false))
                      <span class="badge bg-info badge-mini">Ya ({{ $r->split_suffix ?? 'Z' }})</span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>

                  <td>
                    @if((bool)($r->is_aktif ?? false))
                      <span class="badge bg-success badge-mini">Aktif</span>
                    @else
                      <span class="badge bg-secondary badge-mini">Nonaktif</span>
                    @endif
                  </td>

                  <td>{{ $dibuat ? \Carbon\Carbon::parse($dibuat)->format('d/m/Y') : '-' }}</td>

                  <td class="text-center td-aksi">
                    <div class="aksi-wrap">
                      <a href="{{ route('produksi.edit',$r->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                      <form action="{{ route('produksi.destroy',$r->id) }}" method="POST"
                            onsubmit="return confirm('Hapus produk ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="15" class="text-center text-muted py-3">Belum ada data produk.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-1">
          {{ $rows->withQueryString()->links() }}
        </div>
      </div>

    </div>
  </div>
</div>
@endsection
