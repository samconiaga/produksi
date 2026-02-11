<!doctype html>
<html>
<head>
  <meta charset="utf-8">

  <style>
    @page { size: A4 portrait; margin: 0; }
    html, body { margin: 0; padding: 0; }

    .page{
      width: 210mm;
      height: 297mm;
      position: relative;
    }

    /* ukuran kartu hijau 16.7cm x 7.5cm, diputar -90 */
    .wrap{
      position:absolute;
      left: {{ $offsetXmm }}mm;
      top:  {{ $offsetYmm }}mm;
      width: 75mm;   /* setelah rotate: tinggi kartu */
      height: 167mm; /* setelah rotate: lebar kartu */
      box-sizing: border-box;
      border: {{ !empty($debugBorder) ? '1px dashed #ff0000' : 'none' }};
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
      font-size: 14pt;
      font-weight: 700;
      color:#000;
      line-height: 1.15;
    }

    /* ISIAN */
    .v-nama  { position:absolute; left: {{ $valueXmm }}mm; top: {{ $topStartMm }}mm; white-space:nowrap; }
    .v-batch { position:absolute; left: {{ $valueXmm }}mm; top: {{ $topStartMm + $rowGapMm }}mm; white-space:nowrap; }
    .v-exp   { position:absolute; left: {{ $valueXmm }}mm; top: {{ $topStartMm + (2*$rowGapMm) }}mm; white-space:nowrap; }

    .v-tgl{
      position:absolute;
      left: {{ $tglXmm }}mm;
      top:  {{ $tglYmm }}mm;
      white-space: nowrap;
      font-size: 12pt;
    }

    /* QR */
    .paraf-qr{
      position:absolute;
      left: {{ $qrXmm }}mm;
      top:  {{ $qrYmm }}mm;
      width: {{ $qrSizeMm }}mm;
      height: {{ $qrSizeMm }}mm;
    }
    .paraf-qr img{ width:100%; height:100%; display:block; }

    /* BARCODE */
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
  <div class="page">
    <div class="wrap">
      <div class="card">
        <div class="v-nama">{{ $namaProduk }}</div>
        <div class="v-batch">{{ $noBatch }}</div>
        <div class="v-exp">{{ $expDate }}</div>

        <div class="v-tgl">{{ $tanggal }}</div>

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
</body>
</html>
