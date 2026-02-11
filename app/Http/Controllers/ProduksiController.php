<?php

namespace App\Http\Controllers;

use App\Models\Produksi;
use Illuminate\Http\Request;

class ProduksiController extends Controller
{
    /* ================== INDEX ================== */
    public function index(Request $request)
    {
        $q        = trim((string) $request->get('q', ''));
        $kategori = (string) $request->get('kategori', 'all');
        $perPage  = (int) $request->get('per_page', 15);
        if ($perPage <= 0) $perPage = 15;

        if ($kategori === 'all') $kategori = '';

        $rows = Produksi::query()
            ->when($q !== '', function ($qb) use ($q) {
                $like = "%{$q}%";
                $qb->where(function ($sub) use ($like) {
                    $sub->where('kode_produk', 'like', $like)
                        ->orWhere('nama_produk', 'like', $like)
                        ->orWhere('kategori_produk', 'like', $like)
                        ->orWhere('bentuk_sediaan', 'like', $like)
                        ->orWhere('wadah', 'like', $like)
                        ->orWhere('tipe_alur', 'like', $like);
                });
            })
            ->when($kategori !== '', function ($qb) use ($kategori) {
                $qb->where('kategori_produk', $kategori);
            })
            ->orderBy('kode_produk')
            ->paginate($perPage);

        $kategoriOptions = $this->kategoriOptions();

        return view('produk.index', compact(
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
        $wadahOptions    = $this->wadahOptions();

        return view('produk.form', compact(
            'isEdit',
            'produk',
            'bentukOptions',
            'tipeAlurOptions',
            'kategoriOptions',
            'wadahOptions'
        ));
    }

    /* ================== STORE ================== */
    public function store(Request $request)
    {
        $data = $this->validatedData($request, null);

        // boolean switches
        $data['is_aktif'] = $request->boolean('is_aktif');
        $data['is_split'] = $request->boolean('is_split');

        // suffix: jika kosong, simpan null (atau default 'Z' jika mau)
        $suffix = (string) $request->input('split_suffix', '');
        $data['split_suffix'] = $suffix !== '' ? strtoupper($suffix) : null;

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
        $wadahOptions    = $this->wadahOptions();

        return view('produk.form', compact(
            'isEdit',
            'produk',
            'bentukOptions',
            'tipeAlurOptions',
            'kategoriOptions',
            'wadahOptions'
        ));
    }

    /* ================== UPDATE ================== */
    public function update(Request $request, Produksi $produksi)
    {
        $data = $this->validatedData($request, $produksi);

        $data['is_aktif'] = $request->boolean('is_aktif');
        $data['is_split'] = $request->boolean('is_split');

        $suffix = (string) $request->input('split_suffix', '');
        $data['split_suffix'] = $suffix !== '' ? strtoupper($suffix) : null;

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

    /* =========================================================
     * VALIDATION (dipakai store & update)
     * =======================================================*/
    private function validatedData(Request $request, ?Produksi $produksi): array
    {
        $uniqueKode = 'unique:produksi,kode_produk';
        if ($produksi) $uniqueKode .= ',' . $produksi->id;

        return $request->validate([
            'kode_produk'     => ['required','string','max:50', $uniqueKode],
            'nama_produk'     => ['required','string','max:150'],

            'kategori_produk' => ['required','string','max:50'],
            'est_qty'         => ['nullable','integer','min:0'],

            // ✅ target rekon per modul
            'target_rekon_weighing'        => ['nullable','integer','min:0'],
            'target_rekon_mixing'          => ['nullable','integer','min:0'],
            'target_rekon_tableting'       => ['nullable','integer','min:0'],
            'target_rekon_capsule_filling' => ['nullable','integer','min:0'],
            'target_rekon_coating'         => ['nullable','integer','min:0'],
            'target_rekon_primary_pack'    => ['nullable','integer','min:0'],
            'target_rekon_secondary_pack'  => ['nullable','integer','min:0'],

            // (opsional) kalau kolom lama masih ada
            'target_rekon' => ['nullable','integer','min:0'],

            'wadah'           => ['nullable','string','max:10'],
            'bentuk_sediaan'  => ['required','string','max:50'],
            'tipe_alur'       => ['required','string','max:50'],
            'leadtime_target' => ['nullable','integer','min:0'],
            'expired_years'   => ['nullable','integer','min:0','max:20'],

            // new split fields
            'is_split'        => ['nullable'],
            'split_suffix'    => ['nullable','string','max:5'],

            'is_aktif'        => ['nullable'], // boolean handled by $request->boolean()
        ]);
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
        return [
            'ETHICAL'     => 'ETHICAL',
            'OTC'         => 'OTC',
            'TRADISIONAL' => 'TRADISIONAL',
        ];
    }

    private function wadahOptions(): array
    {
        return [
            'Dus' => 'Dus',
            'Btl' => 'Botol (Btl)',
            'Top' => 'Toples (Top)',
        ];
    }
}
