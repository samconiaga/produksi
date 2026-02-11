<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /* =========================================================
     * PPIC
     * =======================================================*/
    public function showPPIC()
    {
        $users = User::query()
            ->where('role', 'PPIC')
            ->orderBy('name')
            ->get();

        return view('users_management.show_ppic', compact('users'));
    }

    public function storePPIC(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'min:3', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:7'],
        ]);

        User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'role'              => 'PPIC',
            'email_verified_at' => now(),
            'password'          => Hash::make($data['password']),
        ]);

        return redirect()->route('show-ppic')->with('success', 'Akun PPIC berhasil dibuat.');
    }

    public function editPPIC($id)
    {
        $ppic = User::query()
            ->where('id', $id)
            ->where('role', 'PPIC')
            ->firstOrFail();

        return view('users_management.edit_ppic', compact('ppic'));
    }

    public function updatePPIC(Request $request, $id)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'min:3', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email,' . $id],
            'password' => ['nullable', 'string', 'min:7'],
        ]);

        $update = [
            'name'  => $data['name'],
            'email' => $data['email'],
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        User::query()
            ->where('id', $id)
            ->where('role', 'PPIC')
            ->update($update);

        return redirect()->route('show-ppic')->with('success', 'Akun PPIC diperbarui.');
    }

    /* =========================================================
     * PRODUKSI (ADMIN | OPERATOR | SPV)
     * =======================================================*/
    public function showProduksi()
    {
        $users = User::query()
            ->where('role', 'Produksi')
            ->orderBy('produksi_role')
            ->orderBy('name')
            ->get();

        return view('users_management.show_produksi', compact('users'));
    }

    public function storeProduksi(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'min:3', 'max:255'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'string', 'min:7'],
            'produksi_role' => ['required', 'in:ADMIN,OPERATOR,SPV'],
        ]);

        User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'role'              => 'Produksi',
            'produksi_role'     => $data['produksi_role'], // ADMIN/SPV/OPERATOR
            'email_verified_at' => now(),
            'password'          => Hash::make($data['password']),
        ]);

        return redirect()->route('show-produksi')->with('success', 'Akun Produksi berhasil dibuat.');
    }

    public function editProduksi($id)
    {
        $produksi = User::query()
            ->where('id', $id)
            ->where('role', 'Produksi')
            ->firstOrFail();

        return view('users_management.edit_produksi', compact('produksi'));
    }

    public function updateProduksi(Request $request, $id)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'min:3', 'max:255'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email,' . $id],
            'password'      => ['nullable', 'string', 'min:7'],
            'produksi_role' => ['required', 'in:ADMIN,OPERATOR,SPV'],
        ]);

        $update = [
            'name'          => $data['name'],
            'email'         => $data['email'],
            'produksi_role' => $data['produksi_role'],
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        User::query()
            ->where('id', $id)
            ->where('role', 'Produksi')
            ->update($update);

        return redirect()->route('show-produksi')->with('success', 'Akun Produksi diperbarui.');
    }

    /* =========================================================
     * QC (dengan level + barcode tanda tangan)
     * =======================================================*/
    public function showQC()
    {
        $users = User::query()
            ->where('role', 'QC')
            ->orderBy('name')
            ->get();

        return view('users_management.show_qc', compact('users'));
    }

    public function storeQC(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'min:3', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:7'],
            'qc_level'     => ['required', 'in:QC,MANAGER,SPV'],
            'qr_signature' => ['nullable', 'image', 'max:2048'],
        ]);

        $path = null;
        if ($request->hasFile('qr_signature')) {
            $path = $request->file('qr_signature')->store('qc-signatures', 'public');
        }

        User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'role'              => 'QC',
            'qc_level'          => $data['qc_level'],
            'qc_signature_path' => $path,
            'email_verified_at' => now(),
            'password'          => Hash::make($data['password']),
        ]);

        return redirect()->route('show-qc')->with('success', 'Akun QC berhasil dibuat.');
    }

    public function editQC($id)
    {
        $qc = User::query()
            ->where('id', $id)
            ->where('role', 'QC')
            ->firstOrFail();

        return view('users_management.edit_qc', compact('qc'));
    }

    public function updateQC(Request $request, $id)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'min:3', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email,' . $id],
            'password'     => ['nullable', 'string', 'min:7'],
            'qc_level'     => ['required', 'in:QC,MANAGER,SPV'],
            'qr_signature' => ['nullable', 'image', 'max:2048'],
        ]);

        $qc = User::query()
            ->where('id', $id)
            ->where('role', 'QC')
            ->firstOrFail();

        $update = [
            'name'     => $data['name'],
            'email'    => $data['email'],
            'qc_level' => $data['qc_level'],
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        if ($request->hasFile('qr_signature')) {
            if ($qc->qc_signature_path && Storage::disk('public')->exists($qc->qc_signature_path)) {
                Storage::disk('public')->delete($qc->qc_signature_path);
            }
            $update['qc_signature_path'] = $request->file('qr_signature')->store('qc-signatures', 'public');
        }

        $qc->update($update);

        return redirect()->route('show-qc')->with('success', 'Akun QC diperbarui.');
    }

    /* =========================================================
     * QA
     * =======================================================*/
    public function showQA()
    {
        $users = User::query()
            ->where('role', 'QA')
            ->orderBy('name')
            ->get();

        return view('users_management.show_qa', compact('users'));
    }

    public function storeQA(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'min:3', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:7'],
        ]);

        User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'role'              => 'QA',
            'email_verified_at' => now(),
            'password'          => Hash::make($data['password']),
        ]);

        return redirect()->route('show-qa')->with('success', 'Akun QA berhasil dibuat.');
    }

    public function editQA($id)
    {
        $qa = User::query()
            ->where('id', $id)
            ->where('role', 'QA')
            ->firstOrFail();

        return view('users_management.edit_qa', compact('qa'));
    }

    public function updateQA(Request $request, $id)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'min:3', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email,' . $id],
            'password' => ['nullable', 'string', 'min:7'],
        ]);

        $update = [
            'name'  => $data['name'],
            'email' => $data['email'],
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        User::query()
            ->where('id', $id)
            ->where('role', 'QA')
            ->update($update);

        return redirect()->route('show-qa')->with('success', 'Akun QA diperbarui.');
    }

    /* =========================================================
     * LOGIN SEBAGAI PPIC (impersonate)
     * =======================================================*/
    public function loginAsPPIC($id)
    {
        $ppic = User::query()
            ->where('id', $id)
            ->where('role', 'PPIC')
            ->firstOrFail();

        if (!session()->has('impersonator_id')) {
            session(['impersonator_id' => Auth::id()]);
        }

        Auth::login($ppic);

        return redirect()->route('dashboard')
            ->with('success', 'Sekarang login sebagai PPIC: ' . $ppic->name);
    }

    /* =========================================================
     * COMMON
     * =======================================================*/
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $role = $user->role;

        if ($role === 'QC' && $user->qc_signature_path) {
            if (Storage::disk('public')->exists($user->qc_signature_path)) {
                Storage::disk('public')->delete($user->qc_signature_path);
            }
        }

        $user->delete();

        return match ($role) {
            'Produksi' => redirect()->route('show-produksi')->with('success', 'Akun Produksi dihapus.'),
            'QC'       => redirect()->route('show-qc')->with('success', 'Akun QC dihapus.'),
            'QA'       => redirect()->route('show-qa')->with('success', 'Akun QA dihapus.'),
            'PPIC'     => redirect()->route('show-ppic')->with('success', 'Akun PPIC dihapus.'),
            default    => redirect()->route('dashboard')->with('success', 'Akun dihapus.'),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function profile()
    {
        $user = Auth::user();
        return view('users_management.setting_profile', compact('user'));
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
        ]);

        User::where('id', Auth::id())->update([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        return redirect()->route('show-profile')->with('success', 'Profil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'new_password'         => ['required', 'string', 'min:7'],
            'confirm_new_password' => ['required', 'same:new_password'],
        ]);

        User::where('id', Auth::id())->update([
            'password' => Hash::make($data['new_password']),
        ]);

        return redirect()->route('show-profile')->with('success', 'Password diganti.');
    }

    /* =========================================================
     * Alias kompatibilitas lama (opsional)
     * =======================================================*/
    public function showSiswa()                  { return $this->showProduksi(); }
    public function storeSiswa(Request $r)       { return $this->storeProduksi($r); }
    public function editSiswa($id)               { return $this->editProduksi($id); }
    public function updateSiswa(Request $r, $id) { return $this->updateProduksi($r, $id); }
}
