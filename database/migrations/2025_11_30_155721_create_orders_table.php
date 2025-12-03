<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // File: create_orders_table.php
 public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Thông tin người nhận (Khớp với Model của bạn)
            $table->string('user_name');
            $table->string('user_email');
            $table->string('user_phone');
            $table->string('user_address');
            $table->text('user_note')->nullable();
            
            // Logic ship (Khớp với Model)
            $table->boolean('is_ship_user_same_user')->default(true);
            
            // Quản lý trạng thái (Khớp với Model)
            $table->string('status_order')->default('pending'); // pending, processing, shipping, completed, cancelled
            $table->string('status_payment')->default('unpaid'); // unpaid, paid, failed
            
            // Tiền nong (Khớp với Model)
            $table->decimal('total_price', 15, 2); // Tổng tiền (Hàng + Ship)
            
            // === CÁC CỘT BỔ SUNG CHO MOMO (BẮT BUỘC) ===
            $table->string('order_code')->unique(); // Mã đơn hàng gửi sang MoMo (VD: ORDER_123_456)
            $table->string('request_id')->unique()->nullable(); // Mã request gửi MoMo
            $table->string('trans_id')->nullable(); // Mã giao dịch MoMo trả về
            $table->string('pay_type')->nullable(); // Loại thanh toán (qr, app, card...)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
