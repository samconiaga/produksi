<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LPHP - Print</title>

  <style>
    @page { size: A4; margin: 10mm 10mm 12mm 10mm; }
    html, body { margin:0; padding:0; }
    body { font-family: Arial, sans-serif; font-size: 12px; color:#111; font-weight:400; }
    *, *::before, *::after { box-sizing: border-box; }

    :root{
      --m-left: 10mm;
      --m-right: 10mm;
      --header-h: 40mm;
      --hdr-h: 28mm;
      --hdr-top: 18mm;
      --hdr-btm: 10mm;
      --fs-pt: 13.2px;
      --fs-mid: 12.6px;
      --fs-dept: 12.6px;
      --fs-doc: 12.6px;
      --logo-max-h: 14.5mm;
      --hdr-left-w: 55mm;
      --hdr-right-w: 60mm;
    }

    /* HEADER FIXED */
    .print-header{ position: fixed; top: 0; left: var(--m-left); right: var(--m-right); height: var(--header-h); background:#fff; }
    .content{ margin: 0 var(--m-right) 0 var(--m-left); padding-top: calc(var(--header-h) + 2mm); padding-bottom: 16mm; }

    .hdr-table{ width:100%; border-collapse: collapse; table-layout: fixed; border:1px solid #000; }
    .hdr-table td{ border-left:1px solid #000; padding:0; vertical-align: middle; overflow:hidden; font-weight:400; }
    .hdr-table td:first-child{ border-left:0; }
    .hdr-col-left{ width: var(--hdr-left-w); }
    .hdr-col-mid{  width:auto; }
    .hdr-col-right{ width: var(--hdr-right-w); }
    .hdr-box{ height: var(--hdr-h); }
    .left-wrap, .mid-wrap{ height: var(--hdr-h); overflow:hidden; }

    .left-top{ height: var(--hdr-top); display:flex; align-items:center; justify-content:center; padding:0 2mm; overflow:hidden; }
    .left-top img{ max-height: var(--logo-max-h); max-width: 52mm; display:block; object-fit:contain; }
    .left-bottom{ height: var(--hdr-btm); border-top:1px solid #000; display:flex; align-items:center; justify-content:center; font-size: var(--fs-pt); line-height:1; white-space:nowrap; overflow:hidden; font-weight:400; }

    .mid-top{ height: var(--hdr-top); display:flex; align-items:center; justify-content:center; text-align:center; text-transform:uppercase; font-size: var(--fs-mid); line-height: 1.05; padding:0 2mm; overflow:hidden; font-weight:400; }
    .mid-bottom{ height: var(--hdr-btm); border-top:1px solid #000; display:flex; align-items:center; justify-content:center; text-align:center; text-transform:uppercase; font-size: var(--fs-dept); line-height:1.05; padding:0 2mm; overflow:hidden; font-weight:400; }

    .right-wrap{ height: var(--hdr-h); display:flex; align-items:center; padding:0 2mm; font-size: var(--fs-doc); white-space:nowrap; overflow:hidden; font-weight:400; }
    .right-wrap span{ font-weight:400; padding-left:6px; }

    .date-row{ margin-top: 2mm; text-align:right; font-size:12px; font-weight:400; }

    /* TABLE DATA */
    table.data{ width:100%; max-width:100%; border-collapse:collapse; table-layout: fixed; }
    thead{ display: table-header-group; } /* supaya header tabel tampil di setiap halaman */
    th, td{ border:1px solid #000; padding:6px 6px; vertical-align:middle; overflow:hidden; font-weight:400; }
    th{ text-align:center; font-size:11px; background:#fff; white-space:nowrap; }
    td{ font-size:11.5px; }

    .center{ text-align:center; } .right{ text-align:right; } .nowrap{ white-space:nowrap; }
    .wrap{ white-space:normal; word-break:break-word; overflow-wrap:anywhere; }

    /* SIGNATURE 3 columns */
    .sign-section{ margin-top: 10mm; page-break-inside: avoid; break-inside: avoid; }
    .sign-row{ display:flex; justify-content:space-between; gap:20px; }
    .sign-box{ width:31%; text-align:center; font-size:12px; font-weight:400; min-height:34mm; display:flex; flex-direction:column; align-items:center; }
    .sign-title{ font-weight:400; margin-bottom:6mm; text-transform:uppercase; }
    .sign-line{ width:100%; margin-top:auto; border-top:1px solid #000; }
    .sign-sub{ font-size:11px; margin-top:4px; color:#222; font-weight:700; min-height:16px; }
    .footer-note{ margin-top: 6mm; font-size:11px; font-style:italic; font-weight:400; text-align:left; }

    @media print{ html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
  </style>
</head>

<body>
@php
  use Carbon\Carbon;

  $spvDoc = $spvDoc ?? null;
  $rows   = $rows ?? collect();
  $docNo  = $docNo ?? null;

  // controller-supplied (controller must provide these)
  $opName = $opName ?? null; $opAt = $opAt ?? null;
  $spvName = $spvName ?? null; $spvAt = $spvAt ?? null; $spvTtdUrl = $spvTtdUrl ?? null;
  $gojName = $gojName ?? null; $gojAt = $gojAt ?? null;
  $showGoj = $showGoj ?? false;

  function fmtDateShort($d){
    try { return $d ? Carbon::parse($d)->format('d-m-Y') : null; } catch(\Throwable $e){ return null; }
  }
@endphp

  <!-- HEADER -->
  <div class="print-header">
    <table class="hdr-table">
      <tr class="hdr-box">
        <td class="hdr-col-left">
          <div class="left-wrap">
            <div class="left-top">
              <img src="{{ asset('app-assets/images/logo/Samco.png') }}" alt="SAMCO">
            </div>
            <div class="left-bottom">PT. SAMCO FARMA</div>
          </div>
        </td>

        <td class="hdr-col-mid">
          <div class="mid-wrap">
            <div class="mid-top">
              FORM LAPORAN HASIL PENYERAHAN<br>PRODUKSI
            </div>
            <div class="mid-bottom">DEPARTEMEN PRODUKSI</div>
          </div>
        </td>

        <td class="hdr-col-right">
          <div class="right-wrap">
            No. Dokumen <span>: {{ $docNo ?? ($spvDoc->doc_no ?? 'SF/PRU – DG 032') }}</span>
          </div>
        </td>
      </tr>
    </table>

    <div class="date-row">Tanggal : {{ now()->format('d/m/y') }}</div>
  </div>

  <!-- CONTENT -->
  <div class="content">
    <table class="data">
      <colgroup>
        <col style="width:6%">
        <col style="width:20%">
        <col style="width:18%">
        <col style="width:10%">
        <col style="width:10%">
        <col style="width:14%">
        <col style="width:10%">
        <col style="width:12%">
      </colgroup>

      <thead>
        <tr>
          <th>No</th>
          <th>Nama Produk</th>
          <th>Kode Batch</th>
          <th>Exp Date</th>
          <th>Kemasan</th>
          <th>Isi</th>
          <th>Jumlah</th>
          <th>Total</th>
        </tr>
      </thead>

      <tbody>
        @php
          $no = 1;
          $namaProduk = fn($row) => trim((string)($row->produksi->nama_produk ?? $row->nama_produk ?? '-'));
          $kodeBatch  = fn($row) => trim((string)($row->kode_batch ?? $row->kode ?? $row->no_batch ?? '-'));
          $fmtExp = function($row){
            $raw = data_get($row, 'gudangRelease.tanggal_expired')
              ?? data_get($row, 'tanggal_expired')
              ?? data_get($row, 'tgl_expired')
              ?? data_get($row, 'expired_date')
              ?? data_get($row, 'exp_date');
            if(!$raw) return '-';
            try { return Carbon::parse($raw)->format('M-y'); } catch(\Throwable $e){ return (string)$raw; }
          };

          // prioritas: produksi.wadah dulu
          $kemasan = function($row){
            return data_get($row,'produksi.wadah')
                ?? data_get($row,'produksi.kemasan')
                ?? data_get($row,'gudangRelease.kemasan')
                ?? data_get($row,'wadah')
                ?? data_get($row,'kemasan')
                ?? '-';
          };

          $isiText = function($row){
            $v = data_get($row,'gudangRelease.isi') ?? data_get($row,'isi') ?? '-';
            return trim((string)$v) === '' ? '-' : (string)$v;
          };
          $jumlahNum = function($row){
            $v = data_get($row,'gudangRelease.jumlah_release');
            if($v === null || $v === '') return 0;
            return (int)$v;
          };
          $jumlahText = function($row) use ($jumlahNum){
            $n = $jumlahNum($row);
            return $n ? (string)$n : '-';
          };
          $rowTotalText = function($row){
            $gr = data_get($row,'gudangRelease');
            if(!$gr) return '';
            if(isset($gr->total_text) && $gr->total_text) return (string)$gr->total_text;
            if(isset($gr->total) && $gr->total) return (string)$gr->total;
            return '';
          };

          $extractUnit = function($txt){
            $txt = trim((string)$txt);
            if($txt === '' || $txt === '-') return '';
            if(preg_match('/^\s*[\d\.,]+\s*([a-zA-Z\p{L}\s\.\-\/\(\)]+)$/u', $txt, $m)){
              $c = trim($m[1]);
              $c = preg_replace('/[\(\)]/', '', $c);
              $parts = preg_split('/\s+/', $c);
              return trim($parts[0]);
            }
            if(preg_match('/([a-zA-Z]{2,})\s*$/u', $txt, $m2)) return trim($m2[1]);
            return '';
          };

          $groups = collect($rows)->groupBy(fn($r) => mb_strtolower($namaProduk($r)));
        @endphp

        @foreach($groups as $items)
          @php
            $items = $items->values();
            $rowspan = $items->count();
            $sumJumlah = $items->sum(fn($r) => $jumlahNum($r));

            $kemasanCandidate = $items->map(fn($r) => $kemasan($r))->filter(fn($k)=> trim((string)$k) !== '' && trim((string)$k) !== '-')->first();
            $unit = '';
            if($kemasanCandidate){
              $k = strtolower((string)$kemasanCandidate);
              if(preg_match('/(dus|box|karton|carton|ctn)/', $k)) $unit = 'dus';
              elseif(preg_match('/(btl|botol|bottle)/', $k)) $unit = 'btl';
              elseif(preg_match('/(pcs|pc|sachet|sct)/', $k)) $unit = 'pcs';
              else $unit = trim($kemasanCandidate);
            }

            if(!$unit){
              $lastFilledTotal = $items->map(fn($r) => $rowTotalText($r))->filter(fn($t)=> trim((string)$t) !== '')->last();
              $unit = $extractUnit($lastFilledTotal);
            }
            if(!$unit){
              $firstIsi = $items->map(fn($r)=> $isiText($r))->filter(fn($t)=> trim((string)$t) !== '' && trim((string)$t) !== '-')->first();
              $unit = $extractUnit($firstIsi);
            }

            $groupTotal = $sumJumlah ? trim((string)$sumJumlah . ($unit ? (' '.$unit) : '')) : '-';
          @endphp

          @foreach($items as $idx => $row)
            <tr>
              <td class="center">{{ $no++ }}</td>
              <td class="wrap">{{ $namaProduk($row) }}</td>
              <td class="wrap">{{ $kodeBatch($row) }}</td>
              <td class="center nowrap">{{ $fmtExp($row) }}</td>
              <td class="center nowrap">{{ $kemasan($row) }}</td>
              <td class="wrap">{{ $isiText($row) }}</td>
              <td class="right nowrap">{{ $jumlahText($row) }}</td>

              @if($idx === 0)
                <td class="right nowrap" rowspan="{{ $rowspan }}" style="vertical-align:middle;">{{ $groupTotal }}</td>
              @endif
            </tr>
          @endforeach
        @endforeach

      </tbody>
    </table>

    <!-- SIGNATURES -->
    <div class="sign-section">
      <div class="sign-row">
        <div class="sign-box">
          <div class="sign-title">DIISI OLEH</div>

          @if(!empty($opName))
            <div class="sign-sub">{{ $opName }}</div>
            @if(!empty($opAt)) <div style="font-size:11px;margin-top:2px">Tanggal: {{ fmtDateShort($opAt) }}</div> @endif
          @else
            <div class="sign-sub">&nbsp;</div>
          @endif

          <div class="sign-line"></div>
        </div>

        <div class="sign-box">
          <div class="sign-title">MENGETAHUI</div>

          @if(!empty($spvTtdUrl))
            <div style="margin-top:6mm">
              <img src="{{ $spvTtdUrl }}" style="max-height:48px; display:block; margin:0 auto 6px;">
            </div>
            <div class="sign-sub">{{ $spvName }}</div>
            @if(!empty($spvAt)) <div style="font-size:11px;margin-top:2px">Tanggal: {{ fmtDateShort($spvAt) }}</div> @endif
          @elseif(!empty($spvName))
            <div class="sign-sub">{{ $spvName }}</div>
            @if(!empty($spvAt)) <div style="font-size:11px;margin-top:2px">Tanggal: {{ fmtDateShort($spvAt) }}</div> @endif
          @else
            <div class="sign-sub">&nbsp;</div>
          @endif

          <div class="sign-line"></div>
        </div>

        <div class="sign-box">
          <div class="sign-title">YANG MENERIMA,</div>

          @if(!empty($showGoj) && !empty($gojName))
            <div class="sign-sub">{{ $gojName }}</div>
            @if(!empty($gojAt)) <div style="font-size:11px;margin-top:2px">Tanggal: {{ fmtDateShort($gojAt) }}</div> @endif
          @else
            <div class="sign-sub">&nbsp;</div>
          @endif

          <div class="sign-line"></div>
        </div>
      </div>

      <div class="footer-note">Arsip Asli Untuk GOJ</div>
    </div>
  </div>

  <script>window.onload = () => window.print();</script>
</body>
</html>
