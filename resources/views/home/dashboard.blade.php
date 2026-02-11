@extends('layouts.app')

@section('content')
@php
  $year = $year ?? date('Y');
  $fromDate = $fromDate ?? null;
  $toDate = $toDate ?? null;
  $selectedBatch = $selectedBatch ?? null;
  $selectedModule = $selectedModule ?? '_all';

  $moduleKPI = $moduleKPI ?? [];
  $batchSelesaiCount = $batchSelesaiCount ?? 0;
  $batchAktifCount = $batchAktifCount ?? 0;
  $batchInKarantina = $batchInKarantina ?? 0;
  $totalQtyBatch = $totalQtyBatch ?? 0;

  $months = $months ?? collect(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']);
  $barDone = $barDone ?? array_fill(0, count($months), 0);
  $barInProgress = $barInProgress ?? array_fill(0, count($months), 0);
  $barDimusnahkan = $barDimusnahkan ?? array_fill(0, count($months), 0);

  $chartProdukLabels = $chartProdukLabels ?? [];
  $chartProdukData = $chartProdukData ?? [];
  $rekonLabels = $rekonLabels ?? ['Sesuai','Mendekati','Tidak Memenuhi'];
  $rekonData = $rekonData ?? [0,0,0];
  $moduleCols = $moduleCols ?? [];
  $perModuleRekon = $perModuleRekon ?? [];
  $moduleInProgressCounts = $moduleInProgressCounts ?? [];
  $perModuleSummary = $perModuleSummary ?? [];
  $monthlyDone = $monthlyDone ?? [];
  $monthlyTotal = $monthlyTotal ?? [];
  $perModule = $perModule ?? [];
  $batchList = $batchList ?? [];
  $batchStatus = $batchStatus ?? [];
@endphp

<style>
  :root{
    --bg: #f6f8fb; --card: #fff; --muted:#8b96a8; --accent:#1f77b4; --accent-2:#2ca02c; --danger:#d62728; --radius:12px;
  }
  .dash-body{padding:18px 22px;background:var(--bg);min-height:70vh}
  .grid-main{display:grid;grid-template-columns:1fr 420px;gap:18px;align-items:start}
  .kpi-strip{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px;align-items:center}
  .kpi-card{background:var(--card);border-radius:10px;padding:12px 14px;min-width:120px;box-shadow:0 6px 18px rgba(20,30,45,.04);border:1px solid rgba(30,60,90,.04);display:flex;flex-direction:column;align-items:flex-start;gap:4px;}
  .kpi-card .value{font-weight:800;font-size:1.25rem;color:#0f1724}
  .kpi-card .label{font-size:.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em}
  .card-wrap{background:var(--card);border-radius:var(--radius);padding:14px;box-shadow:0 12px 30px rgba(15,23,42,.05);border:1px solid rgba(18,66,110,.03)}
  .card-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
  .card-sub{color:var(--muted);font-size:.85rem}
  .right-sticky{position:sticky;top:18px;display:flex;flex-direction:column;gap:12px}
  .small-muted{color:var(--muted);font-size:.85rem}
  .progress-pill{height:8px;border-radius:6px;background:#eef4fb;overflow:hidden;width:100%}
  .progress-fill{height:100%;border-radius:6px;}
  .batch-table td, .batch-table th{padding:.45rem .5rem}
  @media (max-width:1100px){ .grid-main{grid-template-columns:1fr} .right-sticky{position:static} }
</style>

<div class="dash-body">
  {{-- Header + Filters --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 style="margin:0;font-weight:800">Dashboard</h2>
      <div class="small-muted">Ringkasan produksi — tahun <strong>{{ $year }}</strong></div>
    </div>

    {{-- Filters form (GET) --}}
    <form method="GET" class="d-flex gap-2 align-items-center" style="min-width:420px">
      <div class="d-flex gap-2 align-items-center">
        <label class="small-muted" style="margin-right:6px">From</label>
        <input type="date" name="from" class="form-control form-control-sm" value="{{ old('from', $fromDate) }}">
      </div>
      <div class="d-flex gap-2 align-items-center">
        <label class="small-muted" style="margin-right:6px">To</label>
        <input type="date" name="to" class="form-control form-control-sm" value="{{ old('to', $toDate) }}">
      </div>
      <div>
        <select name="batch" class="form-select form-select-sm">
          <option value="">— All Batches —</option>
          @foreach($batchList as $b)
            <option value="{{ $b['value'] }}" {{ (string)$selectedBatch === (string)$b['value'] ? 'selected' : '' }}>
              {{ $b['label'] }}
            </option>
          @endforeach
        </select>
      </div>
      <div>
        <button class="btn btn-sm btn-primary">Apply</button>
      </div>
    </form>
  </div>

  {{-- KPI strip --}}
  <div class="kpi-strip">
    <div class="kpi-card">
      <div class="value">{{ number_format((int)($moduleKPI['weighing'] ?? 0),0,',','.') }}</div>
      <div class="label">Batch Ditimbang</div>
    </div>

    <div class="kpi-card">
      <div class="value">{{ number_format((int)$batchSelesaiCount,0,',','.') }}</div>
      <div class="label">Batch Completed</div>
    </div>

    <div class="kpi-card">
      <div class="value">{{ number_format((int)$batchAktifCount,0,',','.') }}</div>
      <div class="label">Batch In Progress</div>
    </div>

    <div class="kpi-card">
      <div class="value">{{ number_format((int)$batchInKarantina,0,',','.') }}</div>
      <div class="label">Batch Karantina</div>
    </div>

    <div style="flex:1"></div>

    <div class="kpi-card" style="min-width:220px">
      <div class="small-muted">Total QTY (qty_batch)</div>
      <div class="value" style="text-align:right">{{ number_format((int)$totalQtyBatch,0,',','.') }}</div>
    </div>
  </div>

  {{-- Module pills summary (secondary2 hidden) --}}
  <div class="card-wrap mb-3">
    <div class="card-head">
      <div>
        <h5>Module Summary</h5>
        <div class="card-sub">Jumlah batch yang sudah menyentuh tiap module</div>
      </div>
      <div class="small-muted">Update realtime</div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
      @php
        // NOTE: secondary2 intentionally omitted from UI per request
        $displayOrder = [
          'weighing'=>'Weighing','mixing'=>'Mixing','tableting'=>'Tableting','coating'=>'Coating',
          'capsule_filling'=>'Capsule Filling','primary'=>'Primary Pack','secondary1'=>'Secondary Pack'
        ];
      @endphp
      @foreach($displayOrder as $k => $lbl)
        <div style="background:var(--card);border-radius:999px;padding:10px 14px;border:1px solid rgba(0,0,0,.04);min-width:120px">
          <div style="font-weight:800">{{ number_format((int)($moduleKPI[$k] ?? 0),0,',','.') }}</div>
          <div class="small-muted" style="font-size:.8rem">{{ $lbl }}</div>
        </div>
      @endforeach
    </div>
  </div>

  {{-- Main grid --}}
  <div class="grid-main">

    {{-- Left column --}}
    <div>
      {{-- Monthly bar --}}
      <div class="card-wrap mb-3">
        <div class="card-head">
          <div>
            <h5>{{ $year }} — Done / In Progress / Dimusnahkan (per bulan)</h5>
            <div class="card-sub">Visualisasi bulanan</div>
          </div>
          <div class="small-muted">Filter active: {{ $fromDate ? "From $fromDate" : '-' }} {{ $toDate ? "To $toDate" : '' }}</div>
        </div>
        <div style="height:480px;"><canvas id="overviewBar" height="480"></canvas></div>
      </div>

      {{-- Batch Status Table --}}
      <div class="card-wrap mb-3">
        <div class="card-head">
          <div><h5>Batch Status</h5><div class="card-sub">Daftar batch & posisi terakhir (first step / last step / duration)</div></div>
          <div class="small-muted">Max 200 rows (recent)</div>
        </div>

        <div style="max-height:460px;overflow:auto">
          <table class="table table-sm table-striped batch-table mb-0">
            <thead class="small-muted">
              <tr>
                <th>ID</th>
                <th>Produk</th>
                <th>WO Date</th>
                <th>First Step</th>
                <th>Last Step</th>
                <th class="text-end">Duration</th>
              </tr>
            </thead>
            <tbody>
              @forelse($batchStatus as $b)
                @php
                  // format minutes -> "X hari Y jam Z menit"
                  $durDisplay = '-';
                  $min = $b['minutes'] ?? null;
                  if (!is_null($min)) {
                      $hari = intdiv((int)$min, 1440);
                      $jam = intdiv(((int)$min % 1440), 60);
                      $mnt = (int)$min % 60;
                      $parts = [];
                      if ($hari > 0) $parts[] = $hari . ' hari';
                      if ($jam > 0) $parts[] = $jam . ' jam';
                      $parts[] = $mnt . ' menit';
                      $durDisplay = implode(' ', $parts);
                  }
                @endphp
                <tr>
                  <td>{{ $b['id'] }}</td>
                  <td style="max-width:220px">{{ \Illuminate\Support\Str::limit($b['produk'] ?? '-',40) }}</td>
                  <td>{{ $b['wo_date'] ? \Carbon\Carbon::parse($b['wo_date'])->format('Y-m-d') : '-' }}</td>
                  <td>{{ $b['first_step'] }}</td>
                  <td>{{ $b['last_step'] }}</td>
                  <td class="text-end">{{ $durDisplay }}</td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-muted">Tidak ada data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- Top Produk (REMOVED per request) --}}
      {{-- intentionally omitted --}}

    </div>

    {{-- Right column --}}
    <div class="right-sticky">

      {{-- Rekon doughnut + module selector --}}
      <div class="card-wrap">
        <div class="card-head"><h5>Rekon (per Module)</h5><div class="card-sub">Pilih module untuk menampilkan Rekon hanya module tersebut</div></div>

        <div style="display:flex;gap:10px;align-items:center">
          <div style="width:60%"><canvas id="chartRekon" height="260"></canvas></div>
          <div style="width:40%">
            <div class="small-muted">Module</div>
            <div class="mb-2">
              <select id="moduleSelect" class="form-select form-select-sm">
                <option value="_all" {{ $selectedModule === '_all' ? 'selected' : '' }}>— All Modules (Overview) —</option>
                @foreach($moduleCols as $k => $c)
                  <option value="{{ $k }}" {{ $selectedModule === $k ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$k)) }}</option>
                @endforeach
              </select>
            </div>
            <div id="moduleRekonStats" style="font-weight:700"></div>
            <div id="moduleCurrently" class="small-muted" style="margin-top:6px"></div>
          </div>
        </div>
      </div>

      {{-- In Progress (separate card, larger) --}}
      <div class="card-wrap">
        <div class="card-head">
          <div><h5>Batch In Progress (Per Bulan)</h5><div class="card-sub">Jumlah batch sedang berjalan per bulan</div></div>
        </div>
        <div style="height:240px;"><canvas id="barInProgressLarge" height="240"></canvas></div>
      </div>

      {{-- Secondary Completed (separate card, larger) --}}
      <div class="card-wrap">
        <div class="card-head">
          <div><h5>Batch Completed (Secondary) - Per Bulan</h5><div class="card-sub">Jumlah batch selesai (secondary2) per bulan</div></div>
        </div>
        <div style="height:240px;"><canvas id="barSecondaryDoneLarge" height="240"></canvas></div>
      </div>

      {{-- Progress per-batch per module (current batches snippet) --}}
      <div class="card-wrap">
        <div class="card-head"><h5>Progress per Batch (Per Module)</h5><div class="card-sub">Ringkasan per module + contoh batch sedang berjalan (ditampilkan: batch sedang dimana & durasi sejak masuk)</div></div>
        <div style="max-height:520px;overflow:auto;padding-right:6px">
          @foreach($perModuleSummary as $k => $m)
            <div style="margin-bottom:12px">
              <div style="display:flex;justify-content:space-between;align-items:center">
                <div style="font-weight:700">{{ $m['label'] }}</div>
                <div class="small-muted">{{ $m['done'] }} / {{ $m['total'] }}</div>
              </div>

              <div class="progress-pill mt-2" style="margin-top:6px">
                @php $c = ($m['pct'] ?? 0) >= 90 ? '#28c76f' : (($m['pct'] ?? 0) >= 50 ? '#ff9f43' : '#ea5455'); @endphp
                <div class="progress-fill" style="width:{{ $m['pct'] ?? 0 }}%;background:{{ $c }}"></div>
              </div>

              <div class="small-muted" style="margin-top:6px">In-progress: <strong>{{ number_format($m['in_progress'] ?? 0) }}</strong></div>

              <div class="batch-list" style="margin-top:8px">
                @if(count($m['current_batches'] ?? []))
                  @foreach($m['current_batches'] as $b)
                    @php
                      // format minutes for module list
                      $durDisplay = '-';
                      $min = $b['minutes'] ?? null;
                      if (!is_null($min)) {
                          $hari = intdiv((int)$min, 1440);
                          $jam = intdiv(((int)$min % 1440), 60);
                          $mnt = (int)$min % 60;
                          $parts = [];
                          if ($hari > 0) $parts[] = $hari . ' hari';
                          if ($jam > 0) $parts[] = $jam . ' jam';
                          $parts[] = $mnt . ' menit';
                          $durDisplay = implode(' ', $parts);
                      }
                    @endphp
                    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed #f1f4f8">
                      <div style="flex:1">
                        <div style="font-weight:700">{{ $b['ident'] }}</div>
                        <div class="small-muted">{{ \Illuminate\Support\Str::limit($b['produk'],36) }}</div>
                      </div>
                      <div style="min-width:140px;text-align:right">
                        <div class="small-muted" style="font-weight:700">{{ $b['current_step'] }}</div>
                        <div class="small-muted">{{ $durDisplay }}</div>
                      </div>
                    </div>
                  @endforeach
                @else
                  <div class="small-muted">Tidak ada batch aktif.</div>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>

    </div>

  </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  // controller-provided data
  const months = @json($months);
  const barDone = @json($barDone);            // completed secondary (per month)
  const barInProgress = @json($barInProgress);
  const barDimusnahkan = @json($barDimusnahkan);
  const perModuleRekon = @json($perModuleRekon);
  const moduleInProgressCounts = @json($moduleInProgressCounts);
  const perModuleSummary = @json($perModuleSummary);
  const rekonData = @json($rekonData);
  const rekonLabels = @json($rekonLabels);

  // Overview stacked bar (larger)
  (function(){
    const ctx = document.getElementById('overviewBar');
    if (!ctx) return;
    new Chart(ctx.getContext('2d'), {
      type: 'bar',
      data: {
        labels: months,
        datasets: [
          { label: 'Done (Secondary)', data: barDone, backgroundColor: '#1f77b4' },
          { label: 'In Progress', data: barInProgress, backgroundColor: '#2ca02c' },
          { label: 'Dimusnahkan', data: barDimusnahkan, backgroundColor: '#d62728' }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
      }
    });
  })();

  // Rekon doughnut (per-module when selected) - larger
  let rekonChart = null;
  (function(){
    const ctx = document.getElementById('chartRekon');
    if (!ctx) return;
    rekonChart = new Chart(ctx.getContext('2d'), {
      type: 'doughnut',
      data: { labels: rekonLabels, datasets: [{ data: rekonData, backgroundColor: ['#28c76f','#ff9f43','#ea5455'] }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '55%' }
    });
  })();

  function showRekonForModule(moduleKey) {
    const statsEl = document.getElementById('moduleRekonStats');
    const currentlyEl = document.getElementById('moduleCurrently');

    if (moduleKey === '_all') {
      if (rekonChart) {
        rekonChart.data.labels = rekonLabels;
        rekonChart.data.datasets[0].data = rekonData;
        rekonChart.update();
      }
      if (statsEl) statsEl.innerHTML = `<small class="small-muted">Overview (seluruh batch)</small>`;
      if (currentlyEl) currentlyEl.innerHTML = '';
      return;
    }

    const r = perModuleRekon[moduleKey] || {sesuai:0,mendekati:0,tidak:0,total:0};
    const arr = [Number(r.sesuai||0), Number(r.mendekati||0), Number(r.tidak||0)];
    if (rekonChart) {
      rekonChart.data.labels = rekonLabels;
      rekonChart.data.datasets[0].data = arr;
      rekonChart.update();
    }
    if (statsEl) statsEl.innerHTML = `<div style="font-size:.95rem">${moduleKey.replace('_',' ')} — <span class="small-muted">Sesuai:</span> <b>${r.sesuai}</b> &nbsp; <span class="small-muted">Mendekati:</span> <b>${r.mendekati}</b> &nbsp; <span class="small-muted">Tidak:</span> <b>${r.tidak}</b></div>`;
    const cur = moduleInProgressCounts[moduleKey] ?? 0;
    if (currentlyEl) currentlyEl.innerHTML = `<div class="small-muted">Currently in ${moduleKey.replace('_',' ')}: <strong>${cur}</strong> batch</div>`;
  }

  (function(){
    const sel = document.getElementById('moduleSelect');
    if (!sel) return;
    let initial = sel.value || '_all';
    if (initial !== '_all' && !perModuleRekon.hasOwnProperty(initial)) {
      const keys = Object.keys(perModuleRekon);
      if (keys.length) { initial = keys[0]; sel.value = initial; }
      else initial = '_all';
    }
    showRekonForModule(initial);
    sel.addEventListener('change', function(){ showRekonForModule(this.value); });
  })();

  // Large bar: In Progress (per month) - separate card
  (function(){
    const ctx = document.getElementById('barInProgressLarge');
    if (!ctx) return;
    new Chart(ctx.getContext('2d'), {
      type: 'bar',
      data: { labels: months, datasets: [{ label: 'In Progress', data: barInProgress, backgroundColor: '#2ca02c' }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
  })();

  // Large bar: Secondary Done (per month) - separate card
  (function(){
    const ctx = document.getElementById('barSecondaryDoneLarge');
    if (!ctx) return;
    new Chart(ctx.getContext('2d'), {
      type: 'bar',
      data: { labels: months, datasets: [{ label: 'Completed (Secondary)', data: barDone, backgroundColor: '#1f77b4' }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
  })();

</script>
@endpush
