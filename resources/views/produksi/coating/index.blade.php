@extends('layouts.app')

@section('content')
@php
  use App\Models\ProduksiBatch;

  $bulanAktif   = $bulan ?? request('bulan', 'all');
  $perPageAktif = request('per_page', $perPage ?? 25);
  $namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
  ];

  $flashBatch =
      session('success_batch')
      ?? session('batch_code')
      ?? session('kode_batch')
      ?? session('batch')
      ?? null;
@endphp

<style>
  /* tombol aksi supaya ukuran konsisten */
  .action-btn-row .btn { min-height:38px; }
  .action-btn-row .btn.flex-fill { display:flex; align-items:center; justify-content:center; }
  .btn-split { border-radius:.5rem; padding:.35rem .75rem; min-width:110px; }
  /* responsive: kalau sempit (mobile) tumpuk tombol */
  @media (max-width: 576px) {
    .action-btn-row { flex-direction: column; }
    .btn-split { width:100%; min-width:unset; }
  }

  /* ===== PAUSE BOX (mirip Mixing) ===== */
  .pause-wrapper {
    display: flex;
    gap: .8rem;
    align-items: center;       /* <-- vertical centering */
    justify-content: center;
    flex-wrap: nowrap;
    width: 100%;
  }
  .resume-area {
    flex: 0 0 130px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .resume-area .btn { min-width: 90px; }
  .pause-box {
    flex: 1 1 auto;
    min-width: 220px;
    max-width: 540px;
    background: #fff;
    border: 1px solid #e6e9ee;
    border-radius: 8px;
    padding: .6rem .75rem;
    box-shadow: 0 6px 12px rgba(15,23,42,.04);
    font-size: .88rem;
    text-align: left;
  }
  .pause-box .title {
    font-weight: 700;
    font-size: .78rem;
    margin-bottom: .25rem;
    color: #374151;
  }
  .pause-box .reason {
    max-height: 4.2rem;
    overflow: auto;
    white-space: pre-wrap;
    word-break: break-word;
    color: #111827;
    line-height: 1.2;
  }
  .pause-box .meta {
    margin-top: .45rem;
    color: #6b7280;
    font-size: .78rem;
  }
  @media (max-width: 992px) {
    .pause-wrapper { flex-direction: column; gap: .5rem; align-items: stretch; }
    .resume-area { flex: 0 0 auto; width: 100%; justify-content: center; }
    .pause-box { min-width: auto; max-width: 100%; }
  }
  /* kecilkan padding tabel agar muat */
  .table td, .table th { vertical-align: middle; }
</style>

<section class="app-user">
  <div class="card">

    <div class="card-header d-flex align-items-start">
      <div>
        <h4 class="card-title mb-0">Coating</h4>
        <small class="text-muted">
          Tekan <strong>START</strong> untuk mulai, <strong>STOP</strong> untuk selesai (wajib isi rekon).
          <strong>PAUSE</strong> untuk kendala lapangan (tetap di halaman ini).
          <strong>HOLD</strong> untuk penyimpangan (pindah ke modul Holding).
        </small>
      </div>

      <a href="{{ route('coating.history') }}" class="btn btn-sm btn-outline-secondary ms-auto">
        Riwayat Coating
      </a>
    </div>

    <div class="card-body">

      {{-- FILTER --}}
      <form method="GET" action="{{ route('coating.index') }}" class="row g-1 g-md-2 align-items-center mb-2">
        <div class="col-12 col-md-4 col-lg-4">
          <input type="text" name="q" class="form-control form-control-sm"
                 placeholder="Cari produk / batch / kode..."
                 value="{{ $search ?? request('q','') }}">
        </div>

        <div class="col-6 col-md-3 col-lg-3">
          <select name="bulan" class="form-select form-select-sm">
            <option value="all" {{ $bulanAktif === 'all' || $bulanAktif === '' ? 'selected' : '' }}>
              Semua Bulan
            </option>
            @foreach($namaBulan as $num => $label)
              <option value="{{ $num }}" {{ (int)$bulanAktif === $num ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-2 col-lg-2">
          <input type="number" name="tahun" class="form-control form-control-sm"
                 placeholder="Tahun" value="{{ $tahun ?? request('tahun') }}">
        </div>

        <div class="col-6 col-md-2 col-lg-2">
          <select name="per_page" class="form-select form-select-sm">
            @foreach([25, 50, 100] as $opt)
              <option value="{{ $opt }}" {{ (int)$perPageAktif === $opt ? 'selected' : '' }}>
                {{ $opt }} / halaman
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-1 col-lg-1 text-end">
          <button class="btn btn-sm btn-primary w-100">Filter</button>
        </div>
      </form>

      {{-- ALERT --}}
      @if(session('success'))
        <div class="alert alert-success py-1 mb-2 d-flex justify-content-between align-items-center flex-wrap gap-1">
          <div>
            {!! session('success') !!}
            @if($flashBatch)
              <span class="ms-50 fw-semibold">({{ $flashBatch }})</span>
            @endif
          </div>
        </div>
      @endif

      @if($errors->any())
        <div class="alert alert-danger py-1 mb-2">{{ $errors->first() }}</div>
      @endif

      {{-- TABLE --}}
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="text-nowrap">
              <th style="width:40px;">#</th>
              <th>Nama Produk</th>
              <th>No WO</th>
              <th>Tableting Selesai</th>
              <th class="text-center" style="width:160px;">Target Coating</th>
              <th>Mulai Coating</th>
              <th>Status</th>
              <th class="text-center" style="width:420px;">Aksi & Alasan PAUSE</th>
            </tr>
          </thead>

          <tbody>
          @forelse($batches as $i => $batch)
            @php
              $produkNama = $batch->produksi->nama_produk ?? $batch->nama_produk ?? '-';
              $kodeBatch  = $batch->kode_batch ?? $batch->no_batch ?? '-';

              $targetCoat = (int) (optional($batch->produksi)->target_rekon_coating ?? 0);
              $targetText = $targetCoat > 0 ? number_format($targetCoat, 0, ',', '.') : '-';

              $started  = !empty($batch->tgl_mulai_coating);
              $finished = !empty($batch->tgl_coating);

              $pauseStage  = $batch->paused_stage ?? null;
              $isPauseHere = (bool)($batch->is_paused ?? false) && $pauseStage === 'COATING';

              // apakah produk mendukung split di master?
              $canSplitInMaster = (bool)(optional($batch->produksi)->is_split ?? false);
              $defaultSuffix = optional($batch->produksi)->split_suffix ?? 'Z';

              // Cek apakah batch sudah pernah "memiliki" hasil split (heuristik sederhana):
              $origNo = (string) ($batch->no_batch ?? '');
              $origKode = (string) ($batch->kode_batch ?? '');

              $hasSplitChild = ProduksiBatch::where(function($q) use ($origNo, $origKode, $batch){
                  if ($origNo !== '') {
                    $q->orWhere('no_batch', 'like', $origNo . '%');
                  }
                  if ($origKode !== '') {
                    $q->orWhere('kode_batch', 'like', $origKode . '%');
                  }
              })->where('id', '<>', $batch->id)->exists();

              // Cek apakah kode batch sudah berakhiran suffix default (mis. Z)
              $normalizedCode = strtolower($kodeBatch);
              $normalizedSuffix = strtolower((string)$defaultSuffix);
              $hasSuffix = $normalizedSuffix !== '' && str_ends_with($normalizedCode, $normalizedSuffix);

              // tampilkan tombol SPLIT hanya jika:
              // - produk mengizinkan split di master
              // - belum ada hasil split child
              // - batch belum start & belum selesai
              // - kode batch belum punya suffix (mis. sudah Z => tidak bisa split)
              $showSplitButton = $canSplitInMaster && !$hasSplitChild && !$started && !$finished && !$hasSuffix;
            @endphp

            <tr>
              <td>{{ $batches->firstItem() + $i }}</td>
              <td class="fw-semibold">{{ $produkNama }}</td>
              <td>{{ $kodeBatch }}</td>
              <td>{{ $batch->tgl_tableting ? $batch->tgl_tableting->format('d-m-Y H:i') : '-' }}</td>

              <td class="text-center">
                @if($targetText === '-')
                  <span class="text-muted">-</span>
                @else
                  <span class="badge bg-light text-dark border" style="font-weight:600;">
                    {{ $targetText }}
                  </span>
                @endif
              </td>

              <td>{{ $batch->tgl_mulai_coating ? $batch->tgl_mulai_coating->format('d-m-Y H:i') : '-' }}</td>

              <td>
                @if($batch->is_holding)
                  <span class="badge bg-dark">HOLD</span>
                @elseif($isPauseHere)
                  <span class="badge bg-warning text-dark">PAUSED</span>
                @elseif($finished)
                  <span class="badge bg-success">Selesai</span>
                @elseif($started)
                  <span class="badge bg-info">Berjalan</span>
                @else
                  <span class="badge bg-secondary">Belum Start</span>
                @endif
              </td>

              <td class="text-center align-top">

                {{-- BELUM START --}}
                @if(!$started && !$finished)
                  @if($showSplitButton)
                    <div class="d-flex action-btn-row gap-1 align-items-center">
                      <button type="button"
                              class="btn btn-outline-primary btn-sm btn-split"
                              data-bs-toggle="modal"
                              data-bs-target="#splitModal{{ $batch->id }}">
                        SPLIT
                      </button>

                      <form method="POST" action="{{ route('coating.start', $batch) }}" style="flex:1; margin:0;">
                        @csrf
                        <button class="btn btn-success btn-sm w-100 flex-fill">START</button>
                      </form>
                    </div>
                  @else
                    <form method="POST" action="{{ route('coating.start', $batch) }}">
                      @csrf
                      <button class="btn btn-success btn-sm w-100">START</button>
                    </form>
                  @endif

                {{-- SUDAH START --}}
                @elseif($started && !$finished)

                  @if($batch->is_holding)
                    <a href="{{ route('holding.index') }}" class="btn btn-dark btn-sm w-100">
                      Lihat di Holding
                    </a>

                  @elseif($isPauseHere)

                    {{-- ===== DISPLAY UNTUK STATUS PAUSED: Resume di kiri + alasan di kanan ===== --}}
                    <div class="pause-wrapper">

                      {{-- RESUME area --}}
                      <div class="resume-area">
                        <form method="POST" action="{{ route('coating.resume', $batch) }}">
                          @csrf
                          <button class="btn btn-warning btn-sm">RESUME</button>
                        </form>
                      </div>

                      {{-- Pause box --}}
                      <div class="pause-box">
                        <div class="title">Alasan PAUSE</div>
                        <div class="reason">{!! nl2br(e($batch->paused_reason ?? '-')) !!}</div>
                        <div class="meta">
                          oleh <strong>{{ $batch->paused_by_name ?? ($batch->paused_by ?? '-') }}</strong>
                          • {{ optional($batch->paused_at)->format('d-m-Y H:i') ?? '-' }}
                        </div>
                      </div>

                    </div>

                  @else
                    {{-- berjalan: STOP / PAUSE / HOLD sejajar --}}
                    <div class="d-flex action-btn-row gap-1">
                      <button type="button"
                              class="btn btn-danger btn-sm flex-fill"
                              data-bs-toggle="modal"
                              data-bs-target="#stopRekonModal{{ $batch->id }}">
                        STOP
                      </button>

                      <button type="button"
                              class="btn btn-outline-warning btn-sm flex-fill"
                              data-bs-toggle="modal"
                              data-bs-target="#pauseModal{{ $batch->id }}">
                        PAUSE
                      </button>

                      <button type="button"
                              class="btn btn-outline-dark btn-sm flex-fill"
                              data-bs-toggle="modal"
                              data-bs-target="#holdModal{{ $batch->id }}">
                        HOLD
                      </button>
                    </div>
                  @endif

                @else
                  <span class="badge bg-success w-100">SELESAI</span>
                @endif

              </td>
            </tr>

          @empty
            <tr>
              <td colspan="8" class="text-center text-muted">Tidak ada data Coating.</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

    </div>

    <div class="card-body">
      <div class="d-flex justify-content-center">
        {{ $batches->withQueryString()->links('pagination::bootstrap-4') }}
      </div>
    </div>

  </div>
</section>

{{-- MODALS (STOP, PAUSE, HOLD) + SPLIT MODAL --}}
@foreach($batches as $batch)
  @php
    $produkNama = $batch->produksi->nama_produk ?? $batch->nama_produk ?? '-';
    $kodeBatch  = $batch->kode_batch ?? $batch->no_batch ?? '-';
    $targetCoat = (int) (optional($batch->produksi)->target_rekon_coating ?? 0);
    $targetText = $targetCoat > 0 ? number_format($targetCoat, 0, ',', '.') : '-';
    $defaultSuffix = optional($batch->produksi)->split_suffix ?? 'Z';
  @endphp

  {{-- STOP --}}
  <div class="modal fade" id="stopRekonModal{{ $batch->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" method="POST" action="{{ route('coating.stop', $batch) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">STOP Coating + Input Rekon</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-1">
            <div class="fw-semibold">{{ $produkNama }}</div>
            <div class="text-muted small">Batch: {{ $kodeBatch }}</div>
          </div>
          <div class="alert alert-light border py-1 small mb-1">
            Target Coating (Master): <strong>{{ $targetText }}</strong>
          </div>
          <label class="form-label">Rekon <span class="text-danger">*</span></label>
          <input type="number" name="rekon_qty" class="form-control" min="0" step="1" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Simpan & STOP</button>
        </div>
      </form>
    </div>
  </div>

  {{-- PAUSE --}}
  <div class="modal fade" id="pauseModal{{ $batch->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" method="POST" action="{{ route('coating.pause', $batch) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Pause Coating - {{ $kodeBatch }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Alasan Pause <span class="text-danger">*</span></label>
          <textarea name="paused_reason" class="form-control" rows="3" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">Simpan Pause</button>
        </div>
      </form>
    </div>
  </div>

  {{-- HOLD --}}
  <div class="modal fade" id="holdModal{{ $batch->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" method="POST" action="{{ route('coating.hold', $batch) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Hold Coating - {{ $kodeBatch }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Alasan Hold <span class="text-danger">*</span></label>
          <textarea name="holding_reason" class="form-control" rows="3" required></textarea>
          <label class="form-label mt-2">Catatan (opsional)</label>
          <textarea name="holding_note" class="form-control" rows="2"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-dark">Masukkan ke Holding</button>
        </div>
      </form>
    </div>
  </div>

  {{-- SPLIT --}}
  @if(optional($batch->produksi)->is_split ?? false)
    <div class="modal fade" id="splitModal{{ $batch->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('coating.split', $batch) }}">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Split Batch - {{ $kodeBatch }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-1">
              <div class="fw-semibold">{{ $produkNama }}</div>
              <div class="text-muted small">Batch asli: {{ $kodeBatch }}</div>
            </div>

            <div class="alert alert-info small">
              Split akan menggandakan batch menjadi <strong>2</strong> batch. Batch baru akan memiliki kode/no
              yang sama dengan tambahan suffix (default: <strong>{{ $defaultSuffix }}</strong>).
            </div>

            <label class="form-label">Suffix (opsional)</label>
            <input type="text" name="suffix" class="form-control" maxlength="5" value="{{ $defaultSuffix }}">
            <div class="hint small mt-1">Jika dikosongkan, akan memakai default dari master produk ({{ $defaultSuffix }}).</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Konfirmasi & Split</button>
          </div>
        </form>
      </div>
    </div>
  @endif

@endforeach
@endsection
