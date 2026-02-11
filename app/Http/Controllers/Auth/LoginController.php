<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Tampilkan halaman login (guest).
     */
    public function login()
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            return redirect()->route($this->redirectRouteNameByRole($user));
        }

        return view('auth.login');
    }

    /**
     * Proses login (POST).
     */
    public function store(Request $request)
    {
        // validasi input
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
            'remember' => ['nullable'],
        ], [], [
            'email'    => 'Email',
            'password' => 'Password',
        ]);

        // ambil boolean dari checkbox (aman untuk "on", "1", true, etc.)
        $remember = $request->boolean('remember');

        // coba autentikasi
        if (Auth::attempt([
            'email'    => $credentials['email'],
            'password' => $credentials['password'],
        ], $remember)) {

            // regenerasi session setelah login (penting untuk mencegah fixation)
            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();

            // redirect ke intended atau route berdasarkan role
            return redirect()->intended(route($this->redirectRouteNameByRole($user)));
        }

        // kalau gagal, kembalikan error validasi
        throw ValidationException::withMessages([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ]);
    }

    /**
     * Logout
     */
    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Map role -> route name (pastikan case sesuai isi kolom role)
     */
    protected function redirectRouteNameByRole(User $user): string
    {
        return match ($user->role) {
            'Admin'      => 'dashboard',
            'Produksi'   => 'dashboard',
            'QC'         => 'dashboard',
            'QA'         => 'dashboard',
            'PPIC'       => 'dashboard',
            'Gudang'     => 'dashboard',
            default      => 'dashboard',
        };
    }
}