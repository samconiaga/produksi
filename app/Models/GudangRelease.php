<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GudangRelease extends Model
{
    protected $table = 'gudang_release';

    protected $fillable = [
        'produksi_batch_id',

        'kemasan',
        'isi',
        'jumlah_release',
        'qty_fisik',

        // ✅ WAJIB
        'gudang_id',

        'tanggal_expired',
        'berat_fisik',
        'no_wadah',

        'status',
        'catatan',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'tanggal_expired' => 'date',
        'approved_at'     => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(ProduksiBatch::class, 'produksi_batch_id');
    }

    public function gudang()
    {
        return $this->belongsTo(MasterGudang::class, 'gudang_id');
    }
}
