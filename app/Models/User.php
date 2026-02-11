<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'produksi_role',
        'password',
        'email_verified_at',

        // QC
        'qc_level',
        'qc_signature_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /* ================= PRODUKSI HELPER ================= */
    public function isProduksiAdmin()
    {
        return $this->role === 'Produksi' && $this->produksi_role === 'ADMIN';
    }

    public function isProduksiSPV()
    {
        return $this->role === 'Produksi' && $this->produksi_role === 'SPV';
    }

    public function isProduksiOperator()
    {
        return $this->role === 'Produksi' && $this->produksi_role === 'OPERATOR';
    }
}
