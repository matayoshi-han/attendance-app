<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    //会員登録画面の表示
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    //会員登録処理
    public function registerPost(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    //ログイン画面の表示
    public function showLoginForm()
    {
        return view('auth.login');
    }

    //ログイン処理
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // メール認証がまだの場合は、認証通知画面へリダイレクト
            if (!$request->user()->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return redirect()->intended(route('attendance.state'));
        }

        return back()->withErrors([
            'login_error' => 'ログイン情報が登録されていません',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        // ログアウトする前に、管理権限を持っているかチェック
        $isAdmin = Auth::check() && Auth::user()->role === 'admin';

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 管理者だった場合は管理者ログインへ、それ以外は一般ログインへ
        if ($isAdmin) {
            return redirect()->route('admin.login');
        }

        return redirect('/login');
    }


    // 管理者ログイン画面の表示
    public function showAdminLoginForm()
    {
        return view('auth/admin_login'); // 作成したBladeを指定
    }

    // 管理者ログイン処理
    public function adminLogin(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            if (Auth::user()->role === 'admin') {
                $request->session()->regenerate();

                // セッションにある「以前の遷移先」をクリアして、強制的にパスを指定する
                $request->session()->forget('url.intended');
                return redirect('/admin/attendance/list');
            }

            Auth::logout();
            return back()->withErrors(['login_error' => '管理者以外のユーザーはログインできません']);
        }

        return back()->withErrors(['login_error' => 'ログイン情報が登録されていません']);
    }
}
