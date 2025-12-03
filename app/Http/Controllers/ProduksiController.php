<?php

namespace App\Http\Controllers;

use App\Models\Produksi;
use Illuminate\Http\Request;

class ProduksiController extends Controller
{
    /* ================== INDEX ================== */
    public function index(Request $request)
    {
        // ambil parameter filter
        $q         = trim($request->get('q', ''));
        $kategori  = $request->get('kategori', '');
        $perPage   = (int) $request->get('per_page', 15);
        if ($perPage <= 0) {
            $perPage = 15;
        }

        // query data
        $rows = Produksi::query()
            ->when($q !== '', function ($qBuilder) use ($q) {
                $like = "%{$q}%";
                $qBuilder->where(function ($sub) use ($like) {
                    $sub->where('kode_produk', 'like', $like)
                        ->orWhere('nama_produk', 'like', $like)
                        ->orWhere('bentuk_sediaan', 'like', $like)
                        ->orWhere('tipe_alur', 'like', $like);
                });
            })
            ->when($kategori !== '', function ($qBuilder) use ($kategori) {
                $qBuilder->where('kategori_produk', $kategori);
            })
            ->orderBy('kode_produk')
            ->paginate($perPage);

        $kategoriOptions = $this->kategoriOptions();

        return view('produksi.index', compact(
            'rows',
            'q',
            'kategori',
            'kategoriOptions',
            'perPage'
        ));
    }

    /* ================== CREATE ================== */
    public function create()
    {
        $isEdit  = false;
        $produk  = new Produksi();

        $bentukOptions   = $this->bentukOptions();
        $tipeAlurOptions = $this->tipeAlurOptions();
        $kategoriOptions = $this->kategoriOptions();

        return view('produksi.form', compact(
            'isEdit',
            'produk',
            'bentukOptions',
            'tipeAlurOptions',
            'kategoriOptions'
        ));
    }

    /* ================== STORE ================== */
    public function store(Request $request)
    {
        $data = $request->validate([
            'kode_produk'      => 'required|string|max:50|unique:produksi,kode_produk',
            'nama_produk'      => 'required|string|max:150',

            'kategori_produk'  => 'required|string|max:50',   // ETHICAL / OTC / TRADISIONAL
            'est_qty'          => 'nullable|integer|min:0',   // est Qty dari master

            'bentuk_sediaan'   => 'required|string|max:50',
            'tipe_alur'        => 'required|string|max:50',
            'leadtime_target'  => 'nullable|integer|min:0',

            // masa kadaluarsa dalam tahun (boleh kosong)
            'expired_years'    => 'nullable|integer|min:0|max:20',

            'is_aktif'         => 'nullable|boolean',
        ]);

        $data['is_aktif'] = $request->has('is_aktif');

        Produksi::create($data);

        return redirect()
            ->route('produksi.index')
            ->with('ok', 'Produk produksi berhasil ditambahkan.');
    }

    /* ================== EDIT ================== */
    public function edit(Produksi $produksi)
    {
        $isEdit  = true;
        $produk  = $produksi;

        $bentukOptions   = $this->bentukOptions();
        $tipeAlurOptions = $this->tipeAlurOptions();
        $kategoriOptions = $this->kategoriOptions();

        return view('produksi.form', compact(
            'isEdit',
            'produk',
            'bentukOptions',
            'tipeAlurOptions',
            'kategoriOptions'
        ));
    }

    /* ================== UPDATE ================== */
    public function update(Request $request, Produksi $produksi)
    {
        $data = $request->validate([
            'kode_produk'      => 'required|string|max:50|unique:produksi,kode_produk,' . $produksi->id,
            'nama_produk'      => 'required|string|max:150',

            'kategori_produk'  => 'required|string|max:50',
            'est_qty'          => 'nullable|integer|min:0',

            'bentuk_sediaan'   => 'required|string|max:50',
            'tipe_alur'        => 'required|string|max:50',
            'leadtime_target'  => 'nullable|integer|min:0',

            'expired_years'    => 'nullable|integer|min:0|max:20',

            'is_aktif'         => 'nullable|boolean',
        ]);

        $data['is_aktif'] = $request->has('is_aktif');

        $produksi->update($data);

        return redirect()
            ->route('produksi.index')
            ->with('ok', 'Produk produksi berhasil diupdate.');
    }

    /* ================== DESTROY ================== */
    public function destroy(Produksi $produksi)
    {
        $produksi->delete();

        return redirect()
            ->route('produksi.index')
            ->with('ok', 'Produk produksi berhasil dihapus.');
    }

    /* ========== Helper pilihan dropdown ========== */

    private function bentukOptions(): array
    {
        return [
            'Tablet Non Salut',
            'Tablet Salut Gula',
            'Tablet Film Coating',
            'Kapsul',
            'Dry Syrup',
            'CLO',
            'Obat Luar',
        ];
    }

    private function tipeAlurOptions(): array
    {
        return [
            'TABLET_NON_SALUT' => 'Tablet / Kaplet Non Salut',
            'TABLET_SALUT'     => 'Tablet / Kaplet Salut / Coating',
            'DRY_SYRUP'        => 'Dry Syrup',
            'CLO'              => 'CLO / Softcaps',
            'CAIRAN_LUAR'      => 'Cairan / Obat Luar',
            'KAPSUL'           => 'Kapsul',
        ];
    }

    private function kategoriOptions(): array
    {
        // ETHICAL / OTC / TRADISIONAL
        return [
            'ETHICAL'     => 'ETHICAL',
            'OTC'         => 'OTC',
            'TRADISIONAL' => 'TRADISIONAL',
        ];
    }
}
