<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->merge(['email' => $this->normalizeEmail($request->input('email'))]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The email or password is incorrect.'])
                ->onlyInput('email');
        }

        if ($request->user()->verification_status === User::STATUS_REJECTED) {
            return $this->logoutRejected($request);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => $this->normalizeEmail($request->input('email')),
        ]);

        $data = $request->validate([
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'name' => ['required', 'string', 'min:3', 'max:100', "regex:/^[\pL\s.'-]+$/u"],
            'phone' => ['required', 'regex:/^0[0-9]{9,14}$/'],
            'email' => ['required', 'string', 'max:255', 'email:rfc,strict', 'regex:/^[^@\s]+@[^@\s]+\.[a-z]{2,}$/', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'max:64', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'nik.required' => 'NIK is required.',
            'nik.digits' => 'NIK must be exactly 16 digits.',
            'nik.unique' => 'This NIK is already registered.',
            'name.required' => 'Full name is required.',
            'name.min' => 'Full name must be at least 3 characters.',
            'name.max' => 'Full name can be at most 100 characters.',
            'name.regex' => 'Full name may only contain letters, spaces, dots, apostrophes and dashes.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number must start with 0 and contain 10 to 15 digits.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.regex' => 'Please enter a complete email address, such as name@gmail.com.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password can be at most 64 characters.',
        ]);

        $user = User::create($data);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.verification');
    }

    public function verification(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('home');
        }

        if ($user->isApproved()) {
            return redirect()->route('my-rents')->with('success', 'Your account has been approved. Welcome to iGarage!');
        }

        if ($user->verification_status === User::STATUS_REJECTED) {
            return $this->logoutRejected($request);
        }

        return view('auth.verification', compact('user'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }

    private function logoutRejected(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error',
            'Your registration was rejected because your ID card data could not be verified. Please contact the iGarage admin.');
    }

    private function normalizeEmail(mixed $email): string
    {
        return strtolower(preg_replace('/\s+/', '', (string) $email));
    }
}
