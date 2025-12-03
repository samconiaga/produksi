<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Produksi;
use App\Models\ProduksiBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// composer require phpoffice/phpspreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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
        if ($perPage <= 0) {
            $perPage = 25;
        }

        $rows = ProduksiBatch::with('produksi')
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('nama_produk', 'like', "%{$q}%")
                        ->orWhere('no_batch', 'like', "%{$q}%")
                        ->orWhere('kode_batch', 'like', "%{$q}%");
                });
            })
            ->when($bulan !== null && $bulan !== '', function ($qb) use ($bulan) {
                $qb->where('bulan', (int) $bulan);
            })
            ->when($tahun !== null && $tahun !== '', function ($qb) use ($tahun) {
                $qb->where('tahun', (int) $tahun);
            })
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('wo_date')
            ->orderBy('id')
            ->paginate($perPage);

        return view('produksi_batches.index', compact(
            'rows',
            'q',
            'perPage',
            'bulan',
            'tahun'
        ));
    }

    /* =========================================================
     * PROSES UPLOAD EXCEL (Jadwal WO + Weighing)
     * =======================================================*/
    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx'],
        ]);

        $file = $request->file('file');

        DB::beginTransaction();
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet       = $spreadsheet->getActiveSheet();
            $highestRow  = $sheet->getHighestRow();

            // ==============================================
            // 1. BACA HEADER ROW → CARI INDEX KOLOM
            // ==============================================
            $highestCol      = $sheet->getHighestColumn();
            $highestColIndex = Coordinate::columnIndexFromString($highestCol);

            $cols = [
                'wo_no'        => null,
                'description'  => null,
                'nama_product' => null,
                'wo_date'      => null,
                'expected'     => null,
                'batch'        => null,
                'item_desc'    => null,
            ];

            for ($col = 1; $col <= $highestColIndex; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $header    = strtoupper(trim((string) $sheet->getCell($colLetter . '1')->getValue()));

                if ($header === '') {
                    continue;
                }

                if (strpos($header, 'WO NO') !== false) {
                    $cols['wo_no'] = $col;
                } elseif (strpos($header, 'DESCRIPTION') !== false) {
                    $cols['description'] = $col;
                } elseif (strpos($header, 'NAMA PRODUCT') !== false || strpos($header, 'NAMA PRODUK') !== false) {
                    $cols['nama_product'] = $col;
                } elseif (strpos($header, 'WO DATE') !== false) {
                    $cols['wo_date'] = $col;
                } elseif (strpos($header, 'EXPECTED') !== false) {
                    $cols['expected'] = $col;
                } elseif (strpos($header, 'BATCH') !== false) {
                    $cols['batch'] = $col;
                } elseif (strpos($header, 'ITEM DESCRIPTION') !== false) {
                    $cols['item_desc'] = $col;
                }
            }

            // Minimal: harus ada WO No & WO Date
            if (! $cols['wo_no'] || ! $cols['wo_date']) {
                throw new \RuntimeException('Format header Excel tidak sesuai (WO No / WO Date tidak ditemukan).');
            }

            // Helper ambil nilai cell berdasarkan index kolom
            $getCell = function (int $colIndex, int $row) use ($sheet) {
                $letter = Coordinate::stringFromColumnIndex($colIndex);
                return $sheet->getCell($letter . $row)->getValue();
            };

            // ==============================================
            // 2. LOOP ISI DATA
            // ==============================================
            for ($row = 2; $row <= $highestRow; $row++) {
                $woNo        = trim((string) $getCell($cols['wo_no'], $row));
                $description = $cols['description']
                    ? trim((string) $getCell($cols['description'], $row))
                    : '';
                $namaProdukC = $cols['nama_product']
                    ? trim((string) $getCell($cols['nama_product'], $row))
                    : '';

                $woDateRaw   = $getCell($cols['wo_date'], $row);
                $expectedRaw = $cols['expected']
                    ? $getCell($cols['expected'], $row)
                    : null;

                $batchExcel  = $cols['batch']
                    ? trim((string) $getCell($cols['batch'], $row))
                    : '';
                $itemDesc    = $cols['item_desc']
                    ? trim((string) $getCell($cols['item_desc'], $row))
                    : '';

                // Baris kosong → skip
                if ($woNo === '' && $description === '' && $namaProdukC === '') {
                    continue;
                }

                // Baris “RENCANA …” tidak disimpan
                $cekRencana = strtoupper($woNo . ' ' . $description . ' ' . $namaProdukC);
                if (strpos($cekRencana, 'RENCANA') !== false) {
                    continue;
                }

                // Pilih nama produk yang paling rapi:
                // prioritas: Item Description → Nama Product → Description
                $namaExcel = $itemDesc !== ''
                    ? $itemDesc
                    : ($namaProdukC !== '' ? $namaProdukC : $description);

                // Parse tanggal
                $woDate       = $this->parseExcelDate($woDateRaw);
                $expectedDate = $this->parseExcelDate($expectedRaw);

                // Bulan & Tahun dari WO Date (kalau ada)
                $bulan = null;
                $tahun = null;
                if ($woDate) {
                    $bulan = (int) date('n', strtotime($woDate));
                    $tahun = (int) date('Y', strtotime($woDate));
                }

                // ===============================
                // PARSE WO NO → kode batch & batch_ke
                // ===============================
                $noBatch   = $woNo;
                $kodeBatch = $batchExcel ?: $woNo; // default fallback
                $batchKe   = 1;

                if (preg_match('/^\s*(\d+)\.(\d+)\s*EA/i', $woNo, $m)) {
                    $kodeProdukWo = (int) $m[1];           // 20
                    $batchKe      = (int) $m[2];           // 3
                    $kodeBatch    = sprintf('%d%02d EA', $kodeProdukWo, $batchKe); // 2003 EA
                }

                // ============= SINKRON KE MASTER PRODUK =============
                // Normalisasi nama produk dari Excel
                $namaForMatch = mb_strtolower(trim($namaExcel));

                // 1) Cocokkan exact (case-insensitive)
                $master = Produksi::whereRaw('LOWER(TRIM(nama_produk)) = ?', [$namaForMatch])->first();

                // 2) Kalau belum ketemu, coba nama yang diawali teks tsb
                if (! $master) {
                    $master = Produksi::whereRaw('LOWER(nama_produk) LIKE ?', [$namaForMatch . '%'])
                        ->first();
                }

                // 3) Kalau masih belum ketemu, coba yang mengandung teks tsb
                if (! $master) {
                    $master = Produksi::whereRaw('LOWER(nama_produk) LIKE ?', ['%' . $namaForMatch . '%'])
                        ->first();
                }

                $namaSinkron = $master ? $master->nama_produk : $namaExcel;
                $tipeAlur    = $master ? $master->tipe_alur    : null;
                $produksiId  = $master ? $master->id           : null;

                // Cek apakah batch ini sudah pernah diimport
                $batch = ProduksiBatch::where('no_batch', $noBatch)->first();

                if (! $batch) {
                    // BATCH BARU
                    ProduksiBatch::create([
                        'no_batch'   => $noBatch,
                        'kode_batch' => $kodeBatch,

                        'nama_produk' => $namaSinkron,
                        'produksi_id' => $produksiId,
                        'batch_ke'    => $batchKe,
                        'bulan'       => $bulan,
                        'tahun'       => $tahun,
                        'tipe_alur'   => $tipeAlur,

                        'wo_date'       => $woDate,
                        'expected_date' => $expectedDate,

                        'tgl_mulai_weighing' => null,
                        'tgl_weighing'       => $woDate,

                        'tgl_mulai_mixing'          => null,
                        'tgl_mixing'                => null,
                        'tgl_mulai_capsule_filling' => null,
                        'tgl_capsule_filling'       => null,
                        'tgl_mulai_tableting'       => null,
                        'tgl_tableting'             => null,
                        'tgl_mulai_coating'         => null,
                        'tgl_coating'               => null,
                        'tgl_mulai_primary_pack'    => null,
                        'tgl_primary_pack'          => null,
                        'tgl_mulai_secondary_pack_1'=> null,
                        'tgl_secondary_pack_1'      => null,
                        'tgl_mulai_secondary_pack_2'=> null,
                        'tgl_secondary_pack_2'      => null,

                        'tgl_datang_granul'        => null,
                        'tgl_analisa_granul'       => null,
                        'tgl_rilis_granul'         => null,
                        'tgl_datang_tablet'        => null,
                        'tgl_analisa_tablet'       => null,
                        'tgl_rilis_tablet'         => null,
                        'tgl_datang_ruahan'        => null,
                        'tgl_analisa_ruahan'       => null,
                        'tgl_rilis_ruahan'         => null,
                        'tgl_datang_ruahan_akhir'  => null,
                        'tgl_analisa_ruahan_akhir' => null,
                        'tgl_rilis_ruahan_akhir'   => null,

                        'qty_batch'         => null,
                        'status_qty_batch'  => null,
                        'tgl_konfirmasi_produksi' => null,
                        'tgl_terima_jobsheet'     => null,
                        'status_jobsheet'         => null,
                        'catatan_jobsheet'        => null,
                        'tgl_sampling'            => null,
                        'status_sampling'         => null,
                        'catatan_sampling'        => null,
                        'tgl_qc_kirim_coa'        => null,
                        'tgl_qa_terima_coa'       => null,
                        'status_coa'              => null,
                        'catatan_coa'             => null,
                        'status_review'           => null,
                        'tgl_review'              => null,
                        'catatan_review'          => null,

                        'hari_kerja'    => null,
                        'status_proses' => null,
                    ]);
                } else {
                    // BATCH SUDAH ADA → update header saja
                    $updateData = [
                        'nama_produk'   => $namaSinkron,
                        'produksi_id'   => $produksiId,
                        'batch_ke'      => $batchKe,
                        'kode_batch'    => $kodeBatch,
                        'bulan'         => $bulan,
                        'tahun'         => $tahun,
                        'tipe_alur'     => $tipeAlur,
                        'wo_date'       => $woDate,
                        'expected_date' => $expectedDate,
                    ];

                    if (! $batch->tgl_weighing && $woDate) {
                        $updateData['tgl_weighing'] = $woDate;
                    }

                    $batch->update($updateData);
                }
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

        $batch->update($data);

        return redirect()
            ->route('show-permintaan')
            ->with('ok', 'Data batch produksi diperbarui.');
    }

    /* =========================================================
     * HELPER PARSE TANGGAL EXCEL
     * =======================================================*/
    private function parseExcelDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject($value);
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
