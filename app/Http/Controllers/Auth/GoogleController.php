<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->user();

        $userProvider = UserProvider::where('provider', 'google')
            ->where('provider_id', $googleUser->getId())
            ->first();

        if ($userProvider) {
            // 既存ユーザー
            $user = $userProvider->user;
        } else {
            // 新規ユーザー作成
            $user = User::create([
                'name' => 'ダミー姓名', // 個人情報は取得しない
                'email' => 'dummy_'. Str::uuid() .'@example.jp', // 個人情報は取得しない
                'password' => '', // Google認証なので空
            ]);
            UserProvider::create([
                'user_id' => $user->id,
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
            ]);
        }

        Auth::login($user);

        return redirect()->to(config('app.url', 'http://localhost'));
    }
}
