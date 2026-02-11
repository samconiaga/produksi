<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoldingLog extends Model
{
    protected $table = 'holding_logs';

    protected $fillable = [
        'produksi_batch_id',
        'hold_no',
        'holding_stage',
        'holding_reason',
        'holding_note',

        'held_at',
        'held_by',

        'outcome',        // RELEASE | REJECT | null(OPEN)
        'return_to',
        'resolve_reason',
        'resolve_note',

        'resolved_at',
        'resolved_by',
        'duration_seconds',
    ];

    protected $casts = [
        'held_at'     => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(ProduksiBatch::class, 'produksi_batch_id');
    }
}
