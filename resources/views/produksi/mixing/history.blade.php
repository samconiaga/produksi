@extends('layouts.app')

@section('content')
<section class="app-user">
  <div class="card">

    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="card-title mb-0">Riwayat Mixing</h4>
      <a href="{{ route('mixing.index') }}" class="btn btn-sm btn-outline-secondary">&laquo; Kembali</a>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Produk</th>
              <th>No Batch</th>
              <th>WO Date</th>
              <th>Weighing</th>
              <th>Mulai</th>
              <th>Selesai</th>
            </tr>
          </thead>

          <tbody>

          @forelse($batches as $i => $batch)
            <tr>
              <td>{{ $batches->firstItem() + $i }}</td>
              <td>{{ $batch->nama_produk }}</td>
              <td>{{ $batch->no_batch }}</td>
              <td>{{ optional($batch->wo_date)->format('d-m-Y') }}</td>
              <td>{{ optional($batch->tgl_weighing)->format('d-m-Y') }}</td>

              <td>{{ $batch->tgl_mulai_mixing ? $batch->tgl_mulai_mixing->format('d-m-Y H:i') : '-' }}</td>
              <td>{{ $batch->tgl_mixing ? $batch->tgl_mixing->format('d-m-Y H:i') : '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center">Belum ada riwayat mixing.</td>
            </tr>
          @endforelse

          </tbody>
        </table>
      </div>

      {{ $batches->links() }}
    </div>

  </div>
</section>
@endsection
