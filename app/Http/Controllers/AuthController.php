<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Login, tenant registration, logout, and the verification (blocked) page.
 */
class AuthController
{
    /**
     * Show the login form.
     *
     * @return View
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Log a user in with email and password.
     *
     * @param  Request  $request
     * @return RedirectResponse  home on success, back to the form with an error otherwise
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->onlyInput('email');
        }

        // New session id after login prevents session fixation.
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * Show the tenant registration form.
     *
     * @return View
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new tenant. The account starts as "pending" until an admin approves it.
     *
     * @param  Request  $request
     * @return RedirectResponse  to the verification page
     */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^0[0-9]{9,14}$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Nomor telepon harus diawali 0 dan berisi 10 sampai 15 angka.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        // role (tenant) and verification_status (pending) come from the database defaults,
        // they are not fillable so the form cannot change them.
        $user = User::create($data);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.verification');
    }

    /**
     * Show the "waiting for verification" or "rejected" page to tenants who are not approved.
     *
     * @param  Request  $request
     * @return View|RedirectResponse  approved users are sent home instead
     */
    public function verification(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isApproved()) {
            return redirect()->route('home');
        }

        return view('auth.verification', compact('user'));
    }

    /**
     * Log the user out and invalidate the session.
     *
     * @param  Request  $request
     * @return RedirectResponse  to the login page
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Kamu sudah logout.');
    }
}
