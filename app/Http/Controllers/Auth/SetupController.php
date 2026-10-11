<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect()->route('login');
        }

        return view('auth.setup');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(User::query()->exists(), 404);
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:120'], 'username' => ['required', 'string', 'max:120', 'unique:users,username'], 'email' => ['required', 'email', 'max:190', 'unique:users,email'], 'password' => ['required', 'confirmed', 'min:8']],
            [
                'username.unique' => 'Username sudah dipakai. Gunakan username yang berbeda.',
                'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            ],
        );
        $user = User::query()->create([...$data, 'role' => 'kepala_tu']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Akun Kepala TU berhasil dibuat.');
    }
}
