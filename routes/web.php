<?php

use Illuminate\Support\Facades\Route;

/* ===== Controller imports ===== */
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\UserManagement\UserController;

use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\ProduksiBatchController;

use App\Http\Controllers\WeighingController;
use App\Http\Controllers\MixingController;
use App\Http\Controllers\CapsuleFillingController;
use App\Http\Controllers\TabletingController;
use App\Http\Controllers\CoatingController;
use App\Http\Controllers\PrimaryPackController;
use App\Http\Controllers\SecondaryPackController;
use App\Http\Controllers\QtyBatchController;

use App\Http\Controllers\QcJobSheetController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\SamplingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\QcReleaseController;

use App\Http\Controllers\QcGranulController;
use App\Http\Controllers\QcTabletController;
use App\Http\Controllers\QcRuahanController;
use App\Http\Controllers\QcRuahanAkhirController;

use App\Http\Controllers\GudangReleaseController;
use App\Http\Controllers\UjiCoaController;

use App\Http\Controllers\SignatureController;
use App\Http\Controllers\HoldingController;

/* ✅ TRACKING MENU BARU */
use App\Http\Controllers\BatchTrackingController;
use App\Http\Controllers\GojController;
use App\Http\Controllers\SpvController;
use App\Http\Controllers\SpvReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Route definitions aligned with layouts/app.blade.php menu & access rules.
|
*/

/* ============================================================
 * AUTH
 * ============================================================ */
Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');

/* ============================================================
 * PROTECTED AREA (requires auth)
 * ============================================================ */
