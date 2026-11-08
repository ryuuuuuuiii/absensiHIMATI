<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('member')->check()) {
            $member = Auth::guard('member')->user();

            if ($member && $member->isDismissed()) {
                Auth::guard('member')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'Akun Anda telah dinonaktifkan karena status SP 3 (Pemberhentian Pengurus).']);
            }
        }

        return $next($request);
    }
}
