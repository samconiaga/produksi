<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterGudang extends Model
{
    protected $table = 'master_gudangs';

    protected $fillable = [
        'kode',
        'nama',
        'keterangan',
        'is_active',
    ];
}
