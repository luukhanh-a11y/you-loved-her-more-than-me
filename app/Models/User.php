<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'google_id',  // cho Google OAuth
        'avatar',     // ảnh đại diện
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Quan hệ: 1 user có nhiều đơn hàng
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Quan hệ: 1 user có 1 giỏ hàng
     */
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Quan hệ: 1 user có nhiều cartItems (qua bảng carts)
     */
    public function cartItems()
    {
        return $this->hasManyThrough(CartItem::class, Cart::class);
    }
}
