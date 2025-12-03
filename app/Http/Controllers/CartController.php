<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // 1. Xem giỏ hàng
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Lấy giỏ hàng của user đang đăng nhập
        // Eager load: items -> variant -> product -> variants 
        // (Load sâu để lấy được danh sách size/màu khác của sản phẩm đó phục vụ việc đổi biến thể)
        $cart = Cart::where('user_id', Auth::id())
            ->with(['items.variant.product.variants']) 
            ->first();

        return view('cart.index', compact('cart'));
    }

    // 2. Thêm vào giỏ hàng
    public function addToCart(Request $request)
    {
        // Yêu cầu đăng nhập mới được thêm
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để mua hàng!');
        }

        $request->validate([
            'product_id' => 'required',
            'quantity' => 'required|numeric|min:1',
            'size' => 'required',
            'color' => 'required',
        ]);

        // Tìm biến thể (Variant) dựa trên Size/Màu
        $variant = ProductVariant::where('product_id', $request->product_id)
            ->where('size', $request->size)
            ->where('color', $request->color)
            ->first();

        if (!$variant) {
            return back()->with('error', 'Sản phẩm với Size/Màu này hiện không khả dụng.');
        }

        if ($variant->quantity < $request->quantity) {
            return back()->with('error', 'Sản phẩm này chỉ còn ' . $variant->quantity . ' món.');
        }

        // Tìm hoặc tạo giỏ hàng cho User
        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        // Kiểm tra xem sản phẩm này đã có trong giỏ chưa
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($cartItem) {
            // Nếu có rồi -> Cộng dồn số lượng
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            // Nếu chưa -> Tạo mới dòng trong cart_items
            CartItem::create([
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
                'quantity' => $request->quantity
            ]);
        }

        return redirect()->back()->with('success', 'Đã thêm vào giỏ hàng thành công!');
    }

    // 3. Xóa một sản phẩm khỏi giỏ
    public function remove($id)
    {
        CartItem::destroy($id);
        return redirect()->back()->with('success', 'Đã xóa sản phẩm!');
    }

    // 4. Cập nhật giỏ hàng (Số lượng, Size, Màu) bằng AJAX
    public function updateCart(Request $request)
    {
        // Validate dữ liệu gửi lên từ AJAX
        $request->validate([
            'item_id' => 'required',
            'quantity' => 'required|numeric|min:1',
            'size' => 'required',
            'color' => 'required',
        ]);

        // Tìm item trong giỏ hàng
        $cartItem = CartItem::find($request->item_id);
        if (!$cartItem) {
            return response()->json(['error' => 'Sản phẩm không tồn tại trong giỏ'], 404);
        }

        // Tìm variant mới dựa trên Size/Màu user vừa chọn
        // (Lấy product_id từ variant cũ để đảm bảo tìm đúng sản phẩm)
        $currentVariant = $cartItem->variant;
        $newVariant = ProductVariant::where('product_id', $currentVariant->product_id)
                        ->where('size', $request->size)
                        ->where('color', $request->color)
                        ->first();

        if (!$newVariant) {
            return response()->json(['error' => 'Biến thể này không tồn tại'], 404);
        }

        // Kiểm tra tồn kho của variant mới
        if ($newVariant->quantity < $request->quantity) {
            return response()->json(['error' => "Kho chỉ còn {$newVariant->quantity} sản phẩm"], 400);
        }

        // Cập nhật thông tin mới vào CartItem
        $cartItem->quantity = $request->quantity;
        $cartItem->product_variant_id = $newVariant->id;
        $cartItem->save();

        // Tính toán lại thành tiền để cập nhật giao diện ngay lập tức
        $price = $newVariant->product->price_sale ?? $newVariant->product->price;
        $itemTotal = $price * $cartItem->quantity;

        return response()->json([
            'success' => 'Cập nhật thành công',
            'item_total' => number_format($itemTotal) . 'đ'
        ]);
    }

    // 5. Xóa toàn bộ giỏ (Dọn giỏ hàng)
    public function clear()
    {
        $cart = Cart::where('user_id', Auth::id())->first();
        if($cart) {
            CartItem::where('cart_id', $cart->id)->delete();
        }
        return redirect()->back()->with('success', 'Đã xóa toàn bộ giỏ hàng!');
    }
}