@extends('layouts.app')

@section('content')
<section class="app-user">
  <div class="card">

    {{-- HEADER --}}
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
      <div>
        <h4 class="card-title mb-0">Mixing</h4>
        <small class="text-muted">
          STOP wajib isi <strong>Rekon</strong>. PAUSE untuk kendala. HOLD untuk penyimpangan.
        </small>
      </div>

      <a href="{{ route('mixing.history') }}" class="btn btn-sm btn-outline-secondary">
        Riwayat Mixing
      </a>
    </div>

    <div class="card-body">

      {{-- ALERT --}}
      @if(session('success'))
        <div class="alert alert-success py-1 mb-2">{!! session('success') !!}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger py-1 mb-2">
          {{ $errors->first() }}
        </div>
      @endif

      {{-- STYLES KHUSUS UNTUK PAUSE BOX --}}
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
              <th style="width:40px;">#</th>
              <th>Produk</th>
              <th>No WO</th>
              <th>Weighing</th>
              <th class="text-center">Target Rekon (KG)</th>
              <th>Mixing Awal</th>
              <th>Mixing Akhir</th>
              <th>Status</th>
              <th class="text-center" style="width:420px;">Aksi & Alasan PAUSE</th>
            </tr>
          </thead>

          <tbody>
          @forelse($batches as $i => $batch)

          @php
            $produkNama = $batch->produksi->nama_produk ?? '-';
            $kodeBatch  = $batch->kode_batch ?? '-';

            $targetMix = (float) (optional($batch->produksi)->target_rekon_mixing ?? 0);
            $targetMixText = $targetMix > 0 ? rtrim(rtrim(number_format($targetMix, 3, '.', ','), '0'), '.') : '-';

            $pauseStage  = $batch->paused_stage ?? null;
            $isPauseHere = (bool)($batch->is_paused ?? false) && $pauseStage === 'MIXING';
          @endphp

          <tr>
            <td style="width:40px;">{{ $batches->firstItem() + $i }}</td>
            <td class="fw-semibold">{{ $produkNama }}</td>
            <td>{{ $kodeBatch }}</td>
            <td>{{ optional($batch->tgl_weighing)->format('d-m-Y') }}</td>

            <td class="text-center fw-semibold">
              {{ $targetMixText }} @if($targetMixText !== '-') KG @endif
            </td>

            <td style="min-width:150px;">{{ $batch->tgl_mulai_mixing?->format('d-m-Y H:i') ?? '-' }}</td>
            <td style="min-width:150px;">{{ $batch->tgl_mixing?->format('d-m-Y H:i') ?? '-' }}</td>

            <td style="width:110px;">
              @if($batch->is_holding)
                <span class="badge bg-dark">HOLD</span>

              @elseif($isPauseHere)
                <span class="badge bg-warning text-dark">PAUSED</span>

              @elseif($batch->tgl_mixing)
                <span class="badge bg-success">Selesai</span>

              @elseif($batch->tgl_mulai_mixing)
                <span class="badge bg-info">Berjalan</span>

              @else
                <span class="badge bg-secondary">Belum Start</span>
              @endif
            </td>

            <td class="text-center align-top">

            {{-- BELUM START --}}
            @if(!$batch->tgl_mulai_mixing)

              <form action="{{ route('mixing.start', $batch) }}" method="POST" class="d-inline">
                @csrf
                <button class="btn btn-success btn-sm px-3">
                  START
                </button>
              </form>

            {{-- SUDAH START --}}
            @elseif(!$batch->tgl_mixing)

              @if($batch->is_holding)

                <a href="{{ route('holding.index') }}" class="btn btn-dark btn-sm">
                  HOLDING
                </a>

              @elseif($isPauseHere)

                {{-- ===== DISPLAY UNTUK STATUS PAUSED: Resume di kiri (center vertical) + alasan di kanan ===== --}}
                <div class="pause-wrapper">

                  {{-- RESUME area (tetap vertical centered) --}}
                  <div class="resume-area">
                    <form action="{{ route('mixing.resume', $batch) }}" method="POST" class="d-inline">
                      @csrf
                      <button class="btn btn-warning btn-sm">RESUME</button>
                    </form>
                  </div>

                  {{-- Pause box (card kecil, rapi) di kanan --}}
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

                <div class="d-flex justify-content-center gap-1">

                  <button type="button"
                          class="btn btn-danger btn-sm"
                          data-bs-toggle="modal"
                          data-bs-target="#stopModal{{ $batch->id }}">
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
              Tidak ada batch mixing.
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


{{-- 🚨 MODALS — WAJIB DI LUAR TABLE --}}
@foreach($batches as $batch)

@php
$produkNama = $batch->produksi->nama_produk ?? '-';
$kodeBatch  = $batch->kode_batch ?? '-';
$targetMix = (float) (optional($batch->produksi)->target_rekon_mixing ?? 0);
$targetMixText = $targetMix > 0 ? rtrim(rtrim(number_format($targetMix, 3, '.', ','), '0'), '.') : '-';
@endphp


{{-- STOP --}}
<div class="modal fade" id="stopModal{{ $batch->id }}">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="{{ route('mixing.stop', $batch) }}">
      @csrf

      <div class="modal-header">
        <h5 class="modal-title">STOP Mixing</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <div class="fw-semibold">{{ $produkNama }}</div>
        <div class="text-muted small mb-2">Batch: {{ $kodeBatch }}</div>

        <div class="alert alert-light border py-1 small">
          Target: <strong>{{ $targetMixText }}</strong> KG
        </div>

        <label class="form-label">Rekon (KG)</label>

        <div class="input-group">
          <input type="number"
                 name="rekon_qty"
                 class="form-control"
                 step="0.001"
                 required>
          <span class="input-group-text">KG</span>
        </div>

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
    <form class="modal-content" method="POST" action="{{ route('mixing.pause', $batch) }}">
      @csrf

      <div class="modal-header">
        <h5 class="modal-title">Pause Mixing</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <label class="form-label">Alasan Pause</label>
        <textarea name="reason" class="form-control" rows="3" required></textarea>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-warning">Simpan Pause</button>
      </div>

    </form>
  </div>
</div>


{{-- HOLD --}}
<div class="modal fade" id="holdModal{{ $batch->id }}">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="{{ route('mixing.hold', $batch) }}">
      @csrf

      <div class="modal-header">
        <h5 class="modal-title">Hold Mixing</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <label class="form-label">Alasan Hold</label>
        <textarea name="reason" class="form-control" rows="3" required></textarea>

        <label class="form-label mt-2">Catatan</label>
        <textarea name="note" class="form-control" rows="2"></textarea>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-dark">Masukkan ke Holding</button>
      </div>

    </form>
  </div>
</div>

@endforeach

@endsection