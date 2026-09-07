<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('pages.auth.signin', ['title' => 'Masuk']);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        AuditLogger::record($request, 'auth.login', $request->user(), ['role' => $request->user()->role]);

        return redirect()->intended(route($request->user()->dashboardRouteName(), absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        AuditLogger::record($request, 'auth.logout', $request->user(), ['role' => $request->user()->role]);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
