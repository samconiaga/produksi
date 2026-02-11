@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h4 class="card-title mb-0">Holding</h4>
            <p class="mb-0 text-muted" style="max-width:720px">
              Daftar batch yang sedang di-HOLD. Gunakan <strong>Release</strong> untuk mengembalikan ke tahap tertentu, atau <strong>Reject</strong> untuk menutup hold (batch hilang dari list).
            </p>
          </div>

          <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('holding.history') }}" class="btn btn-outline-secondary btn-sm">
              Lihat Rekap Log
            </a>
          </div>
        </div>

        @if(session('ok'))
          <div class="alert alert-success m-2">{{ session('ok') }}</div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger m-2">
            <div class="fw-bold mb-50">Validasi gagal:</div>
            <ul class="mb-0">
              @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        {{-- Filter / Search --}}
        <div class="card-body border-bottom">
          <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-6 col-lg-4">
              <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Cari produk / batch / kode...">
            </div>
            <div class="col-auto">
              <button class="btn btn-outline-primary">Filter</button>
            </div>
            <div class="col text-end text-muted small">
              Menampilkan <strong>{{ $rows->count() }}</strong> dari <strong>{{ $rows->total() }}</strong>
            </div>
          </form>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:60px">#</th>
                <th>Produk</th>
                <th>No WO</th>
                <th>Kode Batch</th>
                <th>Hold Ke</th>
                <th>Stage</th>
                <th>Alasan</th>
                <th>Hold At</th>
                <th>Durasi (jalan)</th>
                <th>Total Hold</th>
                <th style="min-width:220px">Aksi</th>
              </tr>
            </thead>

            <tbody>
              @forelse($rows as $i => $row)
                @php
                  $sum = $summary[$row->id] ?? null;

                  $holdNo = (int)($sum->max_hold_no ?? 0);
                  $holdCount = (int)($sum->hold_count ?? 0);
                  $totalClosedSeconds = (int)($sum->total_seconds ?? 0);

                  $open = $row->holdingLogOpen ?? null;
                  $heldAt = $open?->held_at ?? $row->holding_at ?? null;

                  $runningSec = $heldAt ? max(0, now()->diffInSeconds($heldAt)) : 0;

                  // total hold = closed total + running sekarang
                  $totalAll = $totalClosedSeconds + $runningSec;

                  $produkNama = $row->produksi->nama_produk ?? $row->nama_produk ?? '-';
                @endphp

                <tr>
                  <td class="text-muted align-middle">{{ $rows->firstItem() + $i }}</td>

                  <td class="align-middle" style="min-width:180px;">
                    <div class="fw-semibold">{{ $produkNama }}</div>
                    <div class="text-muted small">{{ $row->nama_produk ?? '' }}</div>
                  </td>

                  <td class="align-middle">{{ $row->no_batch }}</td>
                  <td class="align-middle">{{ $row->kode_batch }}</td>

                  <td class="align-middle">
                    <span class="badge bg-light-primary">#{{ $holdNo > 0 ? $holdNo : $holdCount }}</span>
                  </td>

                  <td class="align-middle">{{ $stages[$row->holding_stage] ?? ($row->holding_stage ?? '-') }}</td>
                  <td class="align-middle text-truncate" style="max-width:200px;">{{ $row->holding_reason ?? '-' }}</td>

                  <td class="align-middle">
                    {{ $heldAt ? \Carbon\Carbon::parse($heldAt)->format('d-m-Y H:i') : '-' }}
                  </td>

                  {{-- Live running --}}
                  <td class="align-middle">
                    <span
                      class="badge bg-light-warning js-hold-running"
                      data-held-at="{{ $heldAt ? \Carbon\Carbon::parse($heldAt)->timestamp : '' }}"
                      data-server-now="{{ now()->timestamp }}"
                    >
                      {{ gmdate('H:i:s', $runningSec) }}
                    </span>
                  </td>

                  {{-- Live total --}}
                  <td class="align-middle">
                    <span
                      class="badge bg-light-info js-hold-total"
                      data-held-at="{{ $heldAt ? \Carbon\Carbon::parse($heldAt)->timestamp : '' }}"
                      data-closed="{{ $totalClosedSeconds }}"
                      data-server-now="{{ now()->timestamp }}"
                    >
                      {{ gmdate('H:i:s', $totalAll) }}
                    </span>
                  </td>

                  {{-- ACTIONS --}}
                  <td class="align-middle">
                    <div class="d-flex gap-1 align-items-center">

                      {{-- LOG --}}
                      <a href="{{ route('holding.history', ['batch' => $row->id]) }}"
                         class="btn btn-outline-secondary btn-sm">
                        Log
                      </a>

                      {{-- Release - buka modal (action di-set via data-action) --}}
                      <button
                        type="button"
                        class="btn btn-success btn-sm btn-release-modal"
                        data-action="{{ route('holding.release', $row->id) }}"
                        data-batch-id="{{ $row->id }}"
                        data-batch-name="{{ e($produkNama) }}"
                        data-stage="{{ $row->holding_stage }}"
                      >
                        Release
                      </button>

                      {{-- Reject - buka modal --}}
                      <button
                        type="button"
                        class="btn btn-danger btn-sm btn-reject-modal"
                        data-action="{{ route('holding.reject', $row->id) }}"
                        data-batch-id="{{ $row->id }}"
                        data-batch-name="{{ e($produkNama) }}"
                      >
                        Reject
                      </button>

                    </div>
                    {{-- Optional small note --}}
                    <div class="text-muted small mt-1">Created: {{ $row->created_at?->format('d-m-Y') ?? '-' }}</div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="11" class="text-center text-muted">Tidak ada batch yang sedang HOLD.</td>
                </tr>
              @endforelse
            </tbody>

          </table>
        </div>

        {{-- Pagination --}}
        <div class="card-body">
          <div class="d-flex justify-content-center">
            {{ $rows->withQueryString()->links('pagination::bootstrap-4') }}
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

