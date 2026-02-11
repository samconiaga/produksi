<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">

  @php
    $cardWmm = 167;
    $cardHmm = 75;

    $offsetXmm = (float) request('ox', $offsetXmm ?? 18);
    $offsetYmm = (float) request('oy', $offsetYmm ?? 10);
    $debugBorder = (int) request('debug', $debugBorder ?? 0);

    $labelXmm = (float) request('lx', $labelXmm ?? 10);
    $colonXmm = (float) request('cx', $colonXmm ?? 62);
    $valueXmm = (float) request('vx', $valueXmm ?? 66);

    $topStartMm = (float) request('ts', $topStartMm ?? 28);
    $rowGapMm   = (float) request('rg', $rowGapMm ?? 6);

    $hdrLeftXmm  = (float) request('hlx', $hdrLeftXmm ?? 8);
    $hdrTopMm    = (float) request('ht',  $hdrTopMm ?? 8);
    $hdrCenterMm = (float) request('hc',  $hdrCenterMm ?? 38);

    $tglXmm = (float) request('tx', $tglXmm ?? 12);
    $tglYmm = (float) request('ty', $tglYmm ?? 66);

    $qrXmm    = (float) request('qx', $qrXmm ?? 138);
    $qrYmm    = (float) request('qy', $qrYmm ?? 50);
    $qrSizeMm = (float) request('qs', $qrSizeMm ?? 18);

    $items = $items ?? [];
  @endphp

  <style>
    @page { size: A4 portrait; margin: 0; }
    html, body { margin:0; padding:0; }

    .page{ width:210mm; height:297mm; position:relative; page-break-after:always; }
    .page:last-child{ page-break-after:auto; }

    .card{
      position:absolute;
      left: {{ $offsetXmm }}mm;
      top:  {{ $offsetYmm }}mm;
      width: {{ $cardWmm }}mm;
      height: {{ $cardHmm }}mm;
      box-sizing:border-box;
      border: {{ $debugBorder ? '0.3mm dashed #ff0000' : 'none' }};
      overflow: visible;
    }

    .card, .card *{
      font-family: "Times New Roman", "Times-Roman", serif;
      color:#000;
      font-weight:700;
      line-height:1.15;
      font-size: 13pt;
    }

    .hdr-left{ position:absolute; left: {{ $hdrLeftXmm }}mm; top: {{ $hdrTopMm }}mm; font-size: 12pt; }
    .hdr-mid{
      position:absolute;
      left: {{ $hdrCenterMm }}mm;
      top:  {{ $hdrTopMm }}mm;
      text-align:center;
      width: 90mm;
      font-size: 14pt;
    }
    .hdr-mid .t1, .hdr-mid .t2{ display:block; }

    .lbl{ position:absolute; left: {{ $labelXmm }}mm; white-space:nowrap; font-size: 12pt; }
    .col{ position:absolute; left: {{ $colonXmm }}mm; white-space:nowrap; font-size: 12pt; }
    .val{ position:absolute; left: {{ $valueXmm }}mm; white-space:nowrap; font-size: 12pt; }

    .r1{ top: {{ $topStartMm + (0*$rowGapMm) }}mm; }
    .r2{ top: {{ $topStartMm + (1*$rowGapMm) }}mm; }
    .r3{ top: {{ $topStartMm + (2*$rowGapMm) }}mm; }
    .r4{ top: {{ $topStartMm + (3*$rowGapMm) }}mm; }
    .r5{ top: {{ $topStartMm + (4*$rowGapMm) }}mm; }

    .tgl-label{ position:absolute; left: {{ $tglXmm }}mm; top: {{ $tglYmm }}mm; font-size: 12pt; }
    .tgl-val{ position:absolute; left: {{ $tglXmm + 20 }}mm; top: {{ $tglYmm }}mm; font-size: 12pt; white-space:nowrap; }

    .paraf-label{
      position:absolute;
      left: {{ $qrXmm - 18 }}mm;
      top:  {{ $tglYmm }}mm;
      font-size: 12pt;
    }

    .qr{
      position:absolute;
      left: {{ $qrXmm }}mm;
      top:  {{ $qrYmm }}mm;
      width: {{ $qrSizeMm }}mm;
      height: {{ $qrSizeMm }}mm;
    }
    .qr img{ width:100%; height:100%; display:block; }
  </style>
</head>
<body>
  @foreach($items as $it)
    @php
      $namaProduk = $it['namaProduk'] ?? '';
      $noBatch    = $it['noBatch'] ?? '';
      $expDate    = $it['expDate'] ?? '';
      $berat      = $it['berat'] ?? '';
      $noWadah    = $it['noWadah'] ?? '';
      $tanggal    = $it['tanggal'] ?? '';
      $qrPng      = $it['qrPng'] ?? '';
    @endphp

    <div class="page">
      <div class="card">
        <div class="hdr-left">PT. SAMCO FARMA</div>
        <div class="hdr-mid">
          <span class="t1">PELULUSAN</span>
          <span class="t2">PRODUK ANTARA</span>
        </div>

        <div class="lbl r1">NAMA PRODUK</div><div class="col r1">:</div><div class="val r1">{{ $namaProduk }}</div>
        <div class="lbl r2">NO. BATCH</div><div class="col r2">:</div><div class="val r2">{{ $noBatch }}</div>
        <div class="lbl r3">EXP DATE</div><div class="col r3">:</div><div class="val r3">{{ $expDate }}</div>
        <div class="lbl r4">BERAT</div><div class="col r4">:</div><div class="val r4">{{ $berat }}</div>
        <div class="lbl r5">NO. WADAH</div><div class="col r5">:</div><div class="val r5">{{ $noWadah }}</div>

        <div class="tgl-label">Tanggal :</div>
        <div class="tgl-val">{{ $tanggal }}</div>

        <div class="paraf-label">Paraf :</div>

        @if(!empty($qrPng))
          <div class="qr">
            <img src="data:image/png;base64,{{ $qrPng }}" alt="QR">
          </div>
        @endif
      </div>
    </div>
  @endforeach
</body>
</html>
