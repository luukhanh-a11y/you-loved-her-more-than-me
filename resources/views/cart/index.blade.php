@extends('layouts.app')

@section('body')
<div class="container py-5">
    <h2 class="text-uppercase fw-bold mb-4 text-center">Giỏ hàng của {{ Auth::user()->name }}</h2>

    @if(isset($cart) && $cart->items->count() > 0)
        <div class="row">
            <div class="col-lg-8">
                {{-- Thông báo lỗi/thành công từ AJAX sẽ hiện ở đây --}}
                <div id="cart-notification"></div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;"><input type="checkbox" id="check-all"></th>
                                <th>Sản phẩm</th>
                                <th>Phân loại</th> {{-- Cột mới để chỉnh size/màu --}}
                                <th>Đơn giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                                <th>Xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cart->items as $item)
                                @php 
                                    $product = $item->variant->product;
                                    $price = $product->price_sale ?? $product->price;
                                    $subtotal = $price * $item->quantity;
                                    
                                    // Lấy danh sách size và color unique từ variants của sản phẩm
                                    $sizes = $product->variants->pluck('size')->unique();
                                    $colors = $product->variants->pluck('color')->unique();
                                @endphp
                                <tr class="cart-item-row" data-id="{{ $item->id }}" data-price="{{ $price }}">
                                    <td>
                                        {{-- Checkbox tính tiền --}}
                                        <input type="checkbox" class="item-checkbox form-check-input" 
                                               value="{{ $item->id }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $product->img_thumbnail }}" width="60" class="me-3 rounded">
                                            <div>
                                                <h6 class="mb-0 fw-bold">
                                                    <a href="{{ route('shop.detail', $product->slug) }}" class="text-dark text-decoration-none">
                                                        {{ $product->name }}
                                                    </a>
                                                </h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {{-- Dropdown chọn Size --}}
                                        <select class="form-select form-select-sm mb-1 cart-update-trigger cart-size">
                                            @foreach($sizes as $size)
                                                <option value="{{ $size }}" {{ $item->variant->size == $size ? 'selected' : '' }}>
                                                    Size: {{ $size }}
                                                </option>
                                            @endforeach
                                        </select>
                                        {{-- Dropdown chọn Màu --}}
                                        <select class="form-select form-select-sm cart-update-trigger cart-color">
                                            @foreach($colors as $color)
                                                <option value="{{ $color }}" {{ $item->variant->color == $color ? 'selected' : '' }}>
                                                    Màu: {{ $color }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="item-price-display">{{ number_format($price) }}đ</td>
                                    <td>
                                        {{-- Input số lượng --}}
                                        <input type="number" value="{{ $item->quantity }}" min="1" 
                                               class="form-control text-center cart-qty cart-update-trigger" style="width: 70px;">
                                    </td>
                                    <td class="fw-bold item-subtotal">{{ number_format($subtotal) }}đ</td>
                                    <td>
                                        <a href="{{ route('cart.remove', $item->id) }}" class="btn btn-sm btn-outline-danger border-0">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('cart.clear') }}" class="btn btn-outline-secondary rounded-0 mt-3">Xóa tất cả</a>
            </div>

            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card border-0 shadow-sm bg-light sticky-top" style="top: 20px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Cộng giỏ hàng</h5>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fw-bold fs-5">Tổng thanh toán:</span>
                            <span class="fw-bold fs-5 text-danger" id="total-payment">0đ</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Đã chọn:</span>
                            <span id="total-items-count">0 sản phẩm</span>
                        </div>
                        
                        {{-- Nút thanh toán - Gửi các ID đã check đi --}}
                        <form action="{{ route('payment.checkout') }}" method="GET" id="checkout-form">
                            {{-- Input hidden này sẽ được điền bằng JS khi submit --}}
                            <input type="hidden" name="selected_items" id="selected_items_input">
                            <button type="submit" class="btn btn-dark w-100 py-2 rounded-0 text-uppercase fw-bold" id="btn-checkout" disabled>
                                Tiến hành thanh toán
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-5">
            <i class="bi bi-cart-x fs-1 text-muted mb-3 d-block"></i>
            <p class="lead">Giỏ hàng của bạn đang trống.</p>
            <a href="/" class="btn btn-dark rounded-0 px-4">Mua sắm ngay</a>
        </div>
    @endif
</div>

{{-- JAVASCRIPT XỬ LÝ LOGIC --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    
    // 1. Hàm tính tổng tiền các mục được check
    function calculateTotal() {
        let total = 0;
        let count = 0;
        let selectedIds = [];

        $('.item-checkbox:checked').each(function() {
            let row = $(this).closest('tr');
            let price = parseFloat(row.data('price')); // Lấy giá gốc từ data attribute
            let qty = parseInt(row.find('.cart-qty').val());
            
            total += price * qty;
            count += qty;
            selectedIds.push($(this).val());
        });

        // Format tiền tệ VNĐ
        $('#total-payment').text(new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(total));
        $('#total-items-count').text(count + ' sản phẩm');
        
        // Cập nhật trạng thái nút thanh toán
        if (count > 0) {
            $('#btn-checkout').prop('disabled', false);
        } else {
            $('#btn-checkout').prop('disabled', true);
        }

        // Cập nhật input hidden cho form submit
        $('#selected_items_input').val(selectedIds.join(','));
    }

    // 2. Xử lý Check All
    $('#check-all').change(function() {
        $('.item-checkbox').prop('checked', $(this).prop('checked'));
        calculateTotal();
    });

    // 3. Xử lý khi check từng item
    $('.item-checkbox').change(function() {
        calculateTotal();
        // Bỏ check all nếu có 1 item bị bỏ check
        if ($('.item-checkbox:checked').length == $('.item-checkbox').length) {
            $('#check-all').prop('checked', true);
        } else {
            $('#check-all').prop('checked', false);
        }
    });

    // 4. AJAX Cập nhật Giỏ hàng (Số lượng, Size, Màu)
    $('.cart-update-trigger').on('change', function() {
        let row = $(this).closest('tr');
        let itemId = row.data('id');
        let qty = row.find('.cart-qty').val();
        let size = row.find('.cart-size').val();
        let color = row.find('.cart-color').val();

        $.ajax({
            url: '{{ route("cart.update") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                item_id: itemId,
                quantity: qty,
                size: size,
                color: color
            },
            success: function(response) {
                // Cập nhật lại thành tiền của item đó trên giao diện
                row.find('.item-subtotal').text(response.item_total);
                
                calculateTotal(); // Tính lại tổng tiền giỏ hàng
                console.log(response.success);
            },
            error: function(xhr) {
                alert(xhr.responseJSON.error || 'Có lỗi xảy ra. Vui lòng kiểm tra lại số lượng hoặc kho hàng.');
                // Có thể reload lại trang nếu lỗi quá nghiêm trọng
                // location.reload(); 
            }
        });
    });
});
</script>
@endsection