<?php

use Illuminate\Support\Facades\Route;

/* ===== Controller imports ===== */
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\UserManagement\UserController;

use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\ProduksiBatchController;

// WeighingController DIHAPUS karena sudah digabung di Jadwal Produksi
use App\Http\Controllers\MixingController;
use App\Http\Controllers\CapsuleFillingController;
use App\Http\Controllers\TabletingController;
use App\Http\Controllers\CoatingController;
use App\Http\Controllers\QcReleaseController;
use App\Http\Controllers\UjiCoaController;
use App\Http\Controllers\PrimaryPackController;
use App\Http\Controllers\SecondaryPackController;
use App\Http\Controllers\QtyBatchController;
use App\Http\Controllers\QcJobSheetController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\SamplingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\QcGranulController;
use App\Http\Controllers\QcTabletController;
use App\Http\Controllers\QcRuahanController;
use App\Http\Controllers\QcRuahanAkhirController;

/* ============================================================
 * AUTH
 * ============================================================ */
Route::get('/login',  [LoginController::class, 'login'])->name('login');
Route::post('/login', [LoginController::class, 'store']);

/* ============================================================
 * PROTECTED AREA
 * ============================================================ */
Route::middleware(['auth'])->group(function () {

    /* ---------------- Dashboard ---------------- */
    Route::get('/', fn () => redirect()->route('dashboard'))->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* ============================================================
     * USER MANAGEMENT (ADMIN ONLY)
     * ============================================================ */
    Route::middleware(['admin'])->group(function () {

        // PRODUKSI
        Route::get('/show-produksi',           [UserController::class, 'showProduksi'])->name('show-produksi');
        Route::post('/show-produksi',          [UserController::class, 'storeProduksi']);
        Route::get('/show-produksi/{id}/edit', [UserController::class, 'editProduksi'])->name('edit-produksi');
        Route::put('/show-produksi/{id}',      [UserController::class, 'updateProduksi']);
        Route::delete('/show-produksi/{id}',   [UserController::class, 'destroy'])->name('delete-produksi');

        // QC
        Route::get('/show-qc',           [UserController::class, 'showQC'])->name('show-qc');
        Route::post('/show-qc',          [UserController::class, 'storeQC']);
        Route::get('/show-qc/{id}/edit', [UserController::class, 'editQC'])->name('edit-qc');
        Route::put('/show-qc/{id}',      [UserController::class, 'updateQC']);
        Route::delete('/show-qc/{id}',   [UserController::class, 'destroy'])->name('delete-qc');

        // QA
        Route::get('/show-qa',           [UserController::class, 'showQA'])->name('show-qa');
        Route::post('/show-qa',          [UserController::class, 'storeQA']);
        Route::get('/show-qa/{id}/edit', [UserController::class, 'editQA'])->name('edit-qa');
        Route::put('/show-qa/{id}',      [UserController::class, 'updateQA']);
        Route::delete('/show-qa/{id}',   [UserController::class, 'destroy'])->name('delete-qa');

        // PPIC
        Route::get('/show-ppic',           [UserController::class, 'showPPIC'])->name('show-ppic');
        Route::post('/show-ppic',          [UserController::class, 'storePPIC']);
        Route::get('/show-ppic/{id}/edit', [UserController::class, 'editPPIC'])->name('edit-ppic');
        Route::put('/show-ppic/{id}',      [UserController::class, 'updatePPIC']);
        Route::delete('/show-ppic/{id}',   [UserController::class, 'destroy'])->name('delete-ppic');

        // Login sebagai PPIC (impersonate)
        Route::get('/login-as-ppic/{id}', [UserController::class, 'loginAsPPIC'])->name('login-as-ppic');

        /* ---- MASTER PRODUK / PRODUKSI ---- */
        Route::resource('produksi', ProduksiController::class)->names('produksi');
    });

    /* ============================================================
     * PRODUKSI (Admin + Produksi)
     * ============================================================ */

    /*
     * Jadwal Produksi (Upload Work Order)
     * - Di sini sekaligus menyimpan tanggal Weighing (mulai & selesai)
     *   berdasarkan WO Date.
     */
    Route::get('/permintaan-bahan-baku',               [ProduksiBatchController::class, 'index'])->name('show-permintaan');
    Route::post('/permintaan-bahan-baku/upload',       [ProduksiBatchController::class, 'upload'])->name('permintaan.upload');
    Route::get('/permintaan-bahan-baku/{batch}/edit',  [ProduksiBatchController::class, 'edit'])->name('edit-permintaan');
    Route::put('/permintaan-bahan-baku/{batch}',       [ProduksiBatchController::class, 'update'])->name('update-permintaan');

    /*
     * TIDAK ADA LAGI ROUTE WEIGHING TERPISAH
     * Proses Weighing dianggap sudah "terjadwal" dari hasil upload WO.
     * Tanggal mulai & selesai Weighing ada di kolom:
     * - tgl_mulai_weighing
     * - tgl_weighing
     */

/* --- Mixing (Realtime) --- */
Route::prefix('mixing')->name('mixing.')
    ->middleware('role:Admin,Produksi')
    ->group(function () {

        Route::get('/', [MixingController::class, 'index'])->name('index');
        Route::get('/history', [MixingController::class, 'history'])->name('history');

        // Start (realtime)
        Route::post('/{batch}/start', [MixingController::class, 'start'])->name('start');

        // Stop (realtime)
        Route::post('/{batch}/stop', [MixingController::class, 'stop'])->name('stop');
    });


    /* --- Capsule Filling --- */
    Route::prefix('capsule-filling')->name('capsule-filling.')
        ->middleware('role:Admin,Produksi')
        ->group(function () {
            // List pending (belum selesai capsule filling)
            Route::get('/', [CapsuleFillingController::class, 'index'])->name('index');

            // Riwayat batch yang sudah selesai Capsule Filling
            Route::get('/history', [CapsuleFillingController::class, 'history'])->name('history');

            // Konfirmasi Capsule Filling satu batch
            Route::post('/{batch}/confirm', [CapsuleFillingController::class, 'confirm'])->name('confirm');
        });

   /* --- Tableting --- */
Route::prefix('tableting')->name('tableting.')
    ->middleware('role:Admin,Produksi')
    ->group(function () {
        Route::get('/',        [TabletingController::class, 'index'])->name('index');
        Route::get('/history', [TabletingController::class, 'history'])->name('history');

        // Realtime start/stop
        Route::post('/{batch}/start', [TabletingController::class, 'start'])->name('start');
        Route::post('/{batch}/stop',  [TabletingController::class, 'stop'])->name('stop');
    });


  /* --- Coating --- */
Route::prefix('coating')
    ->name('coating.')
    ->middleware(['role:Admin,Produksi,QA'])
    ->group(function () {

        // list batch yang belum selesai Coating
        Route::get('/',        [CoatingController::class, 'index'])->name('index');

        // riwayat batch yang sudah selesai Coating
        Route::get('/history', [CoatingController::class, 'history'])->name('history');

        // halaman detail per batch (start/stop step coating)
        Route::get('/{batch}', [CoatingController::class, 'show'])->name('show');

        // realtime start / stop per step
        Route::post('/{batch}/start', [CoatingController::class, 'start'])->name('start');
        Route::post('/{batch}/stop',  [CoatingController::class, 'stop'])->name('stop');

        // mesin 2 (EAZ)
        Route::post('/{batch}/split-eaz',    [CoatingController::class, 'splitEaz'])->name('split-eaz');
        Route::delete('/{batch}/destroy-eaz',[CoatingController::class, 'destroyEaz'])->name('destroy-eaz');
    });


/* --- Primary Pack --- */
Route::prefix('primary-pack')->name('primary-pack.')
    ->middleware('role:Admin,Produksi')
    ->group(function () {
        Route::get('/',        [PrimaryPackController::class, 'index'])->name('index');
        Route::get('/history', [PrimaryPackController::class, 'history'])->name('history');

        // Start / Stop style
        Route::post('/{batch}/start', [PrimaryPackController::class, 'start'])->name('start');
        Route::post('/{batch}/stop',  [PrimaryPackController::class, 'stop'])->name('stop');
    });


/* --- Secondary Pack --- */
Route::prefix('secondary-pack')->name('secondary-pack.')
    ->middleware('role:Admin,Produksi')
    ->group(function () {
        Route::get('/',        [SecondaryPackController::class, 'index'])->name('index');
        Route::get('/history', [SecondaryPackController::class, 'history'])->name('history');

        // Start / Stop style
        Route::post('/{batch}/start', [SecondaryPackController::class, 'start'])->name('start');
        Route::post('/{batch}/stop',  [SecondaryPackController::class, 'stop'])->name('stop');

        // Qty Batch sesudah Secondary selesai
        Route::get('/{batch}/qty',  [SecondaryPackController::class, 'qtyForm'])->name('qty.form');
        Route::post('/{batch}/qty', [SecondaryPackController::class, 'qtySave'])->name('qty.save');
    });

// =================== QC GRANUL ===================
Route::get('/qc-granul', [QcGranulController::class, 'index'])
    ->name('qc-granul.index');
Route::get('/qc-granul/history', [QcGranulController::class, 'history'])
    ->name('qc-granul.history');
Route::put('/qc-granul/{batch}', [QcGranulController::class, 'update'])
    ->name('qc-granul.update');

// =================== QC TABLET ===================
Route::get('/qc-tablet', [QcTabletController::class, 'index'])
    ->name('qc-tablet.index');
Route::get('/qc-tablet/history', [QcTabletController::class, 'history'])
    ->name('qc-tablet.history');
Route::put('/qc-tablet/{batch}', [QcTabletController::class, 'update'])
    ->name('qc-tablet.update');

// =================== QC RUAHAN ===================
Route::get('/qc-ruahan', [QcRuahanController::class, 'index'])
    ->name('qc-ruahan.index');
Route::get('/qc-ruahan/history', [QcRuahanController::class, 'history'])
    ->name('qc-ruahan.history');
Route::put('/qc-ruahan/{batch}', [QcRuahanController::class, 'update'])
    ->name('qc-ruahan.update');

// ============== QC RUAHAN AKHIR ==================
Route::get('/qc-ruahan-akhir', [QcRuahanAkhirController::class, 'index'])
    ->name('qc-ruahan-akhir.index');
Route::get('/qc-ruahan-akhir/history', [QcRuahanAkhirController::class, 'history'])
    ->name('qc-ruahan-akhir.history');
Route::put('/qc-ruahan-akhir/{batch}', [QcRuahanAkhirController::class, 'update'])
    ->name('qc-ruahan-akhir.update');

    /* --- Qty Batch menu (setelah Secondary Pack) --- */
    Route::prefix('qty-batch')->name('qty-batch.')
        ->middleware('role:Admin,Produksi')
        ->group(function () {

            // list qty batch (aktif)
            Route::get('/', [QtyBatchController::class, 'index'])->name('index');

            // riwayat qty batch (sudah dikonfirmasi)
            Route::get('/history', [QtyBatchController::class, 'history'])->name('history');

            // konfirmasi qty
            Route::post('/{batch}/confirm', [QtyBatchController::class, 'confirm'])
                ->name('confirm');

            // tolak qty
            Route::post('/{batch}/reject', [QtyBatchController::class, 'reject'])
                ->name('reject');
        });

    /*
    |--------------------------------------------------------------------------
    | Job Sheet QC
    |--------------------------------------------------------------------------
    */
    Route::prefix('qc-jobsheet')->name('qc-jobsheet.')
        ->middleware(['role:Admin,Produksi,QA'])
        ->group(function () {

            // List utama (yang masih dikerjakan)
            Route::get('/', [QcJobSheetController::class, 'index'])
                ->name('index');

            // Halaman riwayat (yang sudah dikonfirmasi)
            Route::get('/history', [QcJobSheetController::class, 'history'])
                ->name('history');

            // Form Isi / Ubah Job Sheet
            Route::get('/{batch}/edit', [QcJobSheetController::class, 'edit'])
                ->name('edit');

            // Simpan Job Sheet
            Route::post('/{batch}', [QcJobSheetController::class, 'update'])
                ->name('update');

            // Konfirmasi Job Sheet → status 'confirmed' + kirim ke Review
            Route::post('/{batch}/confirm', [QcJobSheetController::class, 'confirm'])
                ->name('confirm');
        });

    /* --- Modul COA --- */
    Route::prefix('coa')->name('coa.')
        ->middleware(['role:Admin,Produksi,QA'])
        ->group(function () {

            // List COA aktif
            Route::get('/', [CoaController::class, 'index'])
                ->name('index');

            // Riwayat COA
            Route::get('/riwayat', [CoaController::class, 'history'])
                ->name('history');

            // Form isi / edit COA
            Route::get('/{batch}/edit', [CoaController::class, 'edit'])
                ->name('edit');

            // Simpan COA
            Route::post('/{batch}', [CoaController::class, 'update'])
                ->name('update');

            // Konfirmasi COA (QA terima & pindah ke riwayat)
            Route::post('/{batch}/confirm', [CoaController::class, 'confirm'])
                ->name('confirm');
        });

    /* --- Modul Sampling --- */
    Route::prefix('sampling')->name('sampling.')
        ->middleware(['auth', 'role:Admin,Produksi,QA,QC'])
        ->group(function () {

            Route::get('/', [SamplingController::class, 'index'])->name('index');

            // Riwayat Sampling
            Route::get('/history', [SamplingController::class, 'history'])->name('history');

            // Accept (tetap di index)
            Route::post('/{batch}/acc', [SamplingController::class, 'acc'])->name('acc');

            // Konfirmasi (pindah ke riwayat + review)
            Route::post('/{batch}/confirm', [SamplingController::class, 'confirm'])->name('confirm');

            // Tolak (langsung ke riwayat)
            Route::post('/{batch}/reject', [SamplingController::class, 'reject'])->name('reject');
        });

    /* --- Review After Secondary Pack --- */
    Route::prefix('review')
        ->name('review.')
        ->middleware('role:Admin,QA')  // yang boleh review: Admin + QA
        ->group(function () {
            Route::get('/',          [ReviewController::class, 'index'])->name('index');
            Route::get('/history',   [ReviewController::class, 'history'])->name('history');

            Route::post('/{batch}/hold',    [ReviewController::class, 'hold'])->name('hold');
            Route::post('/{batch}/release', [ReviewController::class, 'release'])->name('release');
            Route::post('/{batch}/reject',  [ReviewController::class, 'reject'])->name('reject');
        });

    Route::prefix('release-after-secondary')
        ->name('release.')
        ->middleware(['auth', 'role:Admin,QA'])
        ->group(function () {

            // list batch yang sudah di-Release QA
            Route::get('/', [ReleaseController::class, 'index'])
                ->name('index');

            // print form penyerahan produksi
            Route::get('/print', [ReleaseController::class, 'print'])
                ->name('print');

            // tampilan logsheet
            Route::get('/logsheet', [ReleaseController::class, 'logsheet'])
                ->name('logsheet');

            // export logsheet ke CSV
            Route::get('/logsheet-export', [ReleaseController::class, 'exportCsv'])
                ->name('logsheet.export');
        });

    /* ============================================================
     * QC RELEASE (Admin + QA)
     * ============================================================ */
    Route::prefix('qc-release')->name('qc-release.')
        ->middleware('role:Admin,QA')
        ->group(function () {
            Route::get('/',         [QcReleaseController::class, 'index'])->name('index');
            Route::get('/history',  [QcReleaseController::class, 'history'])->name('history');
            Route::put('/{batch}',  [QcReleaseController::class, 'update'])->name('update');
        });

    /* ============================================================
     * HASIL UJI COA (Admin + QC)
     * ============================================================ */
    Route::prefix('uji-coa')->name('uji-coa.')
        ->middleware('role:Admin,QC')
        ->group(function () {
            Route::get('/',              [UjiCoaController::class, 'index'])->name('index');
            Route::get('/{id}/edit',     [UjiCoaController::class, 'edit'])->name('edit');
            Route::put('/{id}',          [UjiCoaController::class, 'update'])->name('update');
            Route::get('/{id}/confirm',  [UjiCoaController::class, 'confirmForm'])->name('confirm.form');
            Route::put('/{id}/confirm',  [UjiCoaController::class, 'confirmUpdate'])->name('confirm.update');
        });

    /* ============================================================
     * PROFILE & LOGOUT
     * ============================================================ */
    Route::post('/logout', [UserController::class, 'logout'])->name('logout');
    Route::get('show-profile',              [UserController::class, 'profile'])->name('show-profile');
    Route::put('show-profile/general',      [UserController::class, 'updateGeneral'])->name('edit-general');
    Route::put('show-profile',              [UserController::class, 'updatePassword'])->name('edit-password');
});
