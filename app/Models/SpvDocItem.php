<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpvDocItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'spv_doc_id','produksi_batch_id','nama_produk','batch_no','kode_batch',
        'tgl_release','tgl_expired','kemasan','isi','jumlah','status_gudang'
    ];

    public function spvDoc()
    {
        return $this->belongsTo(SpvDoc::class);
    }
}
