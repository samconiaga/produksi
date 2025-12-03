@extends('layouts.app')

@section('content')
<section class="app-user">
  <div class="card">

    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h4 class="card-title mb-0">Mixing</h4>
        <small class="text-muted">Tekan START untuk mulai, STOP untuk selesai (real-time).</small>
      </div>

      <a href="{{ route('mixing.history') }}" class="btn btn-sm btn-outline-secondary">
        Riwayat Mixing
      </a>
    </div>

    <div class="card-body">

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
      @endif

      {{-- TABEL --}}
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Produk</th>
              <th>No Batch</th>
              <th>Kode</th>
              <th>WO Date</th>
              <th>Weighing</th>
              <th>Mulai Mixing</th>
              <th>Selesai Mixing</th>
              <th>Aksi</th>
            </tr>
          </thead>

          <tbody>
          @forelse($batches as $i => $batch)
            <tr>
              <td>{{ $batches->firstItem() + $i }}</td>
              <td>{{ $batch->nama_produk }}</td>
              <td>{{ $batch->no_batch }}</td>
              <td>{{ $batch->kode_batch }}</td>

              <td>{{ optional($batch->wo_date)->format('d-m-Y') }}</td>
              <td>{{ optional($batch->tgl_weighing)->format('d-m-Y') }}</td>

              <td>{{ $batch->tgl_mulai_mixing ? $batch->tgl_mulai_mixing->format('d-m-Y H:i') : '-' }}</td>
              <td>-</td>

              <td>

                {{-- START BUTTON --}}
                @if(!$batch->tgl_mulai_mixing)
                  <form action="{{ route('mixing.start', $batch) }}" method="POST">
                    @csrf
                    <button class="btn btn-success btn-sm w-100">Start</button>
                  </form>

                {{-- STOP BUTTON --}}
                @elseif(!$batch->tgl_mixing)
                  <form action="{{ route('mixing.stop', $batch) }}" method="POST">
                    @csrf
                    <button class="btn btn-danger btn-sm w-100">Stop</button>
                  </form>
                @endif

              </td>

            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-muted">Tidak ada batch untuk mixing.</td>
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
