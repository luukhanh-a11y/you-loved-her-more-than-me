<div class="product-card">
    <!-- Image Container -->
    <div class="img-box">
        <a href="{{ route('shop.detail', $product->slug) }}">
            <img src="{{ $product->img_thumbnail }}" 
                 class="product-img" 
                 alt="{{ $product->name }}"
                 loading="lazy"
                 onerror="this.src='https://placehold.co/400x400/f8f9fa/999?text=No+Image'">
        </a>
        
        <!-- Discount Badge -->
        @if($product->price_sale)
            @php
                $discount = round((($product->price - $product->price_sale) / $product->price) * 100);
            @endphp
            <span class="badge bg-danger position-absolute top-0 start-0 m-2 rounded-pill">
                -{{ $discount }}%
            </span>
        @endif
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <form action="{{ route('cart.add') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                @if($product->variants->isNotEmpty())
                    <input type="hidden" name="variant_id" value="{{ $product->variants->first()->id }}">
                @endif
                <button type="submit" class="btn" title="Thêm vào giỏ">
                    <i class="bi bi-cart-plus"></i>
                </button>
            </form>
            
            <a href="{{ route('shop.detail', $product->slug) }}" 
               class="btn" 
               title="Xem chi tiết">
                <i class="bi bi-eye"></i>
            </a>
        </div>
    </div>
    
    <!-- Card Body -->
    <div class="card-body">
        <!-- Brand -->
        <div class="text-muted text-uppercase small mb-1">
            {{ $product->brand->name ?? 'Thương hiệu' }}
        </div>
        
        <!-- Product Name -->
        <h6 class="card-title">
            <a href="{{ route('shop.detail', $product->slug) }}" 
               class="text-dark" 
               title="{{ $product->name }}">
                {{ Str::limit($product->name, 50) }}
            </a>
        </h6>
        
        <!-- Price -->
        <div class="price-box">
            @if($product->price_sale)
                <span class="text-danger fw-bold me-2">
                    {{ number_format($product->price_sale) }}đ
                </span>
                <span class="text-muted text-decoration-line-through small">
                    {{ number_format($product->price) }}đ
                </span>
            @else
                <span class="fw-bold text-dark">
                    {{ number_format($product->price) }}đ
                </span>
            @endif
        </div>
        
        <!-- Stock Status -->
        @if($product->variants->sum('quantity') > 0)
            <small class="text-success">
                <i class="bi bi-check-circle"></i> Còn hàng
            </small>
        @else
            <small class="text-danger">
                <i class="bi bi-x-circle"></i> Hết hàng
            </small>
        @endif
    </div>
</div>