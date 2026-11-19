<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (!Auth::check()) {
            return view('admin.auth.login');
        }

        return Auth::user()->role === 'tenant'
            ? redirect()->route('tenant.dashboard')
            : redirect()->route('admin.dashboard');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // role is matched as a column, so a tenant who types their own correct
        // password is still refused here instead of being signed into the panel.
        if (!Auth::attempt($credentials + ['role' => 'admin'], $request->boolean('remember'))) {
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Those credentials do not match an admin account.']);
        }

        // New session ID after a successful login so a session id handed out to
        // an attacker before login cannot be reused afterwards.
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Welcome back, ' . $request->user()->name . '.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
