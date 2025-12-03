@extends('layouts.app')

@section('body')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm rounded-0">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0 text-center">Thanh toán MoMo</h4>
                </div>
                
                <div class="card-body p-4">
                    {{-- Hiển thị lỗi --}}
                    @if(session('error'))
                        <div class="alert alert-danger rounded-0">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('momo.create') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Số tiền thanh toán (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   name="amount" 
                                   class="form-control rounded-0 @error('amount') is-invalid @enderror" 
                                   min="1000" 
                                   max="50000000"
                                   value="{{ old('amount', 50000) }}"
                                   required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Tối thiểu: 1,000 VNĐ - Tối đa: 50,000,000 VNĐ</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Nội dung thanh toán <span class="text-danger">*</span></label>
                            <textarea name="order_info" 
                                      class="form-control rounded-0 @error('order_info') is-invalid @enderror" 
                                      rows="3" 
                                      required>{{ old('order_info', 'Thanh toán đơn hàng tại SoildTech') }}</textarea>
                            @error('order_info')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info rounded-0">
                            <i class="bi bi-info-circle"></i>
                            <strong>Lưu ý:</strong> Bạn sẽ được chuyển đến trang thanh toán MoMo để hoàn tất giao dịch.
                        </div>

                        <button type="submit" class="btn btn-danger w-100 rounded-0 py-3">
                            <img src="https://developers.momo.vn/v3/assets/images/square-logo.svg" 
                                 alt="MoMo" 
                                 style="height: 24px;" 
                                 class="me-2">
                            Thanh toán với MoMo
                        </button>
                    </form>

                    <div class="mt-4 text-center">
                        <small class="text-muted">
                            Giao dịch được bảo mật bởi MoMo Payment Gateway
                        </small>
                    </div>
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
                        <li>Số điện thoại: <code>0999999999</code></li>
                        <li>Mật khẩu: <code>999999</code></li>
                        <li>OTP: <code>888888</code> hoặc <code>999999</code></li>
                    </ul>
                    <p class="mt-2 mb-0 small text-muted">
                        * Đây là thông tin test của MoMo Sandbox. Không sử dụng thông tin thật.
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection