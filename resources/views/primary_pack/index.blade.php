{{-- resources/views/primary_pack/index.blade.php --}}
@extends('layouts.app')

@section('content')
@php
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

  // cari satu row yang sedang pause di stage PRIMARY_PACK (bila ada) untuk banner atas
  $pausedRow = null;
  if (isset($rows) && method_exists($rows, 'getCollection')) {
      $pausedRow = $rows->getCollection()->first(function($r){
          return !empty($r->is_paused) && ($r->paused_stage ?? '') === 'PRIMARY_PACK';
      });
  }
@endphp

<section class="app-user">
  <div class="card">

    <style>
      .action-btn { min-width: 96px; }
      @media (max-width: 768px) {
        .action-btn { min-width: 72px; padding-left: .75rem; padding-right: .75rem; }
      }

      .modal .modal-header { border-bottom: 1px solid rgba(0,0,0,0.06); padding: 18px 22px; }
      .modal .modal-title { font-weight: 600; font-size: 1.02rem; }
      .modal .modal-body { padding: 16px 22px; }
      .modal .modal-footer { padding: 12px 22px; }

      .btn-pause-save { background: linear-gradient(180deg,#ffb764,#ff9f3b); border-color:#ff9f3b; color:#fff; }
      .btn-hold-submit { background: linear-gradient(180deg,#ff7b7b,#f54b4b); border-color:#f54b4b; color:#fff; }
      .btn-cancel { background:#fff; border:1px solid rgba(0,0,0,0.12); color:#333; }

      .modal textarea.form-control { min-height: 100px; resize: vertical; }

      .btn-outline-warning { color: #d97706; border-color: #facc15; }
      .btn-outline-dark { color: #374151; border-color: rgba(0,0,0,0.12); }

      .pause-banner {
        background-color: #e6f8ef;
        border: 1px solid #c8f0db;
        color: #0f5132;
        padding: .75rem 1rem;
        border-radius: .375rem;
        margin-bottom: .75rem;
        font-weight: 600;
      }

      /* pause layout */
      .pause-wrapper {
        display: flex;
        gap: .8rem;
        align-items: center;
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
      .pause-box .title { font-weight: 700; font-size: .78rem; margin-bottom: .25rem; color: #374151; }
      .pause-box .reason { max-height: 4.2rem; overflow: auto; white-space: pre-wrap; word-break: break-word; color: #111827; line-height: 1.2; }
      .pause-box .meta { margin-top: .45rem; color: #6b7280; font-size: .78rem; }
      @media (max-width: 992px) {
        .pause-wrapper { flex-direction: column; gap: .5rem; align-items: stretch; }
        .resume-area { flex: 0 0 auto; width: 100%; justify-content: center; }
        .pause-box { min-width: auto; max-width: 100%; }
      }

      .table td, .table th { vertical-align: middle; }
    </style>

    <div class="card-header">
      <div class="d-flex w-100 align-items-start">
        <div class="me-3">
          <h4 class="card-title mb-0">Primary Pack</h4>
          <small class="text-muted d-block">
            Tekan <strong>START</strong> untuk mulai, <strong>STOP</strong> untuk selesai.
            <strong>PAUSE</strong> untuk kendala lapangan (tetap di halaman ini).
            <strong>HOLD</strong> untuk penyimpangan (pindah ke modul Holding).
            <span class="ms-50">STOP wajib isi <strong>Rekon</strong> (angka biasa).</span>
          </small>
        </div>

        <div class="ms-auto">
          <a href="{{ route('primary-pack.history') }}" class="btn btn-sm btn-outline-secondary">
            Riwayat Primary Pack
          </a>
        </div>
      </div>
    </div>

    <div class="card-body">

      <form class="row g-1 g-md-2 align-items-center mb-2"
            method="GET"
            action="{{ route('primary-pack.index') }}">

        <div class="col-12 col-md-4 col-lg-4">
          <input type="text"
                 name="q"
                 value="{{ $q ?? request('q','') }}"
                 class="form-control form-control-sm"
                 placeholder="Cari produk / no batch / kode batch...">
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
          <input type="number"
                 name="tahun"
                 value="{{ $tahun ?? request('tahun') }}"
                 class="form-control form-control-sm"
                 placeholder="Tahun">
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

      @if($pausedRow)
        <div class="pause-banner">
          Primary Pack di-PAUSE untuk batch {{ $pausedRow->kode_batch ?? $pausedRow->no_batch ?? ('#'.$pausedRow->id) }}.
          Alasan: {{ $pausedRow->paused_reason ?? '-' }}
          <span class="text-muted"> ({{ $pausedRow->kode_batch ?? $pausedRow->no_batch ?? ('#'.$pausedRow->id) }})</span>
        </div>
      @endif

      @if(session('success') && !$pausedRow)
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

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="text-nowrap">
              <th style="width:40px;">#</th>
              <th>Kode Batch</th>
              <th>Nama Produk</th>
              <th>Bulan</th>
              <th>Tahun</th>
              <th>WO Date</th>

              <th class="text-center" style="width:170px;">
                <div class="d-flex flex-column align-items-center lh-1">
                  <span>Target Rekon</span>
                  <span class="text-muted small">Primary Pack</span>
                </div>
              </th>

              <th>Primary Mulai</th>
              <th>Status</th>
              <th class="text-center" style="width:320px;">Aksi</th>
            </tr>
          </thead>

          <tbody>
          @forelse($rows as $idx => $row)
            @php
              $kode = $row->kode_batch ?? $row->no_batch ?? ('#'.$row->id);

              $started  = !empty($row->tgl_mulai_primary_pack);
              $finished = !empty($row->tgl_primary_pack);

              $isPaused    = (bool) ($row->is_paused ?? false);
              $pauseStage  = $row->paused_stage ?? null;
              $isPauseHere = $isPaused && $pauseStage === 'PRIMARY_PACK';

              $badge = 'bg-secondary';
              $text  = 'Belum mulai';

              if ($finished) {
                $badge = 'bg-success';
                $text  = 'Selesai';
              } elseif ($isPauseHere) {
                $badge = 'bg-warning text-dark';
                $text  = 'PAUSED';
              } elseif ($started) {
                $badge = 'bg-info';
                $text  = 'Berjalan';
              }

              $target = (int) (optional($row->produksi)->target_rekon_primary_pack ?? 0);
              $targetText = $target > 0 ? number_format($target, 0, '.', ',') : '-';
            @endphp

            <tr>
              <td>{{ $rows->firstItem() + $idx }}</td>
              <td>{{ $kode }}</td>
              <td class="fw-semibold">{{ $row->produksi->nama_produk ?? $row->nama_produk }}</td>
              <td>{{ $row->bulan }}</td>
              <td>{{ $row->tahun }}</td>
              <td>{{ optional($row->wo_date)->format('d-m-Y') }}</td>

              <td class="text-center">
                @if($targetText === '-')
                  <span class="text-muted">-</span>
                @else
                  <span class="fw-semibold">{{ $targetText }}</span>
                @endif
              </td>

              <td>
                @if($row->tgl_mulai_primary_pack)
                  {{ $row->tgl_mulai_primary_pack->format('d-m-Y H:i') }}
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>

              <td>
                {{-- HANYA TAMPILKAN BADGE STATUS (tidak menampilkan alasan di sini) --}}
                <span class="badge {{ $badge }}">{{ $text }}</span>
              </td>

              <td class="text-center align-middle">
                @if(!$started && !$finished && !$isPaused)
                  <div class="d-flex justify-content-center">
                    <form action="{{ route('primary-pack.start', $row) }}" method="POST" class="m-0">
                      @csrf
                      <button type="submit" class="btn btn-success btn-sm action-btn">START</button>
                    </form>
                  </div>

                @elseif($started && !$finished)

                  @if($isPauseHere)
                    {{-- PAUSED: tampilkan RESUME + alasan di samping (tidak di kolom status) --}}
                    <div class="pause-wrapper justify-content-center">
                      <div class="resume-area">
                        <form action="{{ route('primary-pack.resume', $row) }}" method="POST" class="m-0">
                          @csrf
                          <button type="submit" class="btn btn-warning btn-sm">RESUME</button>
                        </form>
                      </div>

                      <div class="pause-box text-start">
                        <div class="title">Alasan PAUSE</div>
                        <div class="reason">{!! nl2br(e($row->paused_reason ?? '-')) !!}</div>
                        <div class="meta">
                          oleh <strong>{{ $row->paused_by_name ?? ($row->paused_by ?? '-') }}</strong>
                          • {{ optional($row->paused_at)->format('d-m-Y H:i') ?? '-' }}
                        </div>
                      </div>
                    </div>

                  @else
                    {{-- Normal running: STOP / PAUSE / HOLD sejajar --}}
                    <div class="d-flex justify-content-center gap-2 flex-nowrap">
                      <button type="button"
                              class="btn btn-danger btn-sm action-btn"
                              data-bs-toggle="modal"
                              data-bs-target="#stopModal{{ $row->id }}">
                        STOP
                      </button>

                      <button type="button"
                              class="btn btn-outline-warning btn-sm action-btn"
                              data-bs-toggle="modal"
                              data-bs-target="#pauseModal{{ $row->id }}">
                        PAUSE
                      </button>

                      <button type="button"
                              class="btn btn-outline-dark btn-sm action-btn"
                              data-bs-toggle="modal"
                              data-bs-target="#holdModal{{ $row->id }}">
                        HOLD
                      </button>
                    </div>
                  @endif

                @else
                  <span class="badge bg-success">Selesai</span>
                @endif
              </td>
            </tr>

          @empty
            <tr>
              <td colspan="10" class="text-center text-muted">
                Belum ada data untuk Primary Pack.
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

    </div>

    <div class="card-body">
      <div class="d-flex justify-content-center">
        {{ $rows->withQueryString()->links('pagination::bootstrap-4') }}
      </div>
    </div>

  </div>
</section>

{{-- MODAL DI LUAR TABLE --}}
@foreach($rows as $row)
  @php
    $kode = $row->kode_batch ?? $row->no_batch ?? ('#'.$row->id);
    $produkNama = $row->produksi->nama_produk ?? $row->nama_produk ?? '-';

    $target = (int) (optional($row->produksi)->target_rekon_primary_pack ?? 0);
    $targetText = $target > 0 ? number_format($target, 0, '.', ',') : '-';
  @endphp

  {{-- STOP MODAL --}}
  <div class="modal fade" id="stopModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" method="POST" action="{{ route('primary-pack.stop', $row) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">STOP Primary Pack + Input Rekon</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-start">
          <div class="mb-1">
            <div class="fw-semibold">{{ $produkNama }}</div>
            <div class="text-muted small">Batch: {{ $kode }}</div>
          </div>

          <div class="alert alert-light border py-1 small mb-2">
            Target Rekon Primary Pack :
            <strong>{{ $targetText }}</strong>
          </div>

          <label class="form-label">Rekon <span class="text-danger">*</span></label>
          <input type="number"
                 name="rekon_qty"
                 class="form-control"
                 min="0"
                 step="1"
                 required
                 placeholder="Contoh: 120">

          <div class="small text-muted mt-1">
            Setelah disimpan, batch dianggap selesai Primary Pack.
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Simpan & STOP</button>
        </div>
      </form>
    </div>
  </div>

  {{-- PAUSE MODAL --}}
  <div class="modal fade" id="pauseModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" method="POST" action="{{ route('primary-pack.pause', $row) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Pause Primary Pack - {{ $kode }}</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-start">
          <div class="mb-2">
            <label class="form-label">Alasan Pause <span class="text-danger">*</span></label>
            <textarea name="paused_reason" class="form-control" rows="3" required
              placeholder="Contoh: kendala mesin, kendala line, ganti tooling, dll"></textarea>
          </div>

          <div class="text-muted mt-1" style="font-size:12px;">
            Pause hanya untuk kendala lapangan, batch tetap di modul Primary Pack.
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">Simpan Pause</button>
        </div>
      </form>
    </div>
  </div>

  {{-- HOLD MODAL --}}
  <div class="modal fade" id="holdModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" method="POST" action="{{ route('primary-pack.hold', $row) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Hold Batch (Primary Pack) - {{ $kode }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-start">
          <div class="mb-2">
            <label class="form-label">Alasan Hold <span class="text-danger">*</span></label>
            <textarea name="holding_reason" class="form-control" rows="3" required
              placeholder="Contoh: penyimpangan proses, investigasi QC, deviasi, dll"></textarea>
          </div>

          <div class="mb-0">
            <label class="form-label">Catatan (opsional)</label>
            <textarea name="holding_note" class="form-control" rows="2"
              placeholder="Keterangan tambahan..."></textarea>
          </div>

          <div class="text-muted mt-1" style="font-size:12px;">
            Hold akan memindahkan batch ke modul Holding.
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Kirim ke Holding</button>
        </div>
      </form>
    </div>
  </div>
@endforeach

@endsection