Route::middleware(['auth'])->group(function () {

    /* ---------------- Home & Dashboard ---------------- */
    Route::get('/', fn () => redirect()->route('dashboard'))->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* ============================================================
     * TTD DIGITAL - HALAMAN VERIFIKASI HASIL SCAN (SIGNED URL)
     * ============================================================ */
    Route::get('/sign/qc/{step}/{code}', [SignatureController::class, 'show'])
        ->name('sign.qc.show')
        ->middleware('signed');

    /* ============================================================
     * USER MANAGEMENT
     * ============================================================ */

    /**
     * ADMIN SISTEM: full user management (Produksi, QC, QA, PPIC + impersonate)
     */
    Route::middleware(['admin'])->group(function () {

        // QC
        Route::get('/show-qc', [UserController::class, 'showQC'])->name('show-qc');
        Route::post('/show-qc', [UserController::class, 'storeQC'])->name('store-qc');
        Route::get('/show-qc/{id}/edit', [UserController::class, 'editQC'])->name('edit-qc');
        Route::put('/show-qc/{id}', [UserController::class, 'updateQC'])->name('update-qc');
        Route::delete('/show-qc/{id}', [UserController::class, 'destroy'])->name('delete-qc');

        // QA
        Route::get('/show-qa', [UserController::class, 'showQA'])->name('show-qa');
        Route::post('/show-qa', [UserController::class, 'storeQA'])->name('store-qa');
        Route::get('/show-qa/{id}/edit', [UserController::class, 'editQA'])->name('edit-qa');
        Route::put('/show-qa/{id}', [UserController::class, 'updateQA'])->name('update-qa');
        Route::delete('/show-qa/{id}', [UserController::class, 'destroy'])->name('delete-qa');

        // PPIC
        Route::get('/show-ppic', [UserController::class, 'showPPIC'])->name('show-ppic');
        Route::post('/show-ppic', [UserController::class, 'storePPIC'])->name('store-ppic');
        Route::get('/show-ppic/{id}/edit', [UserController::class, 'editPPIC'])->name('edit-ppic');
        Route::put('/show-ppic/{id}', [UserController::class, 'updatePPIC'])->name('update-ppic');
        Route::delete('/show-ppic/{id}', [UserController::class, 'destroy'])->name('delete-ppic');

        // Impersonate (contoh: login-as-ppic)
        Route::get('/login-as-ppic/{id}', [UserController::class, 'loginAsPPIC'])->name('login-as-ppic');
    });

    /**
     * PRODUKSI SPV/ADMIN juga boleh manage user Produksi (HANYA PRODUKSI)
     * Admin sistem tetap bisa karena role Admin
     *
     * Middleware 'produksi.level:ADMIN,SPV' ensures only allowed production levels.
     */
    Route::middleware(['role:Admin,Produksi', 'produksi.level:ADMIN,SPV'])->group(function () {
        Route::get('/show-produksi', [UserController::class, 'showProduksi'])->name('show-produksi');
        Route::post('/show-produksi', [UserController::class, 'storeProduksi'])->name('store-produksi');
        Route::get('/show-produksi/{id}/edit', [UserController::class, 'editProduksi'])->name('edit-produksi');
        Route::put('/show-produksi/{id}', [UserController::class, 'updateProduksi'])->name('update-produksi');
        Route::delete('/show-produksi/{id}', [UserController::class, 'destroy'])->name('delete-produksi');
    });

    /* ============================================================
     * MASTER PRODUK PRODUKSI
     * ============================================================ */
    Route::middleware(['role:Admin,Produksi', 'produksi.level:ADMIN,SPV'])->group(function () {
        Route::resource('produksi', ProduksiController::class)->names('produksi');
    });

    /* ============================================================
     * PRODUKSI CORE (NON-QC)
     * ============================================================ */
    Route::middleware(['role:Admin,Produksi'])->group(function () {

        // Upload WO / Jadwal Produksi
        Route::get('/permintaan-bahan-baku', [ProduksiBatchController::class, 'index'])->name('show-permintaan');
        Route::post('/permintaan-bahan-baku/upload', [ProduksiBatchController::class, 'upload'])->name('permintaan.upload');
        Route::get('/permintaan-bahan-baku/{batch}/edit', [ProduksiBatchController::class, 'edit'])
            ->whereNumber('batch')->name('edit-permintaan');
        Route::put('/permintaan-bahan-baku/{batch}', [ProduksiBatchController::class, 'update'])
            ->whereNumber('batch')->name('update-permintaan');
        Route::post('/permintaan-bahan-baku/bulk-delete', [ProduksiBatchController::class, 'bulkDelete'])
            ->name('permintaan.bulk-delete');

        // Weighing
        Route::get('/weighing-wo', [WeighingController::class, 'index'])->name('weighing.index');

        // TRACKING BATCH (read-only)
        Route::prefix('tracking-batch')->name('tracking-batch.')->group(function () {
            Route::get('/', [BatchTrackingController::class, 'index'])->name('index');
            Route::get('/{batch}', [BatchTrackingController::class, 'show'])->whereNumber('batch')->name('show');
        });

        // Mixing
        Route::prefix('mixing')->name('mixing.')->group(function () {
            Route::get('/', [MixingController::class, 'index'])->name('index');
            Route::get('/history', [MixingController::class, 'history'])->name('history');
            Route::post('/{batch}/start', [MixingController::class, 'start'])->whereNumber('batch')->name('start');
            Route::post('/{batch}/stop', [MixingController::class, 'stop'])->whereNumber('batch')->name('stop');
            Route::post('/{batch}/pause', [MixingController::class, 'pause'])->whereNumber('batch')->name('pause');
            Route::post('/{batch}/resume', [MixingController::class, 'resume'])->whereNumber('batch')->name('resume');
            Route::post('/{batch}/hold', [MixingController::class, 'hold'])->whereNumber('batch')->name('hold');
        });

        // Capsule Filling
        Route::prefix('capsule-filling')->name('capsule-filling.')->group(function () {
            Route::get('/', [CapsuleFillingController::class, 'index'])->name('index');
            Route::get('/history', [CapsuleFillingController::class, 'history'])->name('history');
            Route::post('/{batch}/start', [CapsuleFillingController::class, 'start'])->whereNumber('batch')->name('start');
            Route::post('/{batch}/stop', [CapsuleFillingController::class, 'stop'])->whereNumber('batch')->name('stop');
            Route::post('/{batch}/pause', [CapsuleFillingController::class, 'pause'])->whereNumber('batch')->name('pause');
            Route::post('/{batch}/resume', [CapsuleFillingController::class, 'resume'])->whereNumber('batch')->name('resume');
            Route::post('/{batch}/hold', [CapsuleFillingController::class, 'hold'])->whereNumber('batch')->name('hold');
        });

        // Tableting
        Route::prefix('tableting')->name('tableting.')->group(function () {
            Route::get('/', [TabletingController::class, 'index'])->name('index');
            Route::get('/history', [TabletingController::class, 'history'])->name('history');
            Route::post('/{batch}/start', [TabletingController::class, 'start'])->whereNumber('batch')->name('start');
            Route::post('/{batch}/stop', [TabletingController::class, 'stop'])->whereNumber('batch')->name('stop');
            Route::post('/{batch}/pause', [TabletingController::class, 'pause'])->whereNumber('batch')->name('pause');
            Route::post('/{batch}/resume', [TabletingController::class, 'resume'])->whereNumber('batch')->name('resume');
            Route::post('/{batch}/hold', [TabletingController::class, 'hold'])->whereNumber('batch')->name('hold');
        });

        // Primary Pack
        Route::prefix('primary-pack')->name('primary-pack.')->group(function () {
            Route::get('/', [PrimaryPackController::class, 'index'])->name('index');
            Route::get('/history', [PrimaryPackController::class, 'history'])->name('history');
            Route::post('/{batch}/start', [PrimaryPackController::class, 'start'])->whereNumber('batch')->name('start');
            Route::post('/{batch}/stop', [PrimaryPackController::class, 'stop'])->whereNumber('batch')->name('stop');
            Route::post('/{batch}/pause', [PrimaryPackController::class, 'pause'])->whereNumber('batch')->name('pause');
            Route::post('/{batch}/resume', [PrimaryPackController::class, 'resume'])->whereNumber('batch')->name('resume');
            Route::post('/{batch}/hold', [PrimaryPackController::class, 'hold'])->whereNumber('batch')->name('hold');
        });

        // Secondary Pack
        Route::prefix('secondary-pack')->name('secondary-pack.')->group(function () {
            Route::get('/', [SecondaryPackController::class, 'index'])->name('index');
            Route::get('/history', [SecondaryPackController::class, 'history'])->name('history');
            Route::post('/{batch}/start', [SecondaryPackController::class, 'start'])->whereNumber('batch')->name('start');
            Route::post('/{batch}/stop', [SecondaryPackController::class, 'stop'])->whereNumber('batch')->name('stop');
            Route::post('/{batch}/pause', [SecondaryPackController::class, 'pause'])->whereNumber('batch')->name('pause');
            Route::post('/{batch}/resume', [SecondaryPackController::class, 'resume'])->whereNumber('batch')->name('resume');
            Route::post('/{batch}/hold', [SecondaryPackController::class, 'hold'])->whereNumber('batch')->name('hold');

            Route::get('/{batch}/qty', [SecondaryPackController::class, 'qtyForm'])->whereNumber('batch')->name('qty.form');
            Route::post('/{batch}/qty', [SecondaryPackController::class, 'qtySave'])->whereNumber('batch')->name('qty.save');
        });

        // Qty Batch (setelah Secondary Pack)
        Route::prefix('qty-batch')->name('qty-batch.')->group(function () {
            Route::get('/', [QtyBatchController::class, 'index'])->name('index');
            Route::get('/history', [QtyBatchController::class, 'history'])->name('history');
            Route::post('/{batch}/confirm', [QtyBatchController::class, 'confirm'])->whereNumber('batch')->name('confirm');
            Route::post('/{batch}/reject', [QtyBatchController::class, 'reject'])->whereNumber('batch')->name('reject');
        });
    });

   /* ============================================================
    * COATING (Admin + Produksi + QA)
    * ============================================================ */
    Route::prefix('coating')
        ->name('coating.')
        ->middleware('role:Admin,Produksi,QA')
        ->group(function () {

            Route::get('/', [CoatingController::class, 'index'])->name('index');
            Route::get('/history', [CoatingController::class, 'history'])->name('history');

            Route::get('/{batch}', [CoatingController::class, 'show'])
                ->whereNumber('batch')->name('show');

            Route::post('/{batch}/start', [CoatingController::class, 'start'])
                ->whereNumber('batch')->name('start');

            Route::post('/{batch}/stop', [CoatingController::class, 'stop'])
                ->whereNumber('batch')->name('stop');

            Route::post('/{batch}/pause', [CoatingController::class, 'pause'])
                ->whereNumber('batch')->name('pause');

            Route::post('/{batch}/resume', [CoatingController::class, 'resume'])
                ->whereNumber('batch')->name('resume');

            Route::post('/{batch}/hold', [CoatingController::class, 'hold'])
                ->whereNumber('batch')->name('hold');

            // SPLIT (EAZ)
            Route::post('/{batch}/split-eaz', [CoatingController::class, 'splitEaz'])
                ->whereNumber('batch')->name('split-eaz');

            Route::delete('/{batch}/destroy-eaz', [CoatingController::class, 'destroyEaz'])
                ->whereNumber('batch')->name('destroy-eaz');

            // SPLIT BATCH (utama)
            Route::post('/{batch}/split', [CoatingController::class, 'split'])
                ->whereNumber('batch')->name('split');
    });


    /* ============================================================
     * QC MODULES (Admin, QC, QA)
     * ============================================================ */
    Route::middleware(['role:Admin,QC,QA'])->group(function () {

        Route::prefix('qc-granul')->name('qc-granul.')->group(function () {
            Route::get('/', [QcGranulController::class, 'index'])->name('index');
            Route::get('/history', [QcGranulController::class, 'history'])->name('history');

            // Hold
            Route::get('/{batch}/hold', [QcGranulController::class, 'holdForm'])
                ->whereNumber('batch')
                ->name('hold.form');

            Route::post('/{batch}/hold', [QcGranulController::class, 'hold'])
                ->whereNumber('batch')
                ->name('hold');

            // Release
            Route::post('/{batch}/release', [QcGranulController::class, 'release'])
                ->whereNumber('batch')->name('release');
        });

        Route::prefix('qc-tablet')->name('qc-tablet.')->group(function () {
            Route::get('/', [QcTabletController::class, 'index'])->name('index');
            Route::get('/history', [QcTabletController::class, 'history'])->name('history');

            Route::get('/{batch}/hold', [QcTabletController::class, 'holdForm'])
                ->whereNumber('batch')
                ->name('hold.form');

            Route::post('/{batch}/hold', [QcTabletController::class, 'hold'])
                ->whereNumber('batch')
                ->name('hold');

            Route::post('/{batch}/release', [QcTabletController::class, 'release'])
                ->whereNumber('batch')->name('release');
        });

        Route::prefix('qc-ruahan')->name('qc-ruahan.')->group(function () {
            Route::get('/', [QcRuahanController::class, 'index'])->name('index');
            Route::get('/history', [QcRuahanController::class, 'history'])->name('history');

            Route::put('/{batch}', [QcRuahanController::class, 'update'])->name('update');
            Route::post('/{batch}/release', [QcRuahanController::class, 'release'])->name('release');

            Route::get('/{batch}/hold', [QcRuahanController::class, 'holdForm'])->name('hold.form');
            Route::post('/{batch}/hold', [QcRuahanController::class, 'hold'])->name('hold');

            // Print/Excel
            Route::get('/{batch}/print', [QcRuahanController::class, 'print'])->name('print');
            Route::get('/{batch}/excel', [QcRuahanController::class, 'excel'])->name('excel');
            Route::get('/print-all', [QcRuahanController::class, 'printAll'])->name('printAll');
        });

        Route::prefix('qc-ruahan-akhir')->name('qc-ruahan-akhir.')->group(function () {
            Route::get('/', [QcRuahanAkhirController::class, 'index'])->name('index');
            Route::get('/history', [QcRuahanAkhirController::class, 'history'])->name('history');

            Route::post('/{batch}/release', [QcRuahanAkhirController::class, 'release'])
                ->whereNumber('batch')->name('release');

            Route::post('/{batch}/hold', [QcRuahanAkhirController::class, 'hold'])
                ->whereNumber('batch')->name('hold');
        });
    });

    /* ============================================================
     * AFTER SECONDARY PACK (QA dominan)
     * ============================================================ */
    Route::prefix('qc-jobsheet')->name('qc-jobsheet.')
        ->middleware('role:Admin,QA')
        ->group(function () {
            Route::get('/', [QcJobSheetController::class, 'index'])->name('index');
            Route::get('/history', [QcJobSheetController::class, 'history'])->name('history');
            Route::get('/{batch}/edit', [QcJobSheetController::class, 'edit'])->whereNumber('batch')->name('edit');
            Route::post('/{batch}', [QcJobSheetController::class, 'update'])->whereNumber('batch')->name('update');
            Route::post('/{batch}/confirm', [QcJobSheetController::class, 'confirm'])->whereNumber('batch')->name('confirm');
        });

    Route::prefix('coa')->name('coa.')
        ->middleware('role:Admin,QA')
        ->group(function () {
            Route::get('/', [CoaController::class, 'index'])->name('index');
            Route::get('/riwayat', [CoaController::class, 'history'])->name('history');
            Route::get('/{batch}/edit', [CoaController::class, 'edit'])->whereNumber('batch')->name('edit');
            Route::post('/{batch}', [CoaController::class, 'update'])->whereNumber('batch')->name('update');
            Route::post('/{batch}/confirm', [CoaController::class, 'confirm'])->whereNumber('batch')->name('confirm');
        });

    Route::prefix('sampling')->name('sampling.')
        ->middleware('role:Admin,QA,QC')
        ->group(function () {
            Route::get('/', [SamplingController::class, 'index'])->name('index');
            Route::get('/history', [SamplingController::class, 'history'])->name('history');
            Route::post('/{batch}/acc', [SamplingController::class, 'acc'])->whereNumber('batch')->name('acc');
            Route::post('/{batch}/confirm', [SamplingController::class, 'confirm'])->whereNumber('batch')->name('confirm');
            Route::post('/{batch}/reject', [SamplingController::class, 'reject'])->whereNumber('batch')->name('reject');
        });

    Route::prefix('review')->name('review.')
        ->middleware('role:Admin,QA')
        ->group(function () {
            Route::get('/', [ReviewController::class, 'index'])->name('index');
            Route::get('/history', [ReviewController::class, 'history'])->name('history');
            Route::post('/{batch}/hold', [ReviewController::class, 'hold'])->whereNumber('batch')->name('hold');
            Route::post('/{batch}/release', [ReviewController::class, 'release'])->whereNumber('batch')->name('release');
            Route::post('/{batch}/reject', [ReviewController::class, 'reject'])->whereNumber('batch')->name('reject');
        });

    Route::prefix('release-after-secondary')->name('release.')
        ->middleware('role:Admin,QA')
        ->group(function () {
            Route::get('/', [ReleaseController::class, 'index'])->name('index');
            Route::get('/print', [ReleaseController::class, 'print'])->name('print');
            Route::get('/logsheet', [ReleaseController::class, 'logsheet'])->name('logsheet');
            Route::get('/logsheet-export', [ReleaseController::class, 'exportCsv'])->name('logsheet.export');
        });

    Route::prefix('qc-release')->name('qc-release.')
        ->middleware('role:Admin,QA')
        ->group(function () {
            Route::get('/', [QcReleaseController::class, 'index'])->name('index');
            Route::get('/history', [QcReleaseController::class, 'history'])->name('history');
            Route::put('/{batch}', [QcReleaseController::class, 'update'])->whereNumber('batch')->name('update');
        });

    /* ============================================================
     * Gudang Release (bukan QC menu)
     * ============================================================ */
    Route::prefix('gudang-release')->name('gudang-release.')->group(function () {
        Route::get('/', [GudangReleaseController::class, 'index'])->name('index');
        Route::get('/history', [GudangReleaseController::class, 'history'])->name('history');

        Route::post('/{release}/approve', [GudangReleaseController::class, 'approve'])->name('approve');
        Route::post('/{release}/reject',  [GudangReleaseController::class, 'reject'])->name('reject');

        // LPHP
        Route::get('/lphp', [GudangReleaseController::class, 'lphp'])->name('lphp');
        Route::post('/lphp/print', [GudangReleaseController::class, 'lphpPrint'])->name('lphp.print');

        // SPV & GOJ related actions (dalam context gudang-release)
        Route::post('/spv-approve/{spvDoc}', [GudangReleaseController::class, 'spvApprove'])->name('spvApprove');
        Route::post('/{release}/goj-approve', [GudangReleaseController::class, 'gojApprove'])->name('gojApprove');
    });

    /* ============================================================
     * SPV REVIEW (prefix: /spv, route names: spv.*)
     * ============================================================ */
    Route::prefix('spv')->name('spv.')->group(function () {
        Route::get('/', [SpvReviewController::class, 'index'])->name('index');
        Route::get('/history', [SpvReviewController::class, 'history'])->name('history');
        Route::get('/{spvDoc}', [SpvReviewController::class, 'detail'])->name('detail');
        Route::post('/{spvDoc}/approve', [SpvReviewController::class, 'approve'])->name('approve');
        Route::post('/{spvDoc}/reject', [SpvReviewController::class, 'reject'])->name('reject');
    });

    /* ============================================================
     * GOJ (Gudang Office/General) routes
     * ============================================================ */
    Route::prefix('goj')->name('goj.')->group(function () {

        Route::get('/', [GojController::class, 'index'])->name('index');
        Route::get('/history', [GojController::class, 'history'])->name('history');

        // preview (safeguard) then show
        Route::get('/{goj}/preview', [GojController::class, 'preview'])->name('preview');
        Route::get('/{goj}', [GojController::class, 'show'])->name('show');

        Route::post('/{goj}/approve', [GojController::class, 'approve'])->name('approve');
        Route::post('/{goj}/reject',  [GojController::class, 'reject'])->name('reject');
    });

    /* ============================================================
     * HASIL UJI COA (Admin + QC)
     * ============================================================ */
    Route::prefix('uji-coa')->name('uji-coa.')
        ->middleware('role:Admin,QC')
        ->group(function () {
            Route::get('/', [UjiCoaController::class, 'index'])->name('index');
            Route::get('/{id}/edit', [UjiCoaController::class, 'edit'])->name('edit');
            Route::put('/{id}', [UjiCoaController::class, 'update'])->name('update');
            Route::get('/{id}/confirm', [UjiCoaController::class, 'confirmForm'])->name('confirm.form');
            Route::put('/{id}/confirm', [UjiCoaController::class, 'confirmUpdate'])->name('confirm.update');
        });

    /* ============================================================
     * HOLDING (Admin + QA + QC + Produksi)
     * ============================================================ */
    Route::prefix('holding')->name('holding.')
        ->middleware('role:Admin,QA,QC,Produksi')
        ->group(function () {
            Route::get('/', [HoldingController::class, 'index'])->name('index');
            Route::post('/{batch}/hold', [HoldingController::class, 'hold'])->whereNumber('batch')->name('hold');
            Route::post('/{batch}/release', [HoldingController::class, 'release'])->whereNumber('batch')->name('release');
            Route::post('/{batch}/reject', [HoldingController::class, 'reject'])->whereNumber('batch')->name('reject');
            Route::get('/history', [HoldingController::class, 'history'])->name('history');
        });

    /* ============================================================
     * PROFILE & LOGOUT
     * ============================================================ */
    Route::post('/logout', [UserController::class, 'logout'])->name('logout');

    Route::get('/show-profile', [UserController::class, 'profile'])->name('show-profile');
    Route::put('/show-profile/general', [UserController::class, 'updateGeneral'])->name('edit-general');
    Route::put('/show-profile', [UserController::class, 'updatePassword'])->name('edit-password');

});


