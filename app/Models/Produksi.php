<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produksi extends Model
{
    use HasFactory;

    protected $table = 'produksi';

    protected $fillable = [
        'kode_produk',
        'nama_produk',

        'kategori_produk',
        'est_qty',

        'bentuk_sediaan',
        'tipe_alur',
        'leadtime_target',

        'expired_years',      // <--- masa kadaluarsa dalam tahun

        'is_aktif',
    ];

    protected $casts = [
        'is_aktif'        => 'boolean',
        'est_qty'         => 'integer',
        'leadtime_target' => 'integer',
        'expired_years'   => 'integer',
    ];

    // Relasi ke batch produksi
    public function batches()
    {
        return $this->hasMany(ProduksiBatch::class, 'produksi_id');
    }

    // Scope: hanya produk aktif
    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    /**
     * Helper (opsional):
     * hitung label expired (m-Y) berdasarkan tgl mulai weighing.
     * Dipakai nanti di modul Release / After Secondary.
     */
    public function expiredLabelFor($tglMulaiWeighing): ?string
    {
        if (!$tglMulaiWeighing || !$this->expired_years) {
            return null;
        }

        $date = $tglMulaiWeighing->copy()->addYears($this->expired_years);

        return $date->format('m-Y'); // hanya bulan-tahun
    }
}
