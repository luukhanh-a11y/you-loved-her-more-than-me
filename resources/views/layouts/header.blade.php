<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="{{ route('home') }}">
            SOLID <span class="brand-highlight">TECH</span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link text-danger" href="{{ route('shop.hotSale') }}">
                        <i class="bi bi-fire"></i> HOT SALE
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('brands.index') }}">Thương hiệu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('shop.category', 'giay-nam') }}">Giày Nam</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('shop.category', 'giay-nu') }}">Giày Nữ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('shop.category', 'phu-kien') }}">Phụ kiện</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('return.policy') }}">Chính sách</a>
                </li>
            </ul>
            
            <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                <form action="{{ route('shop.index') }}" method="GET" class="d-flex">
                    <div class="input-group">
                        <input class="form-control search-input" 
                               type="search" 
                               name="search" 
                               placeholder="Tìm kiếm sản phẩm..."
                               value="{{ request('search') }}">
                        <button class="btn search-btn" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                
                @guest
                    <a href="{{ route('login') }}" class="btn btn-outline-dark rounded-pill px-3">
                        <i class="bi bi-person"></i> Đăng nhập
                    </a>
                @else
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" 
                           data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-4 me-2"></i>
                            <span class="d-none d-lg-inline">{{ Str::limit(Auth::user()->name, 15) }}</span>
                        </a>
                        
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            @if(Auth::user()->role == 1)
                                <li>
                                    <a class="dropdown-item text-danger fw-bold" href="{{ route('admin.dashboard') }}">
                                        <i class="bi bi-speedometer2 me-2"></i>Trang Admin
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Tài khoản</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-bag me-2"></i>Đơn hàng</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="#" 
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                @endguest

                <a href="{{ route('cart.index') }}" class="text-dark position-relative" title="Giỏ hàng">
                    <i class="bi bi-cart3 fs-4"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill cart-badge">
                        @auth
                            {{ \App\Models\Cart::where('user_id', Auth::id())->first()?->items->count() ?? 0 }}
                        @else
                            0
                        @endauth
                    </span>
                </a>
            </div>
        </div>
    </div>
</nav>