//   UNIVERSAL DATA EXPORTER (CSV) - for integration with other systems Looker

const API_SECRET = 'samco2022';
const BLOCKED_COLUMNS = ['password', 'remember_token', 'token', 'secret'];

Route::get('/api/universal-data', function (Request $request) {
    if ($request->query('key') !== API_SECRET) {
        return response()->json(['error' => 'AKSES DITOLAK: Kunci Salah!'], 403);
    }

    $tableName = $request->query('table');

    if (!$tableName || !Schema::hasTable($tableName)) {
        return response()->json(['error' => "Tabel '$tableName' tidak ditemukan di database!"], 404);
    }

    $data = DB::table($tableName)->get();

    if ($data->isEmpty()) {
         return response("Data Kosong", 200);
    }

    $output = "";

    $firstRow = (array) $data->first();
    $headers = array_diff(array_keys($firstRow), BLOCKED_COLUMNS);

    $output .= implode(",", $headers) . "\n";

    foreach ($data as $row) {
        $rowArray = (array) $row;
        $cleanRow = [];

        foreach ($headers as $header) {
            $cleanValue = str_replace(',', ' ', $rowArray[$header]);
            $cleanRow[] = $cleanValue;
        }

        $output .= implode(",", $cleanRow) . "\n";
    }

    return response($output, 200, [
        'Content-Type' => 'text/plain',
    ]);
});