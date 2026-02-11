<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GojDocItem extends Model
{
    protected $table = 'goj_doc_items';
    protected $guarded = [];

    protected $casts = [
        'tgl_release' => 'date',
        'tgl_expired' => 'date',
    ];

    public function doc()
    {
        return $this->belongsTo(GojDoc::class, 'goj_doc_id');
    }

    public function batch()
    {
        return $this->belongsTo(ProduksiBatch::class, 'produksi_batch_id');
    }
}