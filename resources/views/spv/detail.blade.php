@extends('layouts.app')

@section('content')
@php
  use Carbon\Carbon;
  $items = $spvDoc->items ?? collect();
@endphp

<style>
  .cardx{border-radius:16px;border:0;box-shadow:0 12px 32px rgba(15,23,42,.06)}
  .headx{padding:18px 20px 10px}
  .bodyx{padding:14px 20px 18px}
  .titlex{font-size:1.05rem;font-weight:700;margin:0}
  .subx{font-size:.9rem;color:#6b7280;margin:6px 0 0}
  .tablex thead th{font-size:.82rem;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;background:#f9fafb;border-bottom-color:#e6e6ee;white-space:nowrap}
  .tablex tbody td{font-size:.92rem;vertical-align:middle}
  .actions{display:flex;gap:.5rem}
</style>

<div class="row">
  <div class="col-12">
    <div class="card cardx">
      <div class="headx d-flex justify-content-between align-items-start flex-column flex-md-row gap-2">
        <div>
          <h4 class="titlex">SPV Detail – {{ $spvDoc->doc_no }}</h4>
          <div class="subx">
            Tanggal:
            {{ optional($spvDoc->doc_date)->format('d-m-Y') ?? $spvDoc->created_at->format('d-m-Y') }}
          </div>

          @if(!empty($spvDoc->approved_by))
            @php $apv = \App\Models\User::find($spvDoc->approved_by); @endphp
            <div style="margin-top:.4rem;font-size:.85rem;color:#374151">
              Diapprove oleh:
              <strong>{{ $apv?->name ?? $apv?->email ?? '—' }}</strong>
              • {{ Carbon::parse($spvDoc->approved_at)->format('d-m-Y') }}
            </div>
          @endif
        </div>

        <div class="d-flex gap-2 align-items-center">
          <a href="{{ route('spv.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>

          <!-- Tampilkan approve/reject hanya ketika status PENDING -->
          @if(strtoupper($spvDoc->status) === 'PENDING')
            <form method="post" action="{{ route('spv.approve', $spvDoc->id) }}" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-sm btn-success"
                onclick="this.disabled=true; this.form.submit(); return true;"
              >Approve</button>
            </form>

            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalReject">Reject</button>
          @else
            <span class="badge bg-secondary">{{ strtoupper($spvDoc->status) }}</span>
          @endif
        </div>
      </div>

      <div class="bodyx">
        <div class="table-responsive">
          <table class="table table-sm tablex">
            <thead>
              <tr>
                <th style="width:40px">#</th>
                <th>Produk</th>
                <th>Batch No</th>
                <th>Kode Batch</th>
                <th>Exp</th>
                <th>Kemasan</th>
                <th>Isi</th>
                <th class="text-center">Jumlah</th>
                <th>Status SPV</th>
              </tr>
            </thead>
            <tbody>
              @forelse($items as $i => $it)
                <tr>
                  <td>{{ $i+1 }}</td>
                  <td>{{ $it->nama_produk }}</td>
                  <td>{{ $it->batch_no }}</td>
                  <td>{{ $it->kode_batch }}</td>
                  <td>{{ $it->tgl_expired ? Carbon::parse($it->tgl_expired)->format('d-m-Y') : '-' }}</td>
                  <td>{{ $it->kemasan }}</td>
                  <td>{{ $it->isi }}</td>
                  <td class="text-center">{{ $it->jumlah }}</td>
                  <td>{{ $it->status_gudang }}</td>
                </tr>
              @empty
                <tr><td colspan="9" class="text-center text-muted">Tidak ada item.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <!-- HANYA tombol Kembali di bagian bawah (hapus duplicate approve/reject) -->
        <div class="mt-3 d-flex gap-2">
          <a href="{{ route('spv.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Reject modal (tetap ada) -->
<div class="modal fade" id="modalReject" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="post" action="{{ route('spv.reject', $spvDoc->id) }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Reject SPV (kembalikan ke Operator)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Catatan (wajib)</label>
          <textarea name="catatan" class="form-control" rows="4" required placeholder="contoh: qty fisik tidak cocok, kemasan rusak, dsb"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-danger" type="submit">Kirim Reject</button>
      </div>
    </form>
  </div>
</div>
@endsection
