<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">

  @php
    $items = $items ?? [];

    $offsetXmm = (float)($offsetXmm ?? request('ox', 18));
    $offsetYmm = (float)($offsetYmm ?? request('oy', 10));
    $debugBorder = (int)($debugBorder ?? request('debug', 0));

    $labelXmm = (float)($labelXmm ?? request('lx', 10));
    $colonXmm = (float)($colonXmm ?? request('cx', 62));
    $valueXmm = (float)($valueXmm ?? request('vx', 66));

    $topStartMm = (float)($topStartMm ?? request('ts', 28));
    $rowGapMm   = (float)($rowGapMm ?? request('rg', 6));

    $tglXmm = (float)($tglXmm ?? request('tx', 12));
    $tglYmm = (float)($tglYmm ?? request('ty', 66));

    $qrXmm    = (float)($qrXmm ?? request('qx', 138));
    $qrYmm    = (float)($qrYmm ?? request('qy', 50));
    $qrSizeMm = (float)($qrSizeMm ?? request('qs', 18));

    $barXmm  = (float)($barXmm ?? request('bx', 100));
    $barYmm  = (float)($barYmm ?? request('by', 48));
    $barWmm  = (float)($barWmm ?? request('bw', 60));
    $barHmm  = (float)($barHmm ?? request('bh', 12));
    $barTXmm = (float)($barTXmm ?? request('btx', 102));
    $barTYmm = (float)($barTYmm ?? request('bty', 60));
  @endphp

  <style>
    @page { size: A4 portrait; margin: 0; }
    html, body { margin: 0; padding: 0; }

    .page{
      width: 210mm;
      height: 297mm;
      position: relative;
      page-break-after: always;
    }
    .page:last-child { page-break-after: auto; }

    .wrap{
      position:absolute;
      left: {{ $offsetXmm }}mm;
      top:  {{ $offsetYmm }}mm;
      width: 75mm;
      height: 167mm;
      box-sizing: border-box;
      border: {{ $debugBorder ? '1px dashed #ff0000' : 'none' }};
      overflow: visible;
    }

    .card{
      position:absolute;
      left:0;
      top:167mm;
      width:167mm;
      height:75mm;
      transform-origin: 0 0;
      transform: rotate(-90deg);
    }

    .card, .card *{
      font-family: "Times New Roman", "Times-Roman", serif;
      font-size: 13pt;
      font-weight: 700;
      color:#000;
      line-height: 1.15;
    }

    .lbl{ position:absolute; left: {{ $labelXmm }}mm; white-space:nowrap; font-size: 12pt; }
    .col{ position:absolute; left: {{ $colonXmm }}mm; white-space:nowrap; font-size: 12pt; }
    .val{ position:absolute; left: {{ $valueXmm }}mm; white-space:nowrap; font-size: 12pt; }

    .r1{ top: {{ $topStartMm + (0*$rowGapMm) }}mm; }
    .r2{ top: {{ $topStartMm + (1*$rowGapMm) }}mm; }
    .r3{ top: {{ $topStartMm + (2*$rowGapMm) }}mm; }

    .tgl-label{ position:absolute; left: {{ $tglXmm }}mm; top: {{ $tglYmm }}mm; font-size: 12pt; }
    .tgl-val  { position:absolute; left: {{ $tglXmm + 20 }}mm; top: {{ $tglYmm }}mm; font-size: 12pt; white-space:nowrap; }

    .paraf-label{
      position:absolute;
      left: {{ $qrXmm - 18 }}mm;
      top:  {{ $tglYmm }}mm;
      font-size: 12pt;
      white-space: nowrap;
    }

    .paraf-qr{
      position:absolute;
      left: {{ $qrXmm }}mm;
      top:  {{ $qrYmm }}mm;
      width: {{ $qrSizeMm }}mm;
      height: {{ $qrSizeMm }}mm;
    }
    .paraf-qr img{ width:100%; height:100%; display:block; }

    .barcode{
      position:absolute;
      left: {{ $barXmm }}mm;
      top:  {{ $barYmm }}mm;
      width: {{ $barWmm }}mm;
      height: {{ $barHmm }}mm;
    }
    .barcode img{ width:100%; height:100%; display:block; }

    .barcode-text{
      position:absolute;
      left: {{ $barTXmm }}mm;
      top:  {{ $barTYmm }}mm;
      font-size: 8pt;
      letter-spacing: .4px;
      white-space: nowrap;
    }
  </style>
</head>

<body>
@foreach($items as $it)
  @php
    $namaProduk = $it['namaProduk'] ?? '';
    $noBatch    = $it['noBatch'] ?? '';
    $expDate    = $it['expDate'] ?? '';
    $tanggal    = $it['tanggal'] ?? '';
    $qrPng      = $it['qrPng'] ?? '';
    $barcodePng = $it['barcodePng'] ?? '';
    $signCode   = $it['signCode'] ?? '';
  @endphp

  <div class="page">
    <div class="wrap">
      <div class="card">

        <div class="lbl r1">NAMA PRODUK</div><div class="col r1">:</div><div class="val r1">{{ $namaProduk }}</div>
        <div class="lbl r2">NO. BATCH</div>  <div class="col r2">:</div><div class="val r2">{{ $noBatch }}</div>
        <div class="lbl r3">EXP DATE</div>   <div class="col r3">:</div><div class="val r3">{{ $expDate }}</div>

        <div class="tgl-label">Tanggal :</div>
        <div class="tgl-val">{{ $tanggal }}</div>

        <div class="paraf-label">Paraf :</div>

        @if(!empty($qrPng))
          <div class="paraf-qr">
            <img src="data:image/png;base64,{{ $qrPng }}" alt="qr">
          </div>
        @endif

        @if(!empty($barcodePng))
          <div class="barcode">
            <img src="data:image/png;base64,{{ $barcodePng }}" alt="barcode">
          </div>
          <div class="barcode-text">{{ $signCode }}</div>
        @endif

      </div>
    </div>
  </div>
@endforeach
</body>
</html>
