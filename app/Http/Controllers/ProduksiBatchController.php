<?php

namespace App\Http\Controllers;

use App\Models\Produksi;
use App\Models\ProduksiBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class ProduksiBatchController extends Controller
{
    /* =========================================================
     * LIST + FORM UPLOAD (Jadwal Produksi / Weighing)
     * =======================================================*/
    public function index(Request $request)
    {
        $q       = $request->get('q', '');
        $bulan   = $request->get('bulan');
        $tahun   = $request->get('tahun');
        $perPage = (int) $request->get('per_page', 25);

        if ($perPage <= 0) $perPage = 25;

        $rows = ProduksiBatch::with('produksi')
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('nama_produk', 'like', "%{$q}%")
                        ->orWhere('no_batch', 'like', "%{$q}%")
                        ->orWhere('kode_batch', 'like', "%{$q}%");
                });
            })
            ->when($bulan !== null && $bulan !== '' && $bulan !== 'all', function ($qb) use ($bulan) {
                $qb->where('bulan', (int) $bulan);
            })
            ->when($tahun !== null && $tahun !== '', function ($qb) use ($tahun) {
                $qb->where('tahun', (int) $tahun);
            })
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('produksi_batches.index', compact('rows', 'q', 'perPage', 'bulan', 'tahun'));
    }

    /* =========================================================
     * PROSES UPLOAD EXCEL (Jadwal WO)
     * ✅ no_batch = WO NO (full, tidak diubah)
     * ✅ kode_batch = HANYA "100.01 FA" (dipotong dari WO NO)
     * ✅ Weighing otomatis (tidak menimpa yg sudah ada)
     * ✅ expected_date minimal = wo_date
     * =======================================================*/
    public function upload(Request $request)
    {
        @set_time_limit(300);
        DB::disableQueryLog();

        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx'],
        ]);

        $file = $request->file('file');

        DB::beginTransaction();
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();

            $data = $sheet->toArray(null, true, true, true);

            if (count($data) < 2) {
                throw new \RuntimeException('File Excel kosong / tidak ada data.');
            }

            // =========================
            // 1) Detect header row (row 1)
            // =========================
            $header = $data[1];

            $findCol = function (array $headerRow, array $needles) {
                foreach ($headerRow as $colLetter => $val) {
                    $raw = (string) $val;
                    $h   = strtoupper(trim($raw));
                    if ($h === '') continue;

                    $hn = str_replace([' ', '_', '.', '-', "\n", "\r", "\t"], '', $h);

                    foreach ($needles as $needle) {
                        $n  = strtoupper((string) $needle);
                        $nn = str_replace([' ', '_', '.', '-', "\n", "\r", "\t"], '', $n);

                        if (strpos($h, $n) !== false || strpos($hn, $nn) !== false) {
                            return $colLetter;
                        }
                    }
                }
                return null;
            };

            $colWoNo   = $findCol($header, ['WO NO', 'WONO', 'WORK ORDER', 'WORKORDER', 'WO']);
            $colDesc   = $findCol($header, ['DESCRIPTION', 'DESC', 'KETERANGAN']);
            $colNama   = $findCol($header, ['NAMA PRODUCT', 'NAMA PRODUK', 'PRODUCT', 'PRODUK']);

            $colWoDate = $findCol($header, [
                'WO DATE','WODATE','WO_DT','WODT','W/O DATE',
                'TGL WO','TANGGAL WO','WORK ORDER DATE','WORKORDERDATE','TANGGALWO'
            ]);

            $colExp    = $findCol($header, [
                'EXPECTED', 'EXPECTED DATE', 'EXPECTEDDATE',
                'EXP DATE', 'EXPDATE',
                'ETD', 'DUE DATE', 'DUEDATE',
                'PLAN DATE', 'PLANDATE',
                'TGL EXPECTED', 'TANGGAL EXPECTED', 'TANGGALEXPECTED'
            ]);

            $colItem   = $findCol($header, ['ITEM DESCRIPTION', 'ITEM DESC', 'ITEM', 'ITEMDESC']);

            if (!$colWoNo) {
                throw new \RuntimeException('Format header Excel tidak sesuai (WO No tidak ditemukan).');
            }

            // =========================
            // 2) Preload master produk (tetap dipakai utk nama/tipe_alur)
            // =========================
            $masters = Produksi::select('id', 'kode_produk', 'nama_produk', 'tipe_alur')->get();

            $masterByKode = [];
            $masterByNamaExact = [];

            foreach ($masters as $m) {
                $k = trim((string) $m->kode_produk);
                if ($k !== '') {
                    $masterByKode[(string)((int)$k)] = $m;
                }

                $n = mb_strtolower(trim((string) $m->nama_produk));
                if ($n !== '') $masterByNamaExact[$n] = $m;
            }

            // =========================
            // 3) Collect WO No for preload existing
            // =========================
            $noBatchList = [];
            $maxRow = count($data);

            for ($row = 2; $row <= $maxRow; $row++) {
                $r = $data[$row] ?? null;
                if (!$r) continue;

                $woNo = trim((string) ($r[$colWoNo] ?? ''));
                if ($woNo === '') continue;

                $description = trim((string) ($colDesc ? ($r[$colDesc] ?? '') : ''));
                $namaProdukC = trim((string) ($colNama ? ($r[$colNama] ?? '') : ''));

                $cekRencana = strtoupper($woNo . ' ' . $description . ' ' . $namaProdukC);
                if (strpos($cekRencana, 'RENCANA') !== false) continue;

                $noBatchList[] = $woNo; // no_batch tetap FULL woNo
            }

            $noBatchList = array_values(array_unique($noBatchList));

            $existingMap = collect();
            if (!empty($noBatchList)) {
                $existingMap = ProduksiBatch::whereIn('no_batch', $noBatchList)->get()->keyBy('no_batch');
            }

            // =========================
            // 4) Build payload upsert
            // =========================
            $payload = [];
            $now = now();

            for ($row = 2; $row <= $maxRow; $row++) {
                $r = $data[$row] ?? null;
                if (!$r) continue;

                $woNo        = trim((string) ($r[$colWoNo] ?? ''));
                $description = trim((string) ($colDesc ? ($r[$colDesc] ?? '') : ''));
                $namaProdukC = trim((string) ($colNama ? ($r[$colNama] ?? '') : ''));

                if ($woNo === '' && $description === '' && $namaProdukC === '') continue;

                $cekRencana = strtoupper($woNo . ' ' . $description . ' ' . $namaProdukC);
                if (strpos($cekRencana, 'RENCANA') !== false) continue;

                $woDateRaw   = $colWoDate ? ($r[$colWoDate] ?? null) : null;
                $expectedRaw = $colExp    ? ($r[$colExp] ?? null) : null;

                $itemDesc    = trim((string) ($colItem ? ($r[$colItem] ?? '') : ''));

                // nama prioritas: item_desc -> nama_product -> description
                $namaExcel = $itemDesc !== '' ? $itemDesc : ($namaProdukC !== '' ? $namaProdukC : $description);

                // parse tanggal
                $woDate       = $this->parseExcelDate($woDateRaw);
                $expectedDate = $this->parseExcelDate($expectedRaw);

                $autoDate = $woDate ?: $expectedDate ?: now()->format('Y-m-d');

                if (!$woDate) $woDate = $autoDate;
                if (!$expectedDate) $expectedDate = $woDate;

                $bulan = null;
                $tahun = null;
                if ($woDate) {
                    $bulan = (int) date('n', strtotime($woDate));
                    $tahun = (int) date('Y', strtotime($woDate));
                }

                // =====================================================
                // ✅ INI INTI PERMINTAAN KAMU:
                // no_batch = WO NO FULL (biarin tetap)
                // kode_batch = DIPOTONG jadi "100.01 FA" aja
                // =====================================================
                $noBatch   = $woNo;
                $kodeBatch = $this->extractKodeBatch($woNo);

                // parsing ringan untuk batch_ke/kode_produk (buat match master)
                $batchKe      = 1;
                $kodeProdukWo = null;
                if (preg_match('/^\s*(\d+)\.(\d+)\b/i', $woNo, $m)) {
                    $kodeProdukWo = (string) $m[1];
                    $batchKe      = (int) $m[2];
                }

                $old = $existingMap[$noBatch] ?? null;

                // =========================
                // MATCH master (tetap)
                // =========================
                $master = null;

                if ($kodeProdukWo !== null) {
                    $k = (string) ((int) $kodeProdukWo);
                    if (isset($masterByKode[$k])) $master = $masterByKode[$k];
                }

                if (!$master) {
                    $key = mb_strtolower(trim($namaExcel));
                    if ($key !== '' && isset($masterByNamaExact[$key])) {
                        $master = $masterByNamaExact[$key];
                    }
                }

                if (!$master) {
                    $namaForMatch = mb_strtolower(trim($namaExcel));
                    if ($namaForMatch !== '') {
                        $master = Produksi::whereRaw('LOWER(TRIM(nama_produk)) = ?', [$namaForMatch])->first();
                        if (!$master) $master = Produksi::whereRaw('LOWER(nama_produk) LIKE ?', [$namaForMatch . '%'])->first();
                        if (!$master) $master = Produksi::whereRaw('LOWER(nama_produk) LIKE ?', ['%' . $namaForMatch . '%'])->first();
                    }
                }

                $namaFinal       = $master ? $master->nama_produk : $namaExcel;
                $tipeAlurFinal   = $master ? $master->tipe_alur   : null;
                $produksiIdFinal = $master ? $master->id          : null;

                if ($old && !$master) {
                    $namaFinal       = $old->nama_produk ?: $namaFinal;
                    $tipeAlurFinal   = $old->tipe_alur;
                    $produksiIdFinal = $old->produksi_id;
                }

                $tglWeighing = ($old && $old->tgl_weighing) ? $old->tgl_weighing : $autoDate;

                $payload[] = [
                    'no_batch'      => $noBatch,     // FULL
                    'kode_batch'    => $kodeBatch,   // DIPOTONG: "100.01 FA"

                    'nama_produk'   => $namaFinal,
                    'produksi_id'   => $produksiIdFinal,
                    'batch_ke'      => $batchKe,
                    'bulan'         => $bulan,
                    'tahun'         => $tahun,
                    'tipe_alur'     => $tipeAlurFinal,

                    'wo_date'       => $woDate,
                    'expected_date' => $expectedDate,
                    'tgl_weighing'  => $tglWeighing,

                    'updated_at'    => $now,
                    'created_at'    => $now,
                ];
            }

            // =========================
            // 5) Upsert
            // =========================
            if (!empty($payload)) {
                $uniqueBy = ['no_batch'];
                $updateCols = [
                    'kode_batch',
                    'nama_produk',
                    'produksi_id',
                    'batch_ke',
                    'bulan',
                    'tahun',
                    'tipe_alur',
                    'wo_date',
                    'expected_date',
                    'tgl_weighing',
                    'updated_at',
                ];

                foreach (array_chunk($payload, 500) as $chunk) {
                    ProduksiBatch::upsert($chunk, $uniqueBy, $updateCols);
                }
            }

            // =========================
            // 6) Safety net: pastikan tgl_weighing tidak null
            // =========================
            if (!empty($noBatchList)) {
                ProduksiBatch::whereIn('no_batch', $noBatchList)
                    ->whereNull('tgl_weighing')
                    ->update([
                        'tgl_weighing' => now()->format('Y-m-d'),
                        'updated_at'   => now(),
                    ]);
            }

            // =========================
            // 7) Safety net: pastikan expected_date tidak null
            // =========================
            if (!empty($noBatchList)) {
                ProduksiBatch::whereIn('no_batch', $noBatchList)
                    ->whereNull('expected_date')
                    ->update([
                        'expected_date' => DB::raw('wo_date'),
                        'updated_at'    => now(),
                    ]);
            }

            DB::commit();

            return redirect()
                ->route('show-permintaan')
                ->with('ok', 'File jadwal produksi berhasil diimport.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withErrors(['file' => 'Gagal memproses file: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /* =========================================================
     * BULK DELETE (hapus banyak sekaligus)
     * =======================================================*/
    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:produksi_batches,id'],
        ]);

        ProduksiBatch::whereIn('id', $data['ids'])->delete();

        return back()->with('ok', 'Batch terpilih berhasil dihapus.');
    }

    /* =========================================================
     * EDIT TANGGAL-TANGGAL PER BATCH
     * =======================================================*/
    public function edit(ProduksiBatch $batch)
    {
        return view('produksi_batches.edit', compact('batch'));
    }

    public function update(Request $request, ProduksiBatch $batch)
    {
        $data = $request->validate([
            'wo_date'              => ['nullable', 'date'],
            'expected_date'        => ['nullable', 'date'],

            'tgl_mulai_weighing'   => ['nullable', 'date'],
            'tgl_weighing'         => ['nullable', 'date'],

            'tgl_mulai_mixing'     => ['nullable', 'date'],
            'tgl_mixing'           => ['nullable', 'date'],

            'tgl_mulai_capsule_filling' => ['nullable', 'date'],
            'tgl_capsule_filling'       => ['nullable', 'date'],

            'tgl_mulai_tableting'  => ['nullable', 'date'],
            'tgl_tableting'        => ['nullable', 'date'],

            'tgl_mulai_coating'    => ['nullable', 'date'],
            'tgl_coating'          => ['nullable', 'date'],

            'tgl_mulai_primary_pack'    => ['nullable', 'date'],
            'tgl_primary_pack'          => ['nullable', 'date'],

            'tgl_mulai_secondary_pack_1'=> ['nullable', 'date'],
            'tgl_secondary_pack_1'      => ['nullable', 'date'],
            'tgl_mulai_secondary_pack_2'=> ['nullable', 'date'],
            'tgl_secondary_pack_2'      => ['nullable', 'date'],

            'tgl_datang_granul'        => ['nullable', 'date'],
            'tgl_analisa_granul'       => ['nullable', 'date'],
            'tgl_rilis_granul'         => ['nullable', 'date'],

            'tgl_datang_tablet'        => ['nullable', 'date'],
            'tgl_analisa_tablet'       => ['nullable', 'date'],
            'tgl_rilis_tablet'         => ['nullable', 'date'],

            'tgl_datang_ruahan'        => ['nullable', 'date'],
            'tgl_analisa_ruahan'       => ['nullable', 'date'],
            'tgl_rilis_ruahan'         => ['nullable', 'date'],

            'tgl_datang_ruahan_akhir'  => ['nullable', 'date'],
            'tgl_analisa_ruahan_akhir' => ['nullable', 'date'],
            'tgl_rilis_ruahan_akhir'   => ['nullable', 'date'],

            'hari_kerja'    => ['nullable', 'integer', 'min:0'],
            'status_proses' => ['nullable', 'string', 'max:50'],
        ]);

        if (!empty($data['wo_date']) && empty($data['expected_date'])) {
            $data['expected_date'] = $data['wo_date'];
        }

        $batch->update($data);

        return redirect()
            ->route('show-permintaan')
            ->with('ok', 'Data batch produksi diperbarui.');
    }

    /* =========================================================
     * HELPER: ambil "100.01 FA" dari "100.01 FA-26/CP/I/1"
     * =======================================================*/
    private function extractKodeBatch(string $woNo): string
    {
        $woNo = trim($woNo);
        // rapihin whitespace (kalau ada newline dari excel)
        $woNo = preg_replace('/\s+/', ' ', $woNo);

        // Pola utama: "100.01 FA" / "104.01 EA" di awal string
        if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*([A-Za-z]{1,10})\b/', $woNo, $m)) {
            return trim($m[1] . ' ' . $m[2]);
        }

        // Fallback: potong sampai sebelum "-" atau "/"
        $cut = preg_split('/[-\/]/', $woNo);
        if (!empty($cut[0])) return trim($cut[0]);

        return $woNo;
    }

    /* =========================================================
     * HELPER PARSE TANGGAL EXCEL (SUPER KEBAL .xls/.xlsx)
     * =======================================================*/
    private function parseExcelDate($value): ?string
    {
        if ($value === null) return null;

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') return null;

            if (preg_match('/^\d+,\d+$/', $value)) {
                $value = str_replace(',', '.', $value);
            }

            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
                $value = str_replace('.', '', $value);
            }

            $upper = strtoupper($value);
            if (in_array($upper, ['-', 'N/A', 'NA', 'NULL'], true)) {
                return null;
            }
        }

        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject($value);
                return Carbon::instance($dt)->format('Y-m-d');
            } catch (\Throwable $e) {
                // lanjut parse string
            }
        }

        try {
            $str = is_string($value) ? $value : (string) $value;
            $str = trim($str);
            if ($str === '') return null;

            $str = str_replace(['.', '/'], '-', $str);

            if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $str)) {
                return Carbon::createFromFormat('d-m-Y', $str)->format('Y-m-d');
            }

            if (preg_match('/^\d{1,2}-\d{1,2}-\d{2}$/', $str)) {
                return Carbon::createFromFormat('d-m-y', $str)->format('Y-m-d');
            }

            if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $str)) {
                return Carbon::createFromFormat('Y-m-d', $str)->format('Y-m-d');
            }

            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
