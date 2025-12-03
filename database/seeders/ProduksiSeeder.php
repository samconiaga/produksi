<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProduksiSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Matikan FK sementara
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('produksi_batches')->truncate();
        DB::table('produksi')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        /*
         | =======================================================
         | MASTER PRODUK PRODUKSI
         | -------------------------------------------------------
         | - kode_produk      : kolom "id" di sheet Database Produk / WO
         | - nama_produk      : DISESUAIKAN dengan nama di WO
         | - kategori_produk  : ETHICAL / OTC / TRADISIONAL
         | - est_qty          : est Qty dari master (kalau ada)
         | - bentuk_sediaan   : mapping bentuk fisik (Tablet, Kapsul, dsb)
         | - tipe_alur        : mapping flow proses (TABLET_NON_SALUT, dsb)
         | - leadtime_target  : target leadtime (hari)
         | - expired_years    : MASA KADALUARSA (TAHUN) → default 4 tahun
         |
         | Tambahan:
         | - Beberapa produk lama yang tidak jelas kodenya di Excel
         |   diberi kode FIKTIF 130–134 (lihat comment di bawah).
         |   Nanti kalau sudah ketemu kode aslinya di Excel → tinggal ganti.
         | =======================================================
         */

        $produkList = [

            // ================== TABLET / KAPSUL ETHICAL ==================
            ['kode' => '1',    'nama' => 'Samcoral Forte',          'kategori' => 'ETHICAL', 'est' => 1000, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '100',  'nama' => 'Samrox -20',              'kategori' => 'ETHICAL', 'est' => 2000, 'bentuk' => 'Kapsul',                'tipe' => 'KAPSUL',           'lt' => 30],
            ['kode' => '104',  'nama' => 'Samcodin',                'kategori' => 'ETHICAL', 'est' => 3500, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '106',  'nama' => 'Masflu Forte',            'kategori' => 'ETHICAL', 'est' => 1000, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '107',  'nama' => 'Masneuro',                'kategori' => 'ETHICAL', 'est' => 1000, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '108',  'nama' => 'Samcobion',               'kategori' => 'ETHICAL', 'est' => 2000, 'bentuk' => 'Kapsul',                'tipe' => 'KAPSUL',           'lt' => 30],

            ['kode' => '109A', 'nama' => "Bundavin 30's",           'kategori' => 'ETHICAL', 'est' => 3667, 'bentuk' => 'Tablet Salut Gula',     'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '109B', 'nama' => "Bundavin Dus 100's",      'kategori' => 'ETHICAL', 'est' => 1100, 'bentuk' => 'Tablet Salut Gula',     'tipe' => 'TABLET_SALUT',     'lt' => 30],

            ['kode' => '112',  'nama' => 'Samcalvit tab',           'kategori' => 'ETHICAL', 'est' => 1000, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '113',  'nama' => 'Samquinor',               'kategori' => 'ETHICAL', 'est' => 1200, 'bentuk' => 'Tablet Film Coating',   'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '114',  'nama' => 'Phenzacol',               'kategori' => 'ETHICAL', 'est' => 1000, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '115A', 'nama' => 'Samcovask 5 mg 100s',     'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '117',  'nama' => 'Samcofenac 50',           'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Film Coating',   'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '118',  'nama' => 'Diclofenac Sodium',       'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Film Coating',   'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '119A', 'nama' => 'Samcovask 10 mg 100s',    'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],

            ['kode' => '12',   'nama' => 'Mastatin 10',             'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '122',  'nama' => 'Toxaprim Suspensi 50ml',  'kategori' => 'ETHICAL', 'est' => 1200, 'bentuk' => 'Dry Syrup',              'tipe' => 'DRY_SYRUP',        'lt' => 25],
            ['kode' => '128',  'nama' => 'Amoxicillin Tryhidrate',  'kategori' => 'ETHICAL', 'est' => 2000, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],

            ['kode' => '15',   'nama' => 'Corizine 10 mg',          'kategori' => 'ETHICAL', 'est' => 1000, 'bentuk' => 'Tablet Film Coating',   'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '20',   'nama' => 'Coric 300',               'kategori' => 'ETHICAL', 'est' => 3000, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '22',   'nama' => 'Samcohistin',             'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '24',   'nama' => 'Mastatin 20',             'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '27',   'nama' => 'Samcoxol Tab',            'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '30',   'nama' => 'Samtaflam 50',            'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Film Coating',   'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '33',   'nama' => 'Samcodexon',              'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],

            ['kode' => '41',   'nama' => 'Samcopan Plus',           'kategori' => 'ETHICAL', 'est' => 1000, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '46',   'nama' => 'Ciprofloxacin HCL',       'kategori' => 'ETHICAL', 'est' => 1200, 'bentuk' => 'Tablet Film Coating',   'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '5',    'nama' => 'Coric 100',               'kategori' => 'ETHICAL', 'est' => 1500, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '51',   'nama' => 'AMLODIPINE 5MG',          'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '52',   'nama' => 'AMLODIPINE 10MG',         'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '6',    'nama' => 'Folic 400',               'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '64',   'nama' => 'Samfetra 500mg',          'kategori' => 'ETHICAL', 'est' => 2500, 'bentuk' => 'Tablet Film Coating',   'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '65',   'nama' => 'Horvita G 100s',          'kategori' => 'ETHICAL', 'est' => 1100, 'bentuk' => 'Tablet Film Coating',   'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '71',   'nama' => 'Samoxin Dry Syr',         'kategori' => 'ETHICAL', 'est' => 5000, 'bentuk' => 'Dry Syrup',              'tipe' => 'DRY_SYRUP',        'lt' => 25],

            // di WO tertulis SAMTACID TABLET → non salut
            ['kode' => '78',   'nama' => 'SAMTACID TABLET',         'kategori' => 'ETHICAL', 'est' => 1000, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '86',   'nama' => 'Diaramid',                'kategori' => 'ETHICAL', 'est' => 3000, 'bentuk' => 'Tablet Film Coating',   'tipe' => 'TABLET_SALUT',     'lt' => 30],

            // di WO tertulis SAMMOXIN DS
            ['kode' => '89',   'nama' => 'SAMMOXIN DS',             'kategori' => 'ETHICAL', 'est' => 1200, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],

            // di WO tertulis COSTAN FK
            ['kode' => '90',   'nama' => 'COSTAN FK',               'kategori' => 'ETHICAL', 'est' => 1000, 'bentuk' => 'Tablet Film Coating',   'tipe' => 'TABLET_SALUT',     'lt' => 30],

            ['kode' => '97',   'nama' => 'Samconal Syr',            'kategori' => 'ETHICAL', 'est' => 1100, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],
            ['kode' => '98',   'nama' => 'SAMCONAL FORTE',          'kategori' => 'ETHICAL', 'est' => 1100, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],

            // ================== OTC ==================
            ['kode' => '14',   'nama' => 'Samcorbex Strip',         'kategori' => 'OTC',     'est' => 1000, 'bentuk' => 'Tablet Film Coating',     'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '18A',  'nama' => 'Betamin 2000s',           'kategori' => 'OTC',     'est' => 105,  'bentuk' => 'Tablet Salut Gula',     'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '18B',  'nama' => 'Betamin 100s',            'kategori' => 'OTC',     'est' => 2100, 'bentuk' => 'Tablet Salut Gula',     'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '29',   'nama' => 'Evitan 60s',              'kategori' => 'OTC',     'est' => 1666, 'bentuk' => 'Tablet Salut Gula',     'tipe' => 'TABLET_SALUT',     'lt' => 30],

            // COD LIVER OIL diubah jadi CLO 50 & CLO 100 sesuai WO
            ['kode' => '49A',  'nama' => 'CLO 50',                  'kategori' => 'OTC',     'est' => 10000,'bentuk' => 'CLO',                  'tipe' => 'CLO',              'lt' => 210],
            ['kode' => '47',   'nama' => 'CLO 100',                 'kategori' => 'OTC',     'est' => 12000,'bentuk' => 'CLO',                  'tipe' => 'CLO',              'lt' => 210],

            ['kode' => '57B',  'nama' => 'Samcermin 1500s',         'kategori' => 'OTC',     'est' => 1333, 'bentuk' => 'Tablet Salut Gula',     'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '57C',  'nama' => 'VIT BC+B12 BOTOL',        'kategori' => 'OTC',     'est' => 1500, 'bentuk' => 'Tablet Salut Gula',     'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '59B',  'nama' => 'Vit BC+B12 1500s',        'kategori' => 'OTC',     'est' => 1200, 'bentuk' => 'Tablet Salut Gula',     'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '59C',  'nama' => 'Vit BC+B12 120 Kg',       'kategori' => 'OTC',     'est' => 600,  'bentuk' => 'Tablet Salut Gula',     'tipe' => 'TABLET_SALUT',     'lt' => 30],
            ['kode' => '59D',  'nama' => 'Vit BC+B12 Strip',        'kategori' => 'OTC',     'est' => 1500, 'bentuk' => 'Tablet Salut Gula',     'tipe' => 'TABLET_SALUT',     'lt' => 30],

            // ================== TAMBAHAN DARI SEEDER LAMA (KODE FIKTIF) ==================
            // kalau nanti di Excel ketemu kode aslinya → ganti 'kode' di bawah ini.

            // 130: SAMCONAL (umum) – masih ETHICAL, non salut
            ['kode' => '130',  'nama' => 'SAMCONAL',                'kategori' => 'ETHICAL', 'est' => 2000, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],

            // 131: SAMMOXIN FK – varian lain selain SAMMOXIN DS
            ['kode' => '131',  'nama' => 'SAMMOXIN FK',             'kategori' => 'ETHICAL', 'est' => 2000, 'bentuk' => 'Tablet Non Salut',      'tipe' => 'TABLET_NON_SALUT', 'lt' => 30],

            // 132: DOMESTRIUM TABLET – film coating
            ['kode' => '120',  'nama' => 'DOMESTRIUM TABLET',       'kategori' => 'ETHICAL', 'est' => 2000, 'bentuk' => 'Tablet Film Coating',   'tipe' => 'TABLET_SALUT',     'lt' => 30],

            // 133: VIT BC+B12 BOTOL – masuk OTC
            ['kode' => '133',  'nama' => 'VIT BC+B12 BOTOL',        'kategori' => 'OTC',     'est' => 2000, 'bentuk' => 'Tablet Salut Gula',     'tipe' => 'TABLET_SALUT',     'lt' => 30],

            // 134: Samcefad 500 – kapsul antibiotik
            ['kode' => '134',  'nama' => 'Samcefad 500',            'kategori' => 'ETHICAL', 'est' => 2000, 'bentuk' => 'Kapsul',                'tipe' => 'KAPSUL',           'lt' => 30],


            // ================== TRADISIONAL ==================
            ['kode' => '16',   'nama' => 'OBAT GIGI BKT',           'kategori' => 'TRADISIONAL', 'est' => 100000, 'bentuk' => 'Obat Luar', 'tipe' => 'CAIRAN_LUAR', 'lt' => 30],
            ['kode' => '48',   'nama' => 'Obat Gosok Bunga Merah',  'kategori' => 'TRADISIONAL', 'est' => 4875,   'bentuk' => 'Obat Luar', 'tipe' => 'CAIRAN_LUAR', 'lt' => 30],
        ];

        // ============================
        //  INSERT KE TABEL PRODUKSI
        // ============================
        $rows = [];
        foreach ($produkList as $p) {
            $rows[] = [
                'kode_produk'      => $p['kode'],
                'nama_produk'      => $p['nama'],
                'kategori_produk'  => $p['kategori'],
                'est_qty'          => $p['est'],
                'bentuk_sediaan'   => $p['bentuk'],
                'tipe_alur'        => $p['tipe'],
                'leadtime_target'  => $p['lt'],

                // default masa kadaluarsa 4 tahun
                'expired_years'    => 4,

                'is_aktif'         => 1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        DB::table('produksi')->insert($rows);
    }
}
