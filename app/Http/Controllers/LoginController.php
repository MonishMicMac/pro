<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function index()
    {
        if (Auth::check()) return redirect()->route('dashboard.index');
        return view('auth.login');
    }

    //    public function login(Request $request)
    // {
    //     // 1. Validate 'username' from form, but we will map it to 'name'
    //     $request->validate([
    //         'username' => ['required'],
    //         'password' => ['required'],
    //     ]);

    //     // 2. Map the 'username' input to the 'name' database column
    //     $credentials = [
    //         'name'     => $request->username, // This tells Laravel to check the 'name' column
    //         'password' => $request->password,
    //     ];

    //     if (Auth::attempt($credentials, $request->has('remember'))) {
    //         $request->session()->regenerate();
    //         return redirect()->intended(route('dashboard.index'));
    //     }

    //     throw ValidationException::withMessages([
    //         'username' => 'Invalid credentials.',
    //     ]);
    // }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        // USERS credentials (name)
        $userCredentials = [
            'name' => $request->username,
            'password' => $request->password
        ];

        // FABRICATOR credentials (mobile)
        $fabricatorCredentials = [
            'mobile' => $request->username, // same input field
            'password' => $request->password
        ];

        // 1️⃣ Try USERS table
        if (Auth::guard('web')->attempt($userCredentials)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard.index');
        }

        // 2️⃣ Try FABRICATORS table (mobile)
        if (Auth::guard('fabricator')->attempt($fabricatorCredentials)) {
            $request->session()->regenerate();
            return redirect()->route('fabricator.dashboard');
        }

        return back()->withErrors([
            'username' => 'Invalid credentials'
        ]);
    }



    // public function logout(Request $request)
    // {
    //     Auth::logout();
    //     $request->session()->invalidate();
    //     $request->session()->regenerateToken();
    //     return redirect()->route('login');
    // }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        Auth::guard('fabricator')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
