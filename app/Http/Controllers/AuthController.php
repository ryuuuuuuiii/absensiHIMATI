<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }

        if (Auth::guard('member')->check()) {
            return redirect()->route('portal.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');
        $intended = $request->session()->pull('url.intended', null);

        // 1. Coba autentikasi sebagai Admin (guard 'web')
        if (Auth::guard('web')->attempt($credentials, $remember)) {
            Auth::guard('member')->logout();
            $request->session()->regenerate();

            $redirectUrl = route('dashboard');
            if ($intended && ! str_contains($intended, '/portal')) {
                $redirectUrl = $intended;
            }

            return redirect()->to($redirectUrl)
                ->with('success', 'Selamat datang kembali, '.Auth::guard('web')->user()->nama.'!');
        }

        // 2. Coba autentikasi sebagai Pengurus / Member (guard 'member')
        if (Auth::guard('member')->attempt($credentials, $remember)) {
            $member = Auth::guard('member')->user();

            if ($member->isDismissed()) {
                Auth::guard('member')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'Akun Anda telah dinonaktifkan karena status SP 3 (Pemberhentian Pengurus).']);
            }

            Auth::guard('web')->logout();
            $request->session()->regenerate();

            $member->forceFill(['last_login_at' => now()])->save();

            $redirectUrl = route('portal.dashboard');
            if ($intended && ! preg_match('#/(dashboard|members|meetings|events|reports|settings)(\b|/)#', $intended)) {
                $redirectUrl = $intended;
            }

            return redirect()->to($redirectUrl)
                ->with('success', 'Selamat datang, '.$member->nama_lengkap.'!');
        }

        // 3. Jika bukan Admin maupun Pengurus
        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Email atau password salah.']);
    }

    public function logout(Request $request)
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        if (Auth::guard('member')->check()) {
            Auth::guard('member')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'Anda telah berhasil keluar.');
    }
}
