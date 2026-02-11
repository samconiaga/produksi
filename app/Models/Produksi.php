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

        // target rekon per modul
        'target_rekon_weighing',
        'target_rekon_mixing',
        'target_rekon_tableting',
        'target_rekon_capsule_filling',
        'target_rekon_coating',
        'target_rekon_primary_pack',
        'target_rekon_secondary_pack',

        // (opsional) kalau kolom lama masih ada dan masih dipakai:
        'target_rekon',

        'wadah',
        'bentuk_sediaan',
        'tipe_alur',
        'leadtime_target',
        'expired_years',

        'is_aktif',

        // new split fields
        'is_split',
        'split_suffix',
    ];

    protected $casts = [
        'is_aktif'        => 'boolean',
        'is_split'         => 'boolean',
        'est_qty'         => 'integer',

        // per modul
        'target_rekon_weighing'        => 'integer',
        'target_rekon_mixing'          => 'integer',
        'target_rekon_tableting'       => 'integer',
        'target_rekon_capsule_filling' => 'integer',
        'target_rekon_coating'         => 'integer',
        'target_rekon_primary_pack'    => 'integer',
        'target_rekon_secondary_pack'  => 'integer',

        // kolom lama (kalau ada)
        'target_rekon'    => 'integer',

        'leadtime_target' => 'integer',
        'expired_years'   => 'integer',
    ];

    public function batches()
    {
        return $this->hasMany(ProduksiBatch::class, 'produksi_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    public function expiredLabelFor($tglMulaiWeighing): ?string
    {
        if (!$tglMulaiWeighing || !$this->expired_years) return null;
        $date = $tglMulaiWeighing->copy()->addYears($this->expired_years);
        return $date->format('m-Y');
    }

    /**
     * Helper: label split (suffix) atau null jika tidak bisa split
     */
    public function getSplitLabelAttribute(): ?string
    {
        if (!($this->is_split ?? false)) return null;
        // jika split_suffix kosong, default 'Z'
        $s = $this->split_suffix ?? '';
        return $s !== '' ? (string) $s : 'Z';
    }
}
