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
        // Same format as when registering, so "Budi@Gmail.com " still finds budi@gmail.com.
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
        // Clean the input before validating: trimmed name, lowercase email without spaces.
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => $this->normalizeEmail($request->input('email')),
        ]);

        $data = $request->validate([
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'name' => ['required', 'string', 'min:3', 'max:100', "regex:/^[\pL\s.'-]+$/u"],
            'phone' => ['required', 'regex:/^0[0-9]{9,14}$/'],
            // rfc,strict: a valid address by the email standard. The regex also requires a domain ending such as .com.
            'email' => ['required', 'string', 'max:255', 'email:rfc,strict', 'regex:/^[^@\s]+@[^@\s]+\.[a-z]{2,}$/', 'unique:users,email'],
            // At least 8 characters with an uppercase letter, a lowercase letter, a number and a symbol.
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

    /**
     * Email in one format: spaces removed and lowercase, so the same address is never registered twice.
     *
     * @param  mixed  $email
     * @return string
     */
    private function normalizeEmail(mixed $email): string
    {
        return strtolower(preg_replace('/\s+/', '', (string) $email));
    }
}
