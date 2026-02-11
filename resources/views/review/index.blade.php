@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">

      <div class="card shadow-sm rounded-3">

       {{-- HEADER --}}
<div class="card-header d-flex justify-content-between align-items-center flex-wrap">

    {{-- KIRI --}}
    <div style="max-width:75%">
        <h4 class="card-title mb-1">Review After Secondary Pack</h4>
        <p class="mb-0 text-muted small">
            Menampilkan batch yang <strong>Qty Batch-nya sudah dikonfirmasi</strong>.
            Kolom Job Sheet QC, Sampling, dan COA menunjukkan progres masing-masing.
            Di sini QA dapat melakukan <strong>Hold / Release / Reject</strong>.
        </p>
    </div>

    {{-- KANAN (DIJAMIN NEMPEL) --}}
    <div class="ms-auto">
        <a href="{{ route('review.history') }}"
           class="btn btn-outline-secondary btn-sm">
            Riwayat Review
        </a>
    </div>

</div>


        {{-- FLASH --}}
        <div class="card-body pt-2 pb-1">
          @if (session('ok'))
            <div class="alert alert-success mb-2">{{ session('ok') }}</div>
          @endif
          @if (session('success'))
            <div class="alert alert-success mb-2">{{ session('success') }}</div>
          @endif
          @if ($errors->any())
            <div class="alert alert-danger mb-2">{{ $errors->first() }}</div>
          @endif
        </div>

        {{-- FILTER --}}
        <div class="card-body border-bottom pt-0 pb-3">
          <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
              <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="Cari produk / no batch / kode batch...">
            </div>

            <div class="col-6 col-md-2">
              <select name="bulan" class="form-select form-select-sm">
                <option value="">Semua Bulan</option>
                @for ($i = 1; $i <= 12; $i++)
                  <option value="{{ $i }}" {{ (string)($bulan ?? '') === (string)$i ? 'selected' : '' }}>
                    {{ sprintf('%02d', $i) }}
                  </option>
                @endfor
              </select>
            </div>

            <div class="col-6 col-md-2">
              <input type="number" name="tahun" value="{{ $tahun ?? '' }}" class="form-control form-control-sm" placeholder="Tahun">
            </div>

            <div class="col-6 col-md-2">
              <select name="status" class="form-select form-select-sm">
                <option value="">Semua Status Review (aktif)</option>
                <option value="pending"  {{ ($status ?? '') === 'pending'  ? 'selected' : '' }}>Pending</option>
                <option value="hold"     {{ ($status ?? '') === 'hold'     ? 'selected' : '' }}>Hold</option>
                <option value="released" {{ ($status ?? '') === 'released' ? 'selected' : '' }}>Released</option>
                <option value="rejected" {{ ($status ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
              </select>
            </div>

            <div class="col-6 col-md-2 d-grid">
              <button class="btn btn-sm btn-primary">Filter</button>
            </div>
          </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
              <tr>
                <th style="width:48px">#</th>
                <th>Kode Batch</th>
                <th>Nama Produk</th>
                <th style="width:70px">Bulan</th>
                <th style="width:80px">Tahun</th>
                <th style="width:120px">Qty Batch</th>
                <th style="width:120px">Job Sheet QC</th>
                <th style="width:110px">Sampling</th>
                <th style="width:110px">COA</th>
                <th style="width:140px">Status Review</th>
                <th style="width:160px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              @forelse ($rows as $idx => $row)
                @php
                  $statusReview = strtolower($row->status_review ?? 'pending');
                  if ($statusReview === 'released') { $badge = 'badge bg-success text-white'; $statusText = 'Released'; }
                  elseif ($statusReview === 'hold') { $badge = 'badge bg-warning text-dark'; $statusText = 'Hold'; }
                  elseif ($statusReview === 'rejected') { $badge = 'badge bg-danger text-white'; $statusText = 'Rejected'; }
                  else { $badge = 'badge bg-secondary text-white'; $statusText = 'Pending'; }
                  $isFinal = in_array($statusReview, ['released','rejected']);
                  $indexNo = ($rows->firstItem() ?? 0) + $idx;
                @endphp

                <tr>
                  <td class="fw-medium">{{ $indexNo }}</td>
                  <td class="fw-semibold">{{ $row->kode_batch }}</td>
                  <td>{{ $row->nama_produk }}</td>
                  <td>{{ $row->bulan }}</td>
                  <td>{{ $row->tahun }}</td>

                  {{-- Qty --}}
                  <td>
                    <div class="fw-bold">{{ $row->qty_batch ?? '-' }}</div>
                    <small class="text-muted">{{ $row->status_qty_batch ?? '-' }}</small>
                  </td>

                  {{-- previous steps --}}
                  <td>{{ $row->status_jobsheet ?? '-' }}</td>
                  <td>{{ $row->status_sampling ?? '-' }}</td>
                  <td>{{ $row->status_coa ?? '-' }}</td>

                  {{-- status review --}}
                  <td>
                    <div><span class="{{ $badge }}">{{ $statusText }}</span></div>
                    @if(!empty($row->tgl_review))
                      <small class="text-muted d-block mt-1">{{ $row->tgl_review }}</small>
                    @endif
                  </td>

                  {{-- aksi --}}
                  <td class="text-center">
                    @if ($isFinal)
                      <span class="text-muted small">Tidak ada aksi</span>
                    @else
                      <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false">
                          Aksi QA
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                          {{-- Hold (modal) --}}
                          <li>
                            <button type="button"
                                    class="dropdown-item btn-hold-review text-warning"
                                    data-id="{{ $row->id }}"
                                    data-kode="{{ $row->kode_batch }}"
                                    data-produk="{{ $row->nama_produk }}">
                              Hold
                            </button>
                          </li>

                          {{-- Release --}}
                          <li>
                            <form method="POST" action="{{ route('review.release', $row->id) }}" class="d-inline">
                              @csrf
                              <input type="hidden" name="catatan_review" value="Released oleh QA pada {{ now()->format('d-m-Y') }}">
                              <button type="submit" class="dropdown-item text-success" onclick="return confirm('Release batch ini?')">Release</button>
                            </form>
                          </li>

                          {{-- Reject --}}
                          <li>
                            <form method="POST" action="{{ route('review.reject', $row->id) }}" class="d-inline">
                              @csrf
                              <input type="hidden" name="catatan_review" value="Rejected oleh QA pada {{ now()->format('d-m-Y') }}">
                              <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Yakin REJECT batch ini?')">Reject</button>
                            </form>
                          </li>
                        </ul>
                      </div>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="11" class="text-center text-muted py-4">Belum ada batch yang siap direview.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- PAGINATION --}}
        <div class="card-body pt-3">
          {{ $rows->withQueryString()->links() }}
        </div>

      </div>
    </div>
  </div>
</section>

{{-- MODAL HOLD REVIEW --}}
<div class="modal fade" id="modalHoldReview" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="formHoldReview">
        @csrf

        <div class="modal-header">
          <h5 class="modal-title">Hold Batch</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <p class="mb-2 text-muted" id="holdInfoBatch">—</p>

          <div class="mb-3">
            <label class="form-label">Kembalikan ke</label>
            <div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="return_to" id="return_jobsheet" value="jobsheet" checked>
                <label class="form-check-label" for="return_jobsheet">Job Sheet QC</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="return_to" id="return_coa" value="coa">
                <label class="form-check-label" for="return_coa">COA QC/QA</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="return_to" id="return_both" value="both">
                <label class="form-check-label" for="return_both">Job Sheet &amp; COA</label>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Status Dokumen</label>
            <select name="doc_status" class="form-select">
              <option value="belum_lengkap">Dokumen belum lengkap</option>
              <option value="lengkap">Dokumen lengkap (perlu cek ulang)</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Catatan (opsional)</label>
            <textarea name="catatan_review" class="form-control" rows="3" placeholder="Contoh: Sertakan lampiran hasil analisa COA, tanda tangan QA, dsb."></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" id="btnHoldSave" class="btn btn-warning">Simpan Hold</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- SCRIPT --}}
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.btn-hold-review');
    const modalEl = document.getElementById('modalHoldReview');
    const infoEl  = document.getElementById('holdInfoBatch');
    const form    = document.getElementById('formHoldReview');
    const btnSave = document.getElementById('btnHoldSave');

    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);

    // base route with placeholder; replace 'HOLD_ID_PLACEHOLDER' with actual id.
    const baseActionTemplate = {!! json_encode(route('review.hold', ['batch' => 'HOLD_ID_PLACEHOLDER'])) !!};

    buttons.forEach(btn => {
      btn.addEventListener('click', function () {
        const id     = this.dataset.id;
        const kode   = this.dataset.kode || '';
        const produk = this.dataset.produk || '';

        infoEl.textContent = `Batch ${kode} — ${produk}`;
        form.reset();
        document.getElementById('return_jobsheet').checked = true;

        // set form action dynamic
        form.action = baseActionTemplate.replace('HOLD_ID_PLACEHOLDER', id);

        // show modal
        modal.show();
      });
    });

    // disable button after submit to prevent double submits
    form.addEventListener('submit', function (e) {
      if (btnSave) {
        btnSave.disabled = true;
        btnSave.textContent = 'Menyimpan...';
      }
    });
  });
</script>
@endpush

@endsection
