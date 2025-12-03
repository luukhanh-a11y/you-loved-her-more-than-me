<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

   // app/Models/Order.php

protected $fillable = [
    'user_id',
    'user_name',
    'user_email',
    'user_phone',
    'user_address',
    'user_note',
    'is_ship_user_same_user',
    'status_order',
    'status_payment',
    'total_price',
    
    // BỔ SUNG CÁC TRƯỜNG MOMO
    'order_code', 
    'request_id', 
    'trans_id',
    'pay_type'
];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}