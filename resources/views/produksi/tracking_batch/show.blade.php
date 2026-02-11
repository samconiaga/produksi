@extends('layouts.app')

@section('content')
@php
  $namaProduk = $batch->produksi->nama_produk ?? '-';
@endphp

@push('styles')
<style>
  .tb-card {
    border: 1px solid #eef1f4;
    border-radius: 8px;
  }
  .tb-status-badge {
    font-size: 12px;
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 600;
    letter-spacing: .3px;
  }
  .tb-meta {
    font-size: 13px;
    color: #6b7280;
  }
  .tb-meta strong {
    color: #374151;
    font-weight: 600;
  }
  .tb-section-title {
    font-size: 15px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 12px;
  }
  .tb-table th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #94a3b8;
    border-bottom: 1px solid #eef1f4;
  }
  .tb-table td {
    vertical-align: middle;
    font-size: 14px;
  }
  .tb-step {
    font-weight: 500;
    color: #374151;
  }
  .tb-time {
    font-size: 12px;
    color: #94a3b8;
  }
  /* optional: small helper for expired text */
  .tb-expired-note {
    font-size: 13px;
    color: #b91c1c; /* red-700 */
    font-weight: 600;
  }
</style>
@endpush

<section class="app-user-list">
  <div class="row">
    <div class="col-12">

      <div class="card tb-card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-0">Detail Tracking Batch</h4>
            <small class="text-muted">
              {{ $namaProduk }} • WO: <strong>{{ $batch->no_batch }}</strong> • Kode: <strong>{{ $batch->kode_batch }}</strong>
            </small>
          </div>
          <a href="{{ route('tracking-batch.index') }}" class="btn btn-sm btn-outline-secondary">&laquo; Kembali</a>
        </div>

        <div class="card-body">

          {{-- STATUS RINGKAS --}}
          <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
            <div>
              <span class="tb-status-badge {{ $track['is_expired'] ? 'bg-danger' : ($track['is_holding'] ? 'bg-danger' : 'bg-info text-dark') }}">
                {{ $track['is_expired'] ? 'EXPIRED' : ($track['is_holding'] ? 'HOLD' : 'ON PROGRESS') }}
              </span>

              <div class="mt-2" style="font-size:16px;font-weight:600;">
                {{ $track['current'] ?? '-' }}
              </div>
              <div class="tb-meta mt-1">
                Sejak: <strong>{{ $track['since_text'] ?? '-' }}</strong> • 
                Lama: <strong>{{ $track['age_text'] ?? '-' }}</strong>
              </div>
            </div>

            <div class="text-end tb-meta" style="min-width:240px">
              <div>WO Date: <strong>{{ $track['wo_text'] ?? '-' }}</strong></div>
              <div>Expected: <strong>{{ $track['expected_text'] ?? '-' }}</strong></div>
              <div class="mt-1">Last Update: <strong>{{ $track['last_text'] ?? '-' }}</strong></div>

              @if($track['is_holding'])
                <div class="mt-2 text-danger">
                  Alasan Hold: {{ $track['hold_reason'] ?? '-' }}
                </div>
              @endif

              @if(!empty($track['is_expired']) && $track['is_expired'])
                <div class="mt-2 tb-expired-note">
                  Melebihi masa simpan: {{ $track['expired_age_text'] ?? '-' }} sejak {{ $track['expired_base_text'] ?? '-' }}
                  @if(!empty($track['expired_limit_months'])) (limit: {{ $track['expired_limit_months'] }} bln) @endif
                </div>
              @endif
            </div>
          </div>

          {{-- TIMELINE --}}
          <div class="tb-section-title">Timeline Proses</div>
          <div class="table-responsive mb-4">
            <table class="table tb-table align-middle">
              <thead>
                <tr>
                  <th style="width:40px">#</th>
                  <th>Step</th>
                  <th class="text-center">Mulai</th>
                  <th class="text-center">Selesai</th>
                  <th class="text-center">Durasi</th>
                  <th class="text-center">Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($track['timeline'] as $i => $row)
                  @php
                    $start = $row['start'];
                    $end   = $row['end'];
                    $status = 'Belum';
                    if ($start && $end) $status = 'Selesai';
                    elseif ($start && !$end && $row['type']==='range') $status = 'On Progress';
                    elseif ($start) $status = 'Done';

                    $dur = '-';
                    if ($start && $end) {
                      $mins = $start->diffInMinutes($end);
                      $dur = $mins >= 60 ? intdiv($mins,60).' jam '.($mins%60).' mnt' : $mins.' mnt';
                    } elseif ($start && !$end && $row['type']==='range') {
                      $mins = $start->diffInMinutes(now());
                      $dur = $mins >= 60 ? intdiv($mins,60).' jam '.($mins%60).' mnt' : $mins.' mnt';
                    }

                    // if whole batch expired and this row not finished, mark status color as danger (optional visual cue)
                    $statusClass = ($status=='Selesai' || $status=='Done') ? 'bg-success' : (($status=='On Progress') ? 'bg-warning text-dark' : 'bg-secondary');
                    if (!empty($track['is_expired']) && $track['is_expired'] && ($status!='Selesai' && $status!='Done')) {
                      $statusClass = 'bg-danger';
                    }
                  @endphp
                  <tr>
                    <td class="text-muted">{{ $i+1 }}</td>
                    <td class="tb-step">{{ $row['label'] }}</td>
                    <td class="text-center">
                      <div>{{ $start? $start->format('d-m-Y'):'-' }}</div>
                      <div class="tb-time">{{ $start? $start->format('H:i'):'-' }}</div>
                    </td>
                    <td class="text-center">
                      <div>{{ $end? $end->format('d-m-Y'):'-' }}</div>
                      <div class="tb-time">{{ $end? $end->format('H:i'):'-' }}</div>
                    </td>
                    <td class="text-center fw-semibold">{{ $dur }}</td>
                    <td class="text-center">
                      <span class="badge {{ $statusClass }}">
                        {{ $status }}
                      </span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          {{-- TABEL REKON TERPISAH --}}
          <div class="tb-section-title">Riwayat Rekonsiliasi</div>
          <div class="table-responsive">
            <table class="table tb-table align-middle">
              <thead>
                <tr>
                  <th>Step</th>
                  <th>Tanggal</th>
                  <th>Oleh</th>
                  <th>REKON</th>
                </tr>
              </thead>
              <tbody>
                @php $hasRekon = false; @endphp
                @foreach($track['timeline'] as $row)
                  @php $rekon = $row['rekon'] ?? null; @endphp
                  @if($rekon && ($rekon['at'] || $rekon['note'] || $rekon['qty']))
                    @php $hasRekon = true; @endphp
                    <tr>
                      <td class="tb-step">{{ $row['label'] }}</td>
                      <td>{{ $rekon['at'] ? $rekon['at']->format('d-m-Y H:i') : '-' }}</td>
                      <td>{{ $rekon['by'] ?? '-' }}</td>
                      <td>{{ $rekon['qty'] ?? ($rekon['note'] ?? '-') }}</td>
                    </tr>
                  @endif
                @endforeach

                @if(!$hasRekon)
                  <tr>
                    <td colspan="4" class="text-center text-muted">Belum ada data rekon</td>
                  </tr>
                @endif
              </tbody>
            </table>
          </div>

        </div>
      </div>

    </div>
  </div>
</section>
@endsection