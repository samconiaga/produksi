@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- HEADER --}}
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-0">Release After Secondary Pack</h4>
            <p class="mb-0 text-muted">
              Menampilkan batch yang Qty Batch-nya sudah dikonfirmasi
              dan sudah di-<strong>Release</strong> oleh QA.
            </p>
          </div>

          <div class="d-flex gap-50">
            <a href="{{ route('release.logsheet', request()->query()) }}"
               class="btn btn-sm btn-outline-secondary">
              Lihat Logsheet
            </a>
            <a href="{{ route('release.logsheet.export', request()->query()) }}"
               class="btn btn-sm btn-success">
              Export Logsheet (CSV)
            </a>
          </div>
        </div>

        {{-- FLASH --}}
        @if(session('ok'))
          <div class="alert alert-success m-2">{{ session('ok') }}</div>
        @endif

        {{-- FILTER --}}
        <div class="card-body border-bottom">
          <form class="row g-1" method="GET" action="{{ route('release.index') }}">
            <div class="col-md-3">
              <input type="text" name="q" class="form-control"
                     placeholder="Cari produk / no batch / kode batch..."
                     value="{{ $q ?? '' }}">
            </div>

            <div class="col-md-2">
              <select name="bulan" class="form-control">
                <option value="">Semua Bulan</option>
                @for($i=1;$i<=12;$i++)
                  <option value="{{ $i }}" {{ (string)($bulan ?? '') === (string)$i ? 'selected' : '' }}>
                    {{ sprintf('%02d', $i) }}
                  </option>
                @endfor
              </select>
            </div>

            <div class="col-md-2">
              <input type="number" name="tahun" class="form-control"
                     placeholder="Tahun" value="{{ $tahun ?? '' }}">
            </div>

            <div class="col-md-2">
              <button class="btn btn-outline-primary w-100">Filter</button>
            </div>
          </form>
        </div>

        {{-- TABEL --}}
        <div class="table-responsive">
          <table class="table mb-0 align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Kode Batch</th>
                <th>Nama Produk</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>WO Date</th>
                <th>Qty Batch</th>

                <th>Job Sheet QC</th>
                <th>Sampling</th>
                <th>COA</th>
                <th>Status Review</th>
              </tr>
            </thead>

            <tbody>
            @forelse($rows as $idx => $row)
              <tr>
                <td>{{ $rows->firstItem() + $idx }}</td>
                <td>{{ $row->kode_batch }}</td>
                <td>{{ $row->produksi->nama_produk ?? $row->nama_produk }}</td>
                <td>{{ $row->bulan }}</td>
                <td>{{ $row->tahun }}</td>
                <td>{{ $row->wo_date ? \Carbon\Carbon::parse($row->wo_date)->format('d-m-Y') : '' }}</td>
                <td>{{ $row->qty_batch ?? '-' }}</td>

                {{-- indikator proses sebelumnya --}}
                <td>{{ $row->status_jobsheet ?? '-' }}</td>
                <td>{{ $row->status_sampling ?? '-' }}</td>
                <td>{{ $row->status_coa ?? '-' }}</td>

                <td>
                  <span class="badge badge-light-success">Released</span>
                  @if($row->tgl_review)
                    <br><small class="text-muted">{{ $row->tgl_review }}</small>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="text-center text-muted">
                  Belum ada batch yang di-Release.
                </td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>

        <div class="card-body">
          {{ $rows->links() }}
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
