{{-- resources/views/secondary_pack/index.blade.php --}}
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
<div class="card-header d-flex align-items-start flex-wrap gap-2">

  <div>
    <h4 class="card-title mb-0">Secondary Pack</h4>
    <small class="text-muted">
      Tekan <strong>START</strong> untuk mulai, <strong>STOP</strong> untuk selesai.
      <strong>PAUSE</strong> tetap di modul ini, <strong>HOLD</strong> pindah ke modul Holding.
      STOP wajib isi <strong>Rekon</strong> (angka biasa). Setelah STOP lanjut input <strong>Qty Batch</strong>.
    </small>
  </div>

  <div class="ms-auto">
    <a href="{{ route('secondary-pack.history') }}" class="btn btn-sm btn-outline-secondary">
      Riwayat Secondary Pack
    </a>
  </div>

</div>

    <div class="card-body">

      {{-- FILTER --}}
      <form method="GET" action="{{ route('secondary-pack.index') }}" class="row g-1 g-md-2 align-items-center mb-2">
        <div class="col-12 col-md-4">
          <input type="text" name="q" class="form-control form-control-sm"
                 placeholder="Cari produk / batch / kode..."
                 value="{{ $q ?? request('q','') }}">
        </div>

        <div class="col-6 col-md-3">
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

        <div class="col-6 col-md-2">
          <input type="number" name="tahun" class="form-control form-control-sm"
                 placeholder="Tahun" value="{{ $tahun ?? request('tahun') }}">
        </div>

        <div class="col-6 col-md-2">
          <select name="per_page" class="form-select form-select-sm">
            @foreach([25, 50, 100] as $opt)
              <option value="{{ $opt }}" {{ (int)$perPageAktif === (int)$opt ? 'selected' : '' }}>
                {{ $opt }} / halaman
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-1 text-end">
          <button class="btn btn-sm btn-primary w-100">Filter</button>
        </div>
      </form>

      {{-- FLASH --}}
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

      {{-- STYLES: resume + pause box (sama seperti Primary/Mixing) --}}
      <style>
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

      {{-- TABLE --}}
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="text-nowrap">
              <th style="width:40px;">#</th>
              <th>Kode Batch</th>
              <th>Nama Produk</th>
              <th>WO Date</th>
              <th>Primary Selesai</th>

              <th class="text-center" style="width:150px;">Wadah</th>

              <th class="text-center" style="width:170px;">
                <div class="d-flex flex-column align-items-center lh-1">
                  <span>Target Rekon</span>
                  <span class="text-muted small">Secondary Pack</span>
                </div>
              </th>

              <th>Secondary Mulai</th>
              <th>Status</th>
              <th class="text-center" style="width:190px;">Aksi</th>
            </tr>
          </thead>

          <tbody>
          @forelse($rows as $idx => $row)
            @php
              $started   = !empty($row->tgl_mulai_secondary_pack_1);
              $finished  = !empty($row->tgl_secondary_pack_1);

              $isPaused    = (bool) ($row->is_paused ?? false);
              $pauseStage  = $row->paused_stage ?? null;
              $isPauseHere = $isPaused && $pauseStage === 'SECONDARY_PACK';

              $badge = 'bg-secondary';
              $text  = 'Belum mulai';
              if ($finished) {
                $badge = 'bg-success'; $text = 'Selesai';
              } elseif ($isPauseHere) {
                $badge = 'bg-warning text-dark'; $text = 'PAUSED';
              } elseif ($started) {
                $badge = 'bg-info'; $text = 'Berjalan';
              }

              $wadah = (string) (optional($row->produksi)->wadah ?? '');
              if ($wadah === '') $wadah = (string) ($row->wadah ?? '-');

              $target = (int) (optional($row->produksi)->target_rekon_secondary_pack ?? 0);
              $targetText = $target > 0 ? number_format($target, 0, '.', ',') : '-';
            @endphp

            <tr>
              <td>{{ $rows->firstItem() + $idx }}</td>
              <td>{{ $row->kode_batch ?? $row->no_batch }}</td>
              <td class="fw-semibold">{{ $row->produksi->nama_produk ?? $row->nama_produk }}</td>
              <td>{{ optional($row->wo_date)->format('d-m-Y') }}</td>
              <td>{{ optional($row->tgl_primary_pack)->format('d-m-Y H:i') }}</td>

              <td class="text-center">
                @if($wadah === '' || $wadah === '-')
                  <span class="text-muted">-</span>
                @else
                  <span class="fw-semibold">{{ $wadah }}</span>
                @endif
              </td>

              <td class="text-center">
                @if($targetText === '-')
                  <span class="text-muted">-</span>
                @else
                  <span class="fw-semibold">{{ $targetText }}</span>
                @endif
              </td>

              <td>
                @if($row->tgl_mulai_secondary_pack_1)
                  {{ $row->tgl_mulai_secondary_pack_1->format('d-m-Y H:i') }}
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>

              <td>
                {{-- STATUS: hanya badge tanpa keterangan alasan --}}
                <span class="badge {{ $badge }}">{{ $text }}</span>
              </td>

              {{-- AKSI --}}
              <td class="text-center">
                @if(!$started && !$finished && !$isPaused)
                  {{-- HANYA START di awal --}}
                  <form action="{{ route('secondary-pack.start', $row) }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm w-100">START</button>
                  </form>

                @elseif($started && !$finished)
                  @if($isPauseHere)
                    {{-- Saat paused: tampilkan RESUME + box alasan di samping --}}
                    <div class="pause-wrapper justify-content-center">
                      <div class="resume-area">
                        <form action="{{ route('secondary-pack.resume', $row) }}" method="POST" class="m-0">
                          @csrf
                          <button type="submit" class="btn btn-warning btn-sm">RESUME</button>
                        </form>
                      </div>

                      <div class="pause-box text-start">
                        <div class="title">Alasan PAUSE</div>
                        <div class="reason">{!! nl2br(e($row->paused_reason ?? '-')) !!}</div>
                        <div class="meta">
                          @if(!empty($row->paused_by_name) || !empty($row->paused_by))
                            oleh <strong>{{ $row->paused_by_name ?? $row->paused_by }}</strong>
                            • {{ optional($row->paused_at)->format('d-m-Y H:i') ?? '-' }}
                          @endif
                        </div>
                      </div>
                    </div>

                  @else
                    {{-- Saat berjalan: STOP / PAUSE / HOLD sejajar --}}
                    <div class="d-flex gap-2">
                      <button type="button"
                              class="btn btn-danger btn-sm flex-fill"
                              data-bs-toggle="modal"
                              data-bs-target="#stopModal{{ $row->id }}">
                        STOP
                      </button>

                      <button type="button" class="btn btn-outline-warning btn-sm flex-fill"
                              data-bs-toggle="modal" data-bs-target="#pauseModal{{ $row->id }}">
                        PAUSE
                      </button>

                      <button type="button" class="btn btn-outline-danger btn-sm flex-fill"
                              data-bs-toggle="modal" data-bs-target="#holdModal{{ $row->id }}">
                        HOLD
                      </button>
                    </div>
                  @endif
                @else
                  <a href="{{ route('secondary-pack.qty.form', $row->id) }}" class="btn btn-outline-primary btn-sm w-100">
                    Lihat / Edit Qty
                  </a>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center text-muted">
                Belum ada data untuk Secondary Pack.
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

    </div>

    {{-- PAGINATION --}}
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

    $wadah = (string) (optional($row->produksi)->wadah ?? '');
    if ($wadah === '') $wadah = (string) ($row->wadah ?? '-');

    $target = (int) (optional($row->produksi)->target_rekon_secondary_pack ?? 0);
    $targetText = $target > 0 ? number_format($target, 0, '.', ',') : '-';
  @endphp

  {{-- STOP MODAL (INPUT REKON) --}}
  <div class="modal fade" id="stopModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" method="POST" action="{{ route('secondary-pack.stop', $row) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">STOP Secondary Pack + Input Rekon</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-start">
          <div class="mb-1">
            <div class="fw-semibold">{{ $produkNama }}</div>
            <div class="text-muted small">Batch: {{ $kode }}</div>
          </div>

          <div class="alert alert-light border py-1 small mb-2">
            <div>Wadah (Master): <strong>{{ $wadah === '' ? '-' : $wadah }}</strong></div>
            <div>Target Rekon Secondary Pack (Master): <strong>{{ $targetText }}</strong></div>
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
            Setelah STOP, otomatis lanjut ke form input Qty Batch.
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Simpan & STOP</button>
        </div>
      </form>
    </div>
  </div>

  {{-- MODAL PAUSE --}}
  <div class="modal fade" id="pauseModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <form class="modal-content" method="POST" action="{{ route('secondary-pack.pause', $row) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Pause Secondary Pack - {{ $kode }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-start">
          <div class="mb-2">
            <label class="form-label">Alasan Pause <span class="text-danger">*</span></label>
            <textarea name="paused_reason" class="form-control" rows="3" required
              placeholder="Contoh: kendala line, change over, mesin trouble, dll"></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">Simpan Pause</button>
        </div>
      </form>
    </div>
  </div>

  {{-- MODAL HOLD --}}
  <div class="modal fade" id="holdModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <form class="modal-content" method="POST" action="{{ route('secondary-pack.hold', $row) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Hold Batch (Secondary Pack) - {{ $kode }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-start">
          <div class="mb-2">
            <label class="form-label">Alasan Hold <span class="text-danger">*</span></label>
            <textarea name="holding_reason" class="form-control" rows="3" required
              placeholder="Contoh: deviasi, investigasi QC/QA, penyimpangan proses, dll"></textarea>
          </div>

          <div class="mb-0">
            <label class="form-label">Catatan (opsional)</label>
            <textarea name="holding_note" class="form-control" rows="2"
              placeholder="Keterangan tambahan..."></textarea>
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
