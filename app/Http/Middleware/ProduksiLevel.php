<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProduksiLevel
{
    /**
     * Pemakaian:
     * ->middleware('produksi.level:ADMIN,SPV')
     *
     * Kolom yang dicek (urut prioritas):
     * - produksi_role        (PUNYA KAMU: SPV / OPERATOR)
     * - level_produksi
     * - production_level
     * - level
     */
    public function handle(Request $request, Closure $next, ...$levels)
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        $role = strtolower(trim((string) ($user->role ?? '')));

        // ✅ kalau bukan PRODUKSI, biarkan lewat (Admin/QC/QA/PPIC/Gudang tetap bisa)
        if ($role !== 'produksi') {
            return $next($request);
        }

        // ✅ ambil level produksi dari kolom yang benar (produksi_role)
        $raw = (string) (
            $user->produksi_role
            ?? $user->level_produksi
            ?? $user->production_level
            ?? $user->level
            ?? ''
        );

        $current = strtoupper(trim($raw));

        // kalau kosong, fallback aman
        if ($current === '') {
            $current = 'OPERATOR';
        }

        // normalisasi allowed
        $allowed = array_values(array_filter(array_map(function ($l) {
            return strtoupper(trim((string) $l));
        }, $levels)));

        // kalau route tidak ngasih parameter level, anggap lolos (opsional safety)
        if (count($allowed) === 0) {
            return $next($request);
        }

        if (!in_array($current, $allowed, true)) {
            abort(403, 'AKSES KHUSUS PRODUKSI ADMIN/SPV.');
        }

        return $next($request);
    }
}