{{-- Release Modal --}}
<div class="modal fade" id="modalRelease" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form id="formRelease" method="POST" action="">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Release Holding</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="batch_id" id="release_batch_id">

          <div class="mb-3">
            <label class="form-label">Kembalikan ke tahap</label>
            <select name="holding_return_to" id="release_return_to" class="form-control" required>
              <option value="">Pilih tahap...</option>
              @foreach($stages as $k => $lbl)
                <option value="{{ $k }}">{{ $lbl }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Catatan release (opsional)</label>
            <input type="text" name="resolve_reason" class="form-control" id="release_note" placeholder="Catatan release (opsional)">
          </div>

          <div class="mb-0 text-muted small">
            Anda akan melepaskan HOLD dan mengembalikan batch ke tahap yang dipilih.
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">Konfirmasi Release</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="modalReject" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form id="formReject" method="POST" action="">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Reject Holding</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="batch_id" id="reject_batch_id">

          <div class="mb-3">
            <label class="form-label">Alasan reject <span class="text-danger">*</span></label>
            <input type="text" name="resolve_reason" id="reject_reason" class="form-control" placeholder="Alasan reject (wajib)" required>
          </div>

          <div class="mb-0 text-muted small">
            Reject akan menutup HOLD — batch akan hilang dari daftar HOLD.
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Konfirmasi Reject</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Live timer script & modal handler --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // helper
  function pad(n){ return String(n).padStart(2,'0'); }
  function fmt(sec){
    sec = Math.max(0, Math.floor(sec));
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;
    return pad(h) + ':' + pad(m) + ':' + pad(s);
  }

  // keep approximate sync with server when page rendered
  const clientRenderNow = Math.floor(Date.now()/1000);

  function getServerNow(el){
    const serverNow = parseInt(el.dataset.serverNow || '0', 10);
    if (!serverNow) return Math.floor(Date.now()/1000);
    const clientNow = Math.floor(Date.now()/1000);
    const drift = clientNow - clientRenderNow;
    return serverNow + drift;
  }

  // ticking
  function tick(){
    document.querySelectorAll('.js-hold-running').forEach(el=>{
      const heldAt = parseInt(el.dataset.heldAt || '0', 10);
      if (!heldAt) return;
      const now = getServerNow(el);
      el.textContent = fmt(now - heldAt);
    });

    document.querySelectorAll('.js-hold-total').forEach(el=>{
      const heldAt = parseInt(el.dataset.heldAt || '0', 10);
      const closed = parseInt(el.dataset.closed || '0', 10);
      if (!heldAt) {
        // if no running, just show closed
        el.textContent = fmt(closed);
        return;
      }
      const now = getServerNow(el);
      const running = Math.max(0, now - heldAt);
      el.textContent = fmt(closed + running);
    });
  }

  tick();
  setInterval(tick, 1000);

  // Modal handlers (defensive: cek keberadaan bootstrap)
  const modalReleaseEl = document.getElementById('modalRelease');
  const modalRejectEl  = document.getElementById('modalReject');

  let releaseModal = null;
  let rejectModal  = null;

  if (typeof bootstrap !== 'undefined' && modalReleaseEl) {
    releaseModal = new bootstrap.Modal(modalReleaseEl);
  }
  if (typeof bootstrap !== 'undefined' && modalRejectEl) {
    rejectModal = new bootstrap.Modal(modalRejectEl);
  }

  // release buttons
  document.querySelectorAll('.btn-release-modal').forEach(btn=>{
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const action = this.dataset.action || '';
      const batchId = this.dataset.batchId || '';
      const stage = this.dataset.stage || '';

      const form = document.getElementById('formRelease');
      if (form) {
        form.action = action;
        const hid = document.getElementById('release_batch_id'); if (hid) hid.value = batchId;
        const note = document.getElementById('release_note'); if (note) note.value = '';
        const sel = document.getElementById('release_return_to');
        if (sel) sel.value = stage || '';
      }

      if (releaseModal) {
        releaseModal.show();
      } else if (modalReleaseEl) {
        // fallback sederhana jika bootstrap tidak tersedia: toggle kelas
        modalReleaseEl.classList.add('show');
        modalReleaseEl.style.display = 'block';
        modalReleaseEl.removeAttribute('aria-hidden');
      } else {
        console.warn('modalRelease not found');
      }
    });
  });

  // reject buttons
  document.querySelectorAll('.btn-reject-modal').forEach(btn=>{
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const action = this.dataset.action || '';
      const batchId = this.dataset.batchId || '';

      const form = document.getElementById('formReject');
      if (form) {
        form.action = action;
        const hid = document.getElementById('reject_batch_id'); if (hid) hid.value = batchId;
        const reason = document.getElementById('reject_reason'); if (reason) reason.value = '';
      }

      if (rejectModal) {
        rejectModal.show();
      } else if (modalRejectEl) {
        modalRejectEl.classList.add('show');
        modalRejectEl.style.display = 'block';
        modalRejectEl.removeAttribute('aria-hidden');
      } else {
        console.warn('modalReject not found');
      }
    });
  });

  // prevent double submit
  const formRelease = document.getElementById('formRelease');
  if (formRelease) {
    formRelease.addEventListener('submit', function(){
      this.querySelector('button[type="submit"]').setAttribute('disabled','disabled');
    });
  }
  const formReject = document.getElementById('formReject');
  if (formReject) {
    formReject.addEventListener('submit', function(){
      this.querySelector('button[type="submit"]').setAttribute('disabled','disabled');
    });
  }
});
</script>
@endpush

@endsection