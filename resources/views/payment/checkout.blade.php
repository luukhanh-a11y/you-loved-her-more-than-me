@extends('layouts.app')

@section('body')
<div class="container py-5">
    <h2 class="text-center mb-4 text-uppercase fw-bold">Thanh toán đơn hàng</h2>
    
    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Thông tin giao hàng</h5>
                </div>
                <div class="card-body">
                    {{-- Form gửi sang MoMoController --}}
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
                    </form>
                </div>
            </div>
        </div>

        {{-- Phần hiển thị sản phẩm giữ nguyên như code cũ, chỉ cần nút submit trỏ đúng form --}}
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Đơn hàng của bạn</h5>
                    {{-- Loop hiển thị sản phẩm ở đây (dùng biến $selectedItems từ controller) --}}
                    <ul class="list-group list-group-flush mb-3">
                        @foreach($selectedItems as $item)
                            <li class="list-group-item d-flex justify-content-between lh-sm">
                                <div>
                                    <h6 class="my-0">{{ $item->variant->product->name }}</h6>
                                    <small class="text-muted">Size: {{ $item->variant->size }} | SL: {{ $item->quantity }}</small>
                                </div>
                                <span class="text-muted">
                                    {{ number_format(($item->variant->product->price_sale ?? $item->variant->product->price) * $item->quantity) }}đ
                                </span>
                            </li>
                        @endforeach
                        <li class="list-group-item d-flex justify-content-between bg-light">
                            <span class="fw-bold">Tổng tiền (bao gồm ship)</span>
                            <strong class="text-danger">{{ number_format($total) }}đ</strong>
                        </li>
                    </ul>

                    <button type="submit" form="payment-form" class="btn btn-danger w-100 py-3 fw-bold">
                        THANH TOÁN MOMO
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection