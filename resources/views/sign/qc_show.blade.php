<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verifikasi TTD Digital QC</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f6f7fb;margin:0}
    .wrap{max-width:980px;margin:24px auto;padding:16px}
    .card{background:#fff;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.08);overflow:hidden}
    .hdr{padding:16px 18px;border-bottom:1px solid #eef0f4;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap}
    .hdr-left{display:flex;gap:12px;align-items:center}
    .badge{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:#eef7ff;color:#0b5ed7;font-weight:700;font-size:13px}
    .okdot{width:10px;height:10px;border-radius:50%;background:#16a34a}
    .body{padding:18px}
    .layout{display:grid;grid-template-columns:1fr 320px;gap:14px;align-items:start}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .item{border:1px solid #eef0f4;border-radius:12px;padding:12px}
    .lbl{font-size:12px;color:#6b7280;margin-bottom:6px}
    .val{font-size:15px;font-weight:650;color:#111827;word-break:break-word}
    .sigCard{border:1px solid #eef0f4;border-radius:12px;padding:12px;background:#fafbff}
    .sigTitle{font-size:13px;font-weight:800;color:#111827;margin-bottom:8px}
    .sigBox{width:100%;aspect-ratio:1/1;border:1px dashed #d6dbe6;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden}
    .sigBox img{max-width:100%;max-height:100%;display:block}
    .sigNote{margin-top:10px;font-size:12px;color:#6b7280;line-height:1.4}
    .foot{padding:14px 18px;border-top:1px solid #eef0f4;background:#fafbff;font-size:12px;color:#6b7280}

    @media (max-width:920px){.layout{grid-template-columns:1fr}}
    @media (max-width:720px){.grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
@php
  $stepUpper  = strtoupper($step ?? '-');
  $namaProduk = $batch->nama_produk ?? ($batch->produksi->nama_produk ?? '-');

  $signedAtText = '-';
  if (!empty($signedAt)) {
    try { $signedAtText = \Carbon\Carbon::parse($signedAt)->format('d-m-Y H:i:s'); }
    catch (\Throwable $e) { $signedAtText = (string) $signedAt; }
  }

  $tglRilisText = '-';
  if (!empty($tglRilis)) {
    try { $tglRilisText = \Carbon\Carbon::parse($tglRilis)->format('d-m-Y'); }
    catch (\Throwable $e) { $tglRilisText = (string) $tglRilis; }
  }

  // path gambar barcode/QR tanda tangan dari user yang release
  $sigUrl = null;
  if (!empty($sigPath)) {
    // qc_signature_path disimpan di disk "public"
    $sigUrl = asset('storage/' . ltrim($sigPath, '/'));
  }
@endphp

  <div class="wrap">
    <div class="card">
      <div class="hdr">
        <div class="hdr-left">
          <span class="badge"><span class="okdot"></span>TTD Digital VALID</span>
          <div style="font-weight:800">Verifikasi Tanda Tangan QC</div>
        </div>

        <div style="font-size:12px;color:#6b7280">
          Step: <b>{{ $stepUpper }}</b> • Kode: <b>{{ $code ?? '-' }}</b>
        </div>
      </div>

      <div class="body">
        <div class="layout">
          {{-- KIRI: DATA --}}
          <div class="grid">
            <div class="item">
              <div class="lbl">Produk</div>
              <div class="val">{{ $namaProduk }}</div>
            </div>

            <div class="item">
              <div class="lbl">Kode Batch</div>
              <div class="val">{{ $batch->kode_batch ?? '-' }}</div>
            </div>

            <div class="item">
              <div class="lbl">No Batch</div>
              <div class="val">{{ $batch->no_batch ?? '-' }}</div>
            </div>

            <div class="item">
              <div class="lbl">Status Proses</div>
              <div class="val">{{ $batch->status_proses ?? '-' }}</div>
            </div>

            <div class="item">
              <div class="lbl">Ditandatangani Oleh</div>
              <div class="val">{{ $signedBy ?? '-' }}</div>
            </div>

            <div class="item">
              <div class="lbl">Level</div>
              <div class="val">{{ $signedLevel ?? '-' }}</div>
            </div>

            <div class="item">
              <div class="lbl">Waktu TTD</div>
              <div class="val">{{ $signedAtText }}</div>
            </div>

            <div class="item">
              <div class="lbl">Tanggal Rilis</div>
              <div class="val">{{ $tglRilisText }}</div>
            </div>
          </div>

          {{-- KANAN: BARCODE/QR TTD USER --}}
          <div class="sigCard">
            <div class="sigTitle">Barcode / QR Tanda Tangan (User QC)</div>

            <div class="sigBox">
              @if($sigUrl)
                <img src="{{ $sigUrl }}" alt="TTD Digital QC">
              @else
                <div style="padding:14px;text-align:center;color:#6b7280;font-size:12px;line-height:1.4">
                  Belum ada barcode/QR tanda tangan yang tersimpan untuk user yang melakukan release.
                  <br><br>
                  Solusi: buka menu <b>User QC</b> → upload <b>Barcode/QR Tanda Tangan</b>.
                </div>
              @endif
            </div>

            <div class="sigNote">
              Ini adalah gambar barcode/QR yang di-upload pada akun QC yang melakukan <b>Release</b>.
              Scan barcode/QR ini untuk validasi internal (jika dibutuhkan).
            </div>
          </div>
        </div>
      </div>

      <div class="foot">
        Catatan: Link ini memakai <b>signed URL</b>. Jika URL diubah/diotak-atik, verifikasi akan gagal (403).
      </div>
    </div>
  </div>
</body>
</html>
