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
@endphp

<section class="app-user">
  <div class="card">

    {{-- HEADER --}}
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
      <div>
        <h4 class="card-title mb-0">Tableting</h4>
        <small class="text-muted">
          START untuk mulai • STOP wajib rekon • PAUSE kendala • HOLD penyimpangan
        </small>
      </div>

      <a href="{{ route('tableting.history') }}" class="btn btn-sm btn-outline-secondary">
        Riwayat Tableting
      </a>
    </div>

    <div class="card-body">

      {{-- FILTER --}}
      <form method="get" class="row g-1 g-md-2 align-items-center mb-2">
        <div class="col-md-4">
          <input type="text" name="q" class="form-control form-control-sm"
                 placeholder="Cari produk / batch..." value="{{ $search ?? request('q','') }}">
        </div>

        <div class="col-md-3">
          <select name="bulan" class="form-select form-select-sm">
            <option value="all">Semua Bulan</option>
            @foreach($namaBulan as $num => $label)
              <option value="{{ $num }}" {{ (int)$bulanAktif === $num ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-2">
          <input type="number" name="tahun" class="form-control form-control-sm"
                 placeholder="Tahun" value="{{ $tahun ?? request('tahun') }}">
        </div>

        <div class="col-md-2">
          <select name="per_page" class="form-select form-select-sm">
            @foreach([25,50,100] as $opt)
              <option value="{{ $opt }}" {{ (int)$perPageAktif === $opt ? 'selected' : '' }}>
                {{ $opt }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-1">
          <button class="btn btn-primary btn-sm w-100">Filter</button>
        </div>
      </form>

      {{-- ALERT --}}
      @if(session('success'))
        <div class="alert alert-success py-1 mb-2">
          {!! session('success') !!}
          @if($flashBatch)
            <strong>({{ $flashBatch }})</strong>
          @endif
        </div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger py-1 mb-2">
          {{ $errors->first() }}
        </div>
      @endif

      {{-- STYLES KHUSUS UNTUK PAUSE BOX (sama seperti Mixing) --}}
      <style>
        /* wrapper dua kolom: tombol + pause box di samping, vertical-centered */
        .pause-wrapper {
          display: flex;
          gap: .8rem;
          align-items: center;       /* <-- vertical centering */
          justify-content: center;
          flex-wrap: nowrap;
          width: 100%;
        }

        /* area tombol RESUME (fix width supaya tombol tidak melebar) */
        .resume-area {
          flex: 0 0 130px; /* lebar tetap untuk area tombol */
          display: flex;
          align-items: center;
          justify-content: center;
        }

        /* agar tombol tidak tampak mepet di kanan tabel */
        .resume-area .btn { min-width: 90px; }

        /* Box kecil untuk alasan pause (mengisi sisa lebar) */
        .pause-box {
          flex: 1 1 auto; /* mengambil sisa ruang */
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
          max-height: 4.2rem; /* batasi tinggi, muncul scroll jika panjang */
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

        /* kalau layar sempit, kolom aksi ditumpuk agar tidak memecah layout */
        @media (max-width: 992px) {
          .pause-wrapper { flex-direction: column; gap: .5rem; align-items: stretch; }
          .resume-area { flex: 0 0 auto; width: 100%; justify-content: center; }
          .pause-box { min-width: auto; max-width: 100%; }
        }

        /* kecilkan padding tabel agar muat */
        .table td, .table th { vertical-align: middle; }
      </style>

      {{-- TABLE --}}
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light">
            <tr class="text-nowrap">
              <th>#</th>
              <th>Produk</th>
              <th>No WO</th>
              <th>Mixing</th>
              <th class="text-center">Target</th>
              <th>Mulai</th>
              <th>Selesai</th>
              <th>Status</th>
              <th class="text-center" style="width:420px;">Aksi & Alasan PAUSE</th>
            </tr>
          </thead>

          <tbody>
          @forelse($batches as $i => $batch)

          @php
            $produkNama = $batch->produksi->nama_produk ?? '-';
            $kodeBatch  = $batch->kode_batch ?? '-';

            $targetTab = (int) (optional($batch->produksi)->target_rekon_tableting ?? 0);
            $targetTabText = $targetTab > 0 ? number_format($targetTab, 0, ',', '.') : '-';

            $pauseStage  = $batch->paused_stage ?? null;
            $isPauseHere = (bool)($batch->is_paused ?? false) && $pauseStage === 'TABLETING';
          @endphp

          <tr>
            <td>{{ $batches->firstItem() + $i }}</td>
            <td class="fw-semibold">{{ $produkNama }}</td>
            <td>{{ $kodeBatch }}</td>
            <td>{{ $batch->tgl_mixing?->format('d-m-Y H:i') ?? '-' }}</td>

            <td class="text-center fw-semibold">
              {{ $targetTabText }}
            </td>

            <td>{{ $batch->tgl_mulai_tableting?->format('d-m-Y H:i') ?? '-' }}</td>
            <td>{{ $batch->tgl_tableting?->format('d-m-Y H:i') ?? '-' }}</td>

            <td>
              @if($batch->is_holding)
                <span class="badge bg-dark">HOLD</span>
              @elseif($isPauseHere)
                <span class="badge bg-warning text-dark">PAUSED</span>
              @elseif($batch->tgl_tableting)
                <span class="badge bg-success">Selesai</span>
              @elseif($batch->tgl_mulai_tableting)
                <span class="badge bg-info">Berjalan</span>
              @else
                <span class="badge bg-secondary">Belum</span>
              @endif
            </td>

            {{-- ACTION COLUMN: tampilkan seperti Mixing saat PAUSED --}}
            <td class="text-center align-top">

              {{-- BELUM START --}}
              @if(!$batch->tgl_mulai_tableting)

                <form action="{{ route('tableting.start', $batch) }}" method="POST">
                  @csrf
                  <button class="btn btn-success btn-sm px-3">
                    START
                  </button>
                </form>

              {{-- SUDAH START --}}
              @elseif(!$batch->tgl_tableting)

                @if($batch->is_holding)

                  <a href="{{ route('holding.index') }}" class="btn btn-dark btn-sm">
                    HOLDING
                  </a>

                @elseif($isPauseHere)

                  {{-- ===== DISPLAY UNTUK STATUS PAUSED: Resume di kiri (center vertical) + alasan di kanan ===== --}}
                  <div class="pause-wrapper">

                    {{-- RESUME area (tetap vertical centered) --}}
                    <div class="resume-area">
                      <form action="{{ route('tableting.resume', $batch) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-warning btn-sm">RESUME</button>
                      </form>
                    </div>

                    {{-- Pause box (card kecil, rapi) di kanan --}}
                    <div class="pause-box">
                      <div class="title">Alasan PAUSE</div>
                      <div class="reason">{!! nl2br(e($batch->paused_reason ?? ($batch->paused_note ?? '-'))) !!}</div>
                      <div class="meta">
                        oleh <strong>{{ $batch->paused_by_name ?? ($batch->paused_by ?? '-') }}</strong>
                        • {{ optional($batch->paused_at)->format('d-m-Y H:i') ?? '-' }}
                      </div>
                    </div>

                  </div>

                @else

                  <div class="d-flex justify-content-center gap-1 flex-wrap">

                    <button type="button"
                            class="btn btn-danger btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#stopRekonModal{{ $batch->id }}">
                      STOP
                    </button>

                    <button type="button"
                            class="btn btn-outline-warning btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#pauseModal{{ $batch->id }}">
                      PAUSE
                    </button>

                    <button type="button"
                            class="btn btn-outline-dark btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#holdModal{{ $batch->id }}">
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
            <td colspan="9" class="text-center text-muted">
              Tidak ada batch Tableting.
            </td>
          </tr>
          @endforelse
          </tbody>
        </table>
      </div>

      {{-- PAGINATION --}}
      <div class="d-flex justify-content-center mt-2">
        {{ $batches->withQueryString()->links('pagination::bootstrap-4') }}
      </div>

    </div>
  </div>
</section>

{{-- MODALS WAJIB DI LUAR TABLE --}}
@foreach($batches as $batch)

@php
$produkNama = $batch->produksi->nama_produk ?? '-';
$kodeBatch  = $batch->kode_batch ?? '-';
$targetTab  = (int) (optional($batch->produksi)->target_rekon_tableting ?? 0);
$targetTabText = $targetTab > 0 ? number_format($targetTab, 0, ',', '.') : '-';
@endphp

{{-- STOP --}}
<div class="modal fade" id="stopRekonModal{{ $batch->id }}">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="{{ route('tableting.stop', $batch) }}">
      @csrf

      <div class="modal-header">
        <h5 class="modal-title">STOP + Rekon</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <div class="fw-semibold">{{ $produkNama }}</div>
        <div class="text-muted small mb-2">Batch: {{ $kodeBatch }}</div>

        <div class="alert alert-light border py-1 small">
          Target: <strong>{{ $targetTabText }}</strong>
        </div>

        <label>Rekon</label>
        <input type="number"
               name="rekon_qty"
               class="form-control"
               min="0"
               required>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-danger">Simpan & STOP</button>
      </div>

    </form>
  </div>
</div>

{{-- PAUSE --}}
<div class="modal fade" id="pauseModal{{ $batch->id }}">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="{{ route('tableting.pause', $batch) }}">
      @csrf

      <div class="modal-header">
        <h5 class="modal-title">Pause Tableting</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <textarea name="paused_reason"
                  class="form-control"
                  rows="3"
                  required
                  placeholder="Alasan pause..."></textarea>

        <label class="form-label mt-2">Catatan (opsional)</label>
        <textarea name="paused_note" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-warning">Simpan</button>
      </div>

    </form>
  </div>
</div>

{{-- HOLD --}}
<div class="modal fade" id="holdModal{{ $batch->id }}">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="{{ route('tableting.hold', $batch) }}">
      @csrf

      <div class="modal-header">
        <h5 class="modal-title">Hold Tableting</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <textarea name="holding_reason"
                  class="form-control"
                  rows="3"
                  required
                  placeholder="Alasan hold..."></textarea>

        <label class="form-label mt-2">Catatan</label>
        <textarea name="holding_note" class="form-control" rows="2" placeholder="Catatan..."></textarea>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-dark">Hold</button>
      </div>

    </form>
  </div>
</div>

@endforeach

@endsection
