@extends('layouts.app')

@section('content')
@php
  $isPending = $goj->status === 'PENDING';
@endphp

<style>
  .gd-card{border-radius:16px;border:0;box-shadow:0 12px 32px rgba(15,23,42,.08)}
  .gd-head{padding:18px 20px 10px}
  .gd-body{padding:14px 20px 18px}
  .gd-title{font-size:1.05rem;font-weight:800;margin:0}
  .gd-sub{font-size:.82rem;color:#6b7280;margin:2px 0 0}
  .table-gd thead th{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;background:#f9fafb;border-bottom-color:#e5e7eb;white-space:nowrap}
  .table-gd tbody td{font-size:.82rem;vertical-align:middle}
  .badge-mini{font-size:.68rem;border-radius:999px;padding:.25rem .6rem}
</style>

<div class="row">
  <div class="col-12">
    <div class="card gd-card">
      <div class="gd-head">
        <div class="d-flex flex-column flex-lg-row gap-2 justify-content-between">
          <div>
            <h4 class="gd-title">GOJ Detail – {{ $goj->doc_no }}</h4>
            <div class="gd-sub">
              Tanggal: {{ optional($goj->doc_date)->format('d-m-Y') ?: '-' }}
              @if($goj->status==='REJECTED' && $goj->reject_reason)
                • Alasan: <b>{{ $goj->reject_reason }}</b>
              @endif
            </div>

            @if(!empty($goj->approved_by))
              @php $ga = \App\Models\User::find($goj->approved_by); @endphp
              <div style="margin-top:.4rem;font-size:.85rem;color:#374151">
                Diapprove oleh:
                <strong>{{ $ga?->name ?? $ga?->email ?? '—' }}</strong>
                • {{ \Carbon\Carbon::parse($goj->approved_at)->format('d-m-Y') }}
              </div>
            @endif
          </div>

          <div class="d-flex gap-50 align-items-center">
            {{-- tombol kembali dinamis biar enak --}}
            @if($goj->status === 'PENDING')
              <a href="{{ route('goj.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
            @else
              <a href="{{ route('goj.history', ['status'=>$goj->status]) }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
            @endif

            <a href="{{ route('goj.preview', $goj->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Preview/Print</a>

            @if($goj->status === 'REJECTED')
              <a href="{{ route('gudang-release.lphp', ['status'=>'REJECTED']) }}" class="btn btn-sm btn-outline-danger">Buka LPHP (Rejected)</a>
            @endif

            @php
              $cls = $goj->status==='PENDING' ? 'bg-warning text-dark' : ($goj->status==='APPROVED' ? 'bg-success' : 'bg-danger');
            @endphp
            <span class="badge {{ $cls }} badge-mini">{{ $goj->status }}</span>
          </div>
        </div>
      </div>

      <div class="gd-body">
        @if(session('success'))
          <div class="alert alert-success mb-1">{{ session('success') }}</div>
        @endif

        @if($goj->status === 'REJECTED')
          <div class="alert alert-warning mb-1">
            Dokumen ini <b>REJECTED</b>. Data sudah ditandai masuk antrian <b>LPHP (REJECTED)</b>.
            Halaman ini tidak otomatis pindah—kamu bisa klik tombol <b>Buka LPHP (Rejected)</b> kalau ingin cek.
          </div>
        @endif

        <div class="table-responsive">
          <table class="table table-sm table-hover table-gd">
            <thead>
              <tr>
                <th>#</th>
                <th>Produk</th>
                <th>Batch No</th>
                <th>Kode Batch</th>
                <th>Exp</th>
                <th>Kemasan</th>
                <th>Isi</th>
                <th>Jumlah</th>
                <th>Status Produksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($goj->items as $i => $it)
                <tr>
                  <td>{{ $i+1 }}</td>
                  <td class="fw-semibold">{{ $it->nama_produk }}</td>
                  <td>{{ $it->batch_no }}</td>
                  <td>{{ $it->kode_batch }}</td>
                  <td>{{ optional($it->tgl_expired)->format('d-m-Y') ?: '-' }}</td>
                  <td>{{ $it->kemasan ?: '-' }}</td>
                  <td>{{ $it->isi ?: '-' }}</td>
                  <td>{{ $it->jumlah ?? '-' }}</td>
                  <td>{{ $it->status_gudang ?: '-' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        @if($isPending)
          <div class="d-flex gap-50 align-items-start mt-1">
            <form method="post" action="{{ route('goj.approve', $goj->id) }}">
              @csrf
              <button class="btn btn-success btn-sm" onclick="return confirm('Approve dokumen GOJ ini?')">
                Approve
              </button>
            </form>

            <button class="btn btn-danger btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#modalReject">
              Reject
            </button>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- MODAL REJECT --}}
<div class="modal fade" id="modalReject" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="post" action="{{ route('goj.reject', $goj->id) }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Reject GOJ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Alasan Reject (wajib)</label>
        <textarea name="reason" class="form-control" rows="3" required placeholder="contoh: isi/jumlah tidak sesuai, salah batch, dsb"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-danger">Kirim Reject</button>
      </div>
    </form>
  </div>
</div>
@endsection
