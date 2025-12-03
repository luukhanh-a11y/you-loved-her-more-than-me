@extends('layouts.app')

@section('body')
<div class="container py-5">
    <h2 class="text-center mb-4 text-uppercase fw-bold">Thanh toán đơn hàng</h2>
    
    <div class="row">
        {{-- CỘT TRÁI: FORM THÔNG TIN GIAO HÀNG --}}
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">1. Thông tin giao hàng</h5>
                </div>
                <div class="card-body">
                    {{-- Hiển thị thông báo lỗi nếu có --}}
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('momo.create') }}" method="POST" id="payment-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="user_name" class="form-control" 
                                   value="{{ Auth::user()->name ?? '' }}" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="user_email" class="form-control" 
                                       value="{{ Auth::user()->email ?? '' }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại</label>
                                <input type="text" name="user_phone" class="form-control" 
                                       value="{{ Auth::user()->phone ?? '' }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Địa chỉ nhận hàng</label>
                            <textarea name="user_address" class="form-control" rows="3" required 
                                      placeholder="Số nhà, tên đường, phường/xã..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ghi chú (Tùy chọn)</label>
                            <textarea name="user_note" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="alert alert-info rounded-0">
                            <i class="bi bi-info-circle"></i>
                            <strong>Lưu ý:</strong> Bạn sẽ được chuyển đến trang thanh toán MoMo để hoàn tất giao dịch.
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- CỘT PHẢI: THÔNG TIN ĐƠN HÀNG --}}
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">2. Đơn hàng của bạn</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($selectedItems as $item)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $item->variant->product->img_thumbnail }}" 
                                         width="50" class="rounded me-3" alt="Product Image">
                                    <div>
                                        <h6 class="mb-0 text-truncate" style="max-width: 200px;">
                                            {{ $item->variant->product->name }}
                                        </h6>
                                        <small class="text-muted">
                                            Size: {{ $item->variant->size }} | Màu: {{ $item->variant->color }} | SL: {{ $item->quantity }}
                                        </small>
                                    </div>
                                </div>
                                <span class="fw-bold">
                                    {{ number_format(($item->variant->product->price_sale ?? $item->variant->product->price) * $item->quantity) }}đ
                                </span>
                            </li>
                        @endforeach
                        
                        <li class="list-group-item d-flex justify-content-between py-3">
                            <span>Tạm tính:</span>
                            <strong>{{ number_format($subtotal) }}đ</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-3">
                            <span>Phí vận chuyển:</span>
                            <strong>{{ number_format($shippingFee) }}đ</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-3 bg-light">
                            <span class="fw-bold fs-5">Tổng cộng:</span>
                            <span class="fw-bold fs-5 text-danger">{{ number_format($total) }}đ</span>
                        </li>
                    </ul>
                </div>
                <div class="card-footer p-3 bg-white">
                    <button type="submit" form="payment-form" class="btn btn-danger w-100 py-3 fw-bold">
                        <img src="https://developers.momo.vn/v3/assets/images/square-logo.svg" 
                             alt="MoMo" style="height: 20px;" class="me-2">
                        THANH TOÁN MOMO NGAY
                    </button>
                </div>
            </div>

            {{-- Thông tin test (chỉ hiện khi ở môi trường development) --}}
            @if(config('app.env') === 'local')
                <div class="card mt-4 border-warning rounded-0">
                    <div class="card-header bg-warning text-dark">
                        <strong>⚠️ Môi trường Test (Sandbox)</strong>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Tài khoản MoMo Test:</strong></p>
                        <ul class="mb-0">
                            <li>Số điện thoại: <code>0963412033</code></li>
                            <li>Mật khẩu: <code>Momo12345@</code></li>
                            <li>OTP: <code>000000</code></li>
                        </ul>
                        <p class="mt-2 mb-0 small text-muted">
                            * Dùng thông tin này để đăng nhập trên trang MoMo.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection