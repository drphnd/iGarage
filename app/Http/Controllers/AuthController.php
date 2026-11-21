<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Login, renter registration, logout, and the verification (blocked) page.
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
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The email or password is incorrect.'])
                ->onlyInput('email');
        }

        // New session id after login prevents session fixation.
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * Show the renter registration form.
     *
     * @return View
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new renter. The account starts as "pending" until an admin approves it.
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
            'nik.required' => 'NIK is required.',
            'nik.digits' => 'NIK must be exactly 16 digits.',
            'nik.unique' => 'This NIK is already registered.',
            'name.required' => 'Full name is required.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number must start with 0 and contain 10 to 15 digits.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        // role (renter) and verification_status (pending) come from the database defaults,
        // they are not fillable so the form cannot change them.
        $user = User::create($data);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.verification');
    }

    /**
     * Show the "waiting for verification" or "rejected" page to renters who are not approved.
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

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
