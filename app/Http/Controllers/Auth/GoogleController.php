<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleController extends Controller
{
    /**
     * Redirect đến trang đăng nhập Google
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Xử lý callback từ Google
     */
    public function handleGoogleCallback()
    {
        try {
            // Lấy thông tin user từ Google
            $googleUser = Socialite::driver('google')->user();
            
            // Tìm user theo Google ID hoặc email
            $user = User::where('google_id', $googleUser->getId())
                       ->orWhere('email', $googleUser->getEmail())
                       ->first();

            if ($user) {
                // Nếu user đã tồn tại, cập nhật Google ID và avatar
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                    ]);
                }
            } else {
                // Tạo user mới
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                    'role' => 'user', // Mặc định là user thường
                ]);
            }

            // Đăng nhập user
            Auth::login($user, true);

            // Redirect về trang chủ hoặc trang trước đó
            return redirect()->intended('/')->with('success', 'Đăng nhập thành công!');

        } catch (Exception $e) {
            // Log lỗi
            \Illuminate\Support\Facades\Log::error('Google Login Error: ' . $e->getMessage());
            
            return redirect()->route('login')
                           ->with('error', 'Đăng nhập Google thất bại! Vui lòng thử lại.');
        }
    }
}