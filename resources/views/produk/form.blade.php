@extends('layouts.app')

@section('content')
@php
  $isEdit = $isEdit ?? false;
  $produk = $produk ?? new \App\Models\Produksi();

  $bentukOptions   = $bentukOptions ?? [];
  $tipeAlurOptions = $tipeAlurOptions ?? [];
  $kategoriOptions = $kategoriOptions ?? [];
  $wadahOptions    = $wadahOptions ?? [];

  if (empty($wadahOptions)) {
    $wadahOptions = [
      'Dus' => 'Dus',
      'Btl' => 'Botol (Btl)',
      'Top' => 'Toples (Top)',
    ];
  }

  $isAktifChecked = (bool) old('is_aktif', $produk->is_aktif);
  $isSplitChecked = (bool) old('is_split', $produk->is_split ?? false);

  $valNum = function($name, $fallback) use ($produk){
    // old dulu, kalau tidak ada pakai model
    return old($name, $fallback);
  };
@endphp

<style>
  .card-pro{border-radius:16px;border:0;box-shadow:0 12px 32px rgba(15,23,42,.08);}
  .pro-head{padding:18px 20px 10px;border-bottom:0;}
  .pro-body{padding:16px 20px 18px;}
  .pro-title{font-weight:800;margin:0}
  .pro-sub{font-size:.82rem;color:#6b7280;margin-top:2px}
  .hint{font-size:.78rem;color:#6b7280;margin-top:6px}

  .rekon-grid{
    display:grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap:10px;
  }
  @media (max-width: 992px){
    .rekon-grid{grid-template-columns: repeat(2, minmax(0, 1fr));}
  }
  .rekon-item label{font-size:.78rem;color:#6b7280;margin-bottom:4px}

  /* layout kecil untuk suffix */
  #split-suffix-wrap{display:flex; align-items:center; gap:8px;}
  #split-suffix-wrap .form-label{margin-bottom:0; margin-right:6px;}
  @media (max-width: 576px){
    #split-suffix-wrap{flex-direction:column; align-items:flex-start;}
  }
</style>

<div class="row">
  <div class="col-12">
    <div class="card card-pro">

      <div class="pro-head">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-1">
          <div>
            <h4 class="pro-title">{{ $isEdit ? 'Edit Produk Produksi' : 'Tambah Produk Produksi' }}</h4>
            <div class="pro-sub">Isi data master produk produksi (termasuk target rekon per modul & wadah).</div>
          </div>

          <div class="d-flex align-items-end">
            <a href="{{ route('produksi.index') }}" class="btn btn-outline-secondary btn-sm" style="white-space:nowrap">
              <i data-feather="arrow-left" class="me-25" style="width:14px;height:14px;"></i> Kembali
            </a>
          </div>
        </div>
      </div>

      <div class="pro-body">
        @if($errors->any())
          <div class="alert alert-danger mb-1">
            <div class="fw-bold mb-25">Validasi gagal:</div>
            <ul class="mb-0">
              @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ $isEdit ? route('produksi.update',$produk->id) : route('produksi.store') }}">
          @csrf
          @if($isEdit) @method('PUT') @endif

          {{-- ROW 1 --}}
          <div class="row g-2">
            <div class="col-md-3">
              <label class="form-label">Kode Produk</label>
              <input type="text"
                     name="kode_produk"
                     class="form-control @error('kode_produk') is-invalid @enderror"
                     value="{{ old('kode_produk', $produk->kode_produk) }}"
                     placeholder="Contoh: 109B / PRD-001">
              @error('kode_produk')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="hint">Isi sesuai master (boleh angka/huruf).</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Nama Produk</label>
              <input type="text"
                     name="nama_produk"
                     class="form-control @error('nama_produk') is-invalid @enderror"
                     value="{{ old('nama_produk', $produk->nama_produk) }}"
                     placeholder="Contoh: Bundavin Dus 100's">
              @error('nama_produk')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
              <label class="form-label">Kategori</label>
              <select name="kategori_produk" class="form-select @error('kategori_produk') is-invalid @enderror">
                <option value="">- Pilih -</option>
                @foreach($kategoriOptions as $kv => $label)
                  <option value="{{ $kv }}" {{ old('kategori_produk', $produk->kategori_produk)==$kv ? 'selected' : '' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
              @error('kategori_produk')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="hint">Contoh: ETHICAL / OTC / TRADISIONAL.</div>
            </div>
          </div>

          {{-- ROW 2 --}}
          <div class="row g-2 mt-1">
            <div class="col-md-3">
              <label class="form-label">Est Qty</label>
              <input type="number"
                     name="est_qty"
                     class="form-control @error('est_qty') is-invalid @enderror"
                     value="{{ old('est_qty', $produk->est_qty) }}"
                     min="0" placeholder="0">
              @error('est_qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="hint">Estimasi qty dari master (opsional).</div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Bentuk Sediaan</label>
              <select name="bentuk_sediaan" class="form-select @error('bentuk_sediaan') is-invalid @enderror">
                <option value="">- Pilih -</option>
                @foreach($bentukOptions as $b)
                  <option value="{{ $b }}" {{ old('bentuk_sediaan', $produk->bentuk_sediaan)===$b ? 'selected' : '' }}>
                    {{ $b }}
                  </option>
                @endforeach
              </select>
              @error('bentuk_sediaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
              <label class="form-label">Wadah</label>
              <select name="wadah" class="form-select @error('wadah') is-invalid @enderror">
                <option value="">- (Opsional) -</option>
                @foreach($wadahOptions as $kv => $label)
                  <option value="{{ $kv }}" {{ old('wadah', $produk->wadah)===$kv ? 'selected' : '' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
              @error('wadah')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="hint">Contoh: Dus / Btl / Top.</div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Tipe Alur Produksi</label>
              <select name="tipe_alur" class="form-select @error('tipe_alur') is-invalid @enderror">
                <option value="">- Pilih -</option>
                @foreach($tipeAlurOptions as $kv => $label)
                  <option value="{{ $kv }}" {{ old('tipe_alur', $produk->tipe_alur)===$kv ? 'selected' : '' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
              @error('tipe_alur')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          {{-- TARGET REKON PER MODUL --}}
          <div class="mt-2">
            <div class="fw-semibold mb-50">Target Rekon per Modul</div>
            <div class="text-muted small mb-1">Isi angka target rekon sesuai modul yang dipakai produk ini. Yang tidak dipakai boleh kosong.</div>

            <div class="rekon-grid">
              <div class="rekon-item">
                <label class="form-label">WEI (Weighing)</label>
                <input type="number" min="0"
                       name="target_rekon_weighing"
                       class="form-control @error('target_rekon_weighing') is-invalid @enderror"
                       value="{{ old('target_rekon_weighing', $produk->target_rekon_weighing) }}"
                       placeholder="0">
                @error('target_rekon_weighing')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="rekon-item">
                <label class="form-label">MIX (Mixing)</label>
                <input type="number" min="0"
                       name="target_rekon_mixing"
                       class="form-control @error('target_rekon_mixing') is-invalid @enderror"
                       value="{{ old('target_rekon_mixing', $produk->target_rekon_mixing) }}"
                       placeholder="0">
                @error('target_rekon_mixing')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="rekon-item">
                <label class="form-label">TAB (Tableting)</label>
                <input type="number" min="0"
                       name="target_rekon_tableting"
                       class="form-control @error('target_rekon_tableting') is-invalid @enderror"
                       value="{{ old('target_rekon_tableting', $produk->target_rekon_tableting) }}"
                       placeholder="0">
                @error('target_rekon_tableting')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="rekon-item">
                <label class="form-label">CAP (Capsule Filling)</label>
                <input type="number" min="0"
                       name="target_rekon_capsule_filling"
                       class="form-control @error('target_rekon_capsule_filling') is-invalid @enderror"
                       value="{{ old('target_rekon_capsule_filling', $produk->target_rekon_capsule_filling) }}"
                       placeholder="0">
                @error('target_rekon_capsule_filling')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="rekon-item">
                <label class="form-label">COAT (Coating)</label>
                <input type="number" min="0"
                       name="target_rekon_coating"
                       class="form-control @error('target_rekon_coating') is-invalid @enderror"
                       value="{{ old('target_rekon_coating', $produk->target_rekon_coating) }}"
                       placeholder="0">
                @error('target_rekon_coating')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="rekon-item">
                <label class="form-label">PP (Primary Pack)</label>
                <input type="number" min="0"
                       name="target_rekon_primary_pack"
                       class="form-control @error('target_rekon_primary_pack') is-invalid @enderror"
                       value="{{ old('target_rekon_primary_pack', $produk->target_rekon_primary_pack) }}"
                       placeholder="0">
                @error('target_rekon_primary_pack')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="rekon-item">
                <label class="form-label">SP (Secondary Pack)</label>
                <input type="number" min="0"
                       name="target_rekon_secondary_pack"
                       class="form-control @error('target_rekon_secondary_pack') is-invalid @enderror"
                       value="{{ old('target_rekon_secondary_pack', $produk->target_rekon_secondary_pack) }}"
                       placeholder="0">
                @error('target_rekon_secondary_pack')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>

            <div class="hint">Contoh: produk kapsul biasanya isi MIX + CAP, lainnya boleh kosong.</div>
          </div>

          {{-- ROW 3 --}}
          <div class="row g-2 mt-2">
            <div class="col-md-3">
              <label class="form-label">Leadtime Target (hari)</label>
              <input type="number"
                     name="leadtime_target"
                     class="form-control @error('leadtime_target') is-invalid @enderror"
                     value="{{ old('leadtime_target', $produk->leadtime_target) }}"
                     min="0" placeholder="0">
              @error('leadtime_target')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
              <label class="form-label">Expired (tahun)</label>
              <input type="number"
                     name="expired_years"
                     class="form-control @error('expired_years') is-invalid @enderror"
                     value="{{ old('expired_years', $produk->expired_years) }}"
                     min="0" max="20" placeholder="0">
              @error('expired_years')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="hint">Isi dalam tahun (contoh: 4).</div>
            </div>

            <div class="col-md-6 d-flex align-items-end" style="gap:12px; flex-wrap:wrap;">
              {{-- is_aktif --}}
              <input type="hidden" name="is_aktif" value="0">
              <div class="form-check form-switch">
                <input class="form-check-input"
                       type="checkbox"
                       id="is_aktif"
                       name="is_aktif"
                       value="1"
                       {{ $isAktifChecked ? 'checked' : '' }}>
                <label class="form-check-label" for="is_aktif">Aktif</label>
              </div>

              {{-- is_split --}}
              <input type="hidden" name="is_split" value="0">
              <div class="form-check form-switch">
                <input class="form-check-input"
                       type="checkbox"
                       id="is_split"
                       name="is_split"
                       value="1"
                       {{ $isSplitChecked ? 'checked' : '' }}>
                <label class="form-check-label" for="is_split">Bisa Split (Coating)</label>
              </div>

              {{-- split suffix (tampil jika is_split checked) --}}
              <div id="split-suffix-wrap" style="display: {{ $isSplitChecked ? 'flex' : 'none' }};">
                <label class="form-label mb-0">Suffix</label>
                <input type="text"
                       name="split_suffix"
                       id="split_suffix"
                       class="form-control form-control-sm @error('split_suffix') is-invalid @enderror"
                       value="{{ old('split_suffix', $produk->split_suffix ?? 'Z') }}"
                       maxlength="5"
                       style="width:88px"
                       placeholder="Z">
                @error('split_suffix')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="hint ms-1">Huruf/karakter yang ditambahkan saat split, contoh: <code>Z</code></div>
              </div>

              @error('is_aktif')
                <div class="invalid-feedback d-block ms-1">{{ $message }}</div>
              @enderror
            </div>
          </div>

          {{-- ACTIONS --}}
          <div class="mt-3 d-flex gap-1">
            <button class="btn btn-primary" type="submit">
              <i data-feather="save" class="me-25" style="width:14px;height:14px;"></i>
              {{ $isEdit ? 'Update' : 'Simpan' }}
            </button>

            <a href="{{ route('produksi.index') }}" class="btn btn-outline-secondary">
              <i data-feather="x" class="me-25" style="width:14px;height:14px;"></i> Batal
            </a>
          </div>

        </form>
      </div>

    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function(){
    var isSplit = document.getElementById('is_split');
    var wrap = document.getElementById('split-suffix-wrap');
    var suffixInput = document.getElementById('split_suffix');

    if (isSplit) {
      isSplit.addEventListener('change', function(){
        wrap.style.display = this.checked ? 'flex' : 'none';
        // jika baru dicentang dan input kosong, set default 'Z'
        if (this.checked && suffixInput && suffixInput.value.trim() === '') {
          suffixInput.value = 'Z';
        }
      });
    }
  });
</script>
@endsection
