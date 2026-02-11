<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GojDoc extends Model
{
    protected $table = 'goj_docs';
    protected $guarded = [];

    protected $casts = [
        'doc_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(GojDocItem::class, 'goj_doc_id');
    }
}