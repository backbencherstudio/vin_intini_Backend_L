<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.universities.index');
        }
        return view('admin.login');
    }

    public function adminlogin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('web')->attempt($credentials)) {

            $user = Auth::guard('web')->user();

            if ($user->email === 'admin@gmail.com') {
                $request->session()->regenerate();
                return redirect()->intended(route('admin.universities.index'));
            }

            Auth::guard('web')->logout();
            return back()->withErrors(['email' => 'You do not have permission to access the admin panel.']);
        }

        return back()->withErrors(['email' => 'The provided credentials do not match our records.']);
    }

    public function adminLogout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
