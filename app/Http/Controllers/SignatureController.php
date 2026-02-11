<?php

namespace App\Http\Controllers;

use App\Models\ProduksiBatch;
use App\Models\User;

class SignatureController extends Controller
{
    public function show(string $step, string $code)
    {
        $stepKey = strtolower($step);

        $map = [
            'granul' => [
                'code'    => 'granul_sign_code',
                'by'      => 'granul_signed_by',
                'level'   => 'granul_signed_level',
                'tgl'     => 'tgl_rilis_granul',
                'at'      => 'granul_signed_at',
                'user_id' => 'granul_signed_user_id',
            ],
            'tablet' => [
                'code'    => 'tablet_sign_code',
                'by'      => 'tablet_signed_by',
                'level'   => 'tablet_signed_level',
                'tgl'     => 'tgl_rilis_tablet',
                'at'      => 'tablet_signed_at',
                'user_id' => 'tablet_signed_user_id',
            ],
            'ruahan' => [
                'code'    => 'ruahan_sign_code',
                'by'      => 'ruahan_signed_by',
                'level'   => 'ruahan_signed_level',
                'tgl'     => 'tgl_rilis_ruahan',
                'at'      => 'ruahan_signed_at',
                'user_id' => 'ruahan_signed_user_id',
            ],
            'ruahan_akhir' => [
                'code'    => 'ruahan_akhir_sign_code',
                'by'      => 'ruahan_akhir_signed_by',
                'level'   => 'ruahan_akhir_signed_level',
                'tgl'     => 'tgl_rilis_ruahan_akhir',
                'at'      => 'ruahan_akhir_signed_at',
                'user_id' => 'ruahan_akhir_signed_user_id',
            ],
        ];

        if (!isset($map[$stepKey])) abort(404, 'Step tidak dikenal.');
        $cfg = $map[$stepKey];

        $batch = ProduksiBatch::with('produksi')
            ->where($cfg['code'], $code)
            ->firstOrFail();

        $signedBy    = $batch->{$cfg['by']} ?? '-';
        $signedLevel = $batch->{$cfg['level']} ?? '-';
        $signedAt    = $batch->{$cfg['at']} ?? null;
        $tglRilis    = $batch->{$cfg['tgl']} ?? null;

        // ambil file QR/TTD dari user yang release
        $sigPath = null;
        $uid = $batch->{$cfg['user_id']} ?? null;
        if ($uid) {
            $user = User::find($uid);
            $sigPath = $user?->qc_signature_path;
        }

        return view('sign.qc_show', [
            'step'        => $stepKey,
            'code'        => $code,
            'batch'       => $batch,
            'signedBy'    => $signedBy,
            'signedLevel' => $signedLevel,
            'signedAt'    => $signedAt,
            'tglRilis'    => $tglRilis,
            'sigPath'     => $sigPath,
        ]);
    }
}

