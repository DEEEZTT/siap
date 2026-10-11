<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', ['users' => User::query()->orderBy('role')->orderBy('name')->paginate(20)]);
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:120'], 'username' => ['required', 'string', 'max:120', 'unique:users,username'], 'email' => ['required', 'email', 'max:190', 'unique:users,email'], 'role' => ['required', 'in:tu,kepala_tu,humas,kepsek'], 'position' => ['nullable', 'string', 'max:120'], 'unit' => ['nullable', 'string', 'max:120'], 'password' => ['required', 'confirmed', 'min:8']],
            [
                'username.unique' => 'Username sudah dipakai. Gunakan username yang berbeda.',
                'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            ],
        );
        $user = User::query()->create($data);
        ActivityLog::record('akun_dibuat', 'Membuat akun '.$user->username.' dengan hak akses '.$user->role.'.', $user);

        return redirect()->route('users.index')->with('success', 'Akun pengguna dibuat.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:120'], 'username' => ['required', 'string', 'max:120', 'unique:users,username,'.$user->id], 'email' => ['required', 'email', 'max:190', 'unique:users,email,'.$user->id], 'role' => ['required', 'in:tu,kepala_tu,humas,kepsek'], 'position' => ['nullable', 'string', 'max:120'], 'unit' => ['nullable', 'string', 'max:120'], 'password' => ['nullable', 'confirmed', 'min:8']],
            [
                'username.unique' => 'Username sudah dipakai. Gunakan username yang berbeda.',
                'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            ],
        );
        if ($user->is($request->user()) && $data['role'] !== 'kepala_tu') {
            return back()->withErrors(['role' => 'Akun Kepala TU yang sedang digunakan tidak dapat menghapus hak pengelolaannya sendiri.'])->withInput();
        } if (empty($data['password'])) {
            unset($data['password']);
        } $roleBefore = $user->role;
        $user->update($data);
        ActivityLog::record('akun_diubah', 'Mengubah akun '.$user->username.($roleBefore !== $user->role ? ' (hak akses '.$roleBefore.' → '.$user->role.')' : '').(isset($data['password']) ? ' dan mengganti kata sandi' : '').'.', $user);

        return redirect()->route('users.index')->with('success', 'Akun diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');
        abort_if($user->role === 'kepala_tu' && User::query()->where('role', 'kepala_tu')->count() <= 1, 409, 'Buat akun Kepala TU lain sebelum menghapus pengelola terakhir.');
        ActivityLog::record('akun_dihapus', 'Menghapus akun '.$user->username.'.', $user);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Akun dihapus.');
    }
}
