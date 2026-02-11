<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpvDoc extends Model
{
    use HasFactory;

    protected $fillable = [
        'doc_no','doc_date','status','created_by','approved_by','approved_at','rejected_by','rejected_at'
    ];

    public function items()
    {
        return $this->hasMany(SpvDocItem::class);
    }
}
