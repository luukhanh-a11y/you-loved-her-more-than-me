<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

// Import các Models
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\ProductVariant;

class MoMoController extends Controller
{
    private $endpoint;
    private $partnerCode;
    private $accessKey;
    private $secretKey;
    private $redirectUrl;
    private $ipnUrl;

    public function __construct()
    {
        $this->endpoint = config('services.momo.endpoint');
        $this->partnerCode = config('services.momo.partner_code');
        $this->accessKey = config('services.momo.access_key');
        $this->secretKey = config('services.momo.secret_key');
        $this->redirectUrl = config('services.momo.redirect_url');
        $this->ipnUrl = config('services.momo.ipn_url');
    }

    // 1. SHOW CHECKOUT PAGE
    public function showCheckout(Request $request)
    {
        // Nhận danh sách ID các món hàng từ trang Giỏ hàng
        $selectedIdsRaw = $request->input('selected_items'); 
        if (!$selectedIdsRaw) {
            return redirect()->route('cart.index')->with('error', 'Vui lòng chọn sản phẩm để thanh toán');
        }

        $selectedIds = explode(',', $selectedIdsRaw);

        // Lấy chi tiết sản phẩm từ DB
        $selectedItems = CartItem::whereIn('id', $selectedIds)
            ->whereHas('cart', function($q) {
                $q->where('user_id', Auth::id());
            })
            ->with('variant.product')
            ->get();

        if ($selectedItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng lỗi.');
        }

        // Tính tiền
        $subtotal = 0;
        foreach ($selectedItems as $item) {
            $price = $item->variant->product->price_sale ?? $item->variant->product->price;
            $subtotal += $price * $item->quantity;
        }
        
        $shippingFee = 30000; 
        $total = $subtotal + $shippingFee;

        // Lưu ID vào session để dùng ở bước sau (tránh user sửa HTML form)
        Session::put('checkout_selected_ids', $selectedIds);

        return view('payment.checkout', compact('selectedItems', 'subtotal', 'shippingFee', 'total'));
    }

    // 2. CREATE PAYMENT & ORDER
    public function createPayment(Request $request)
    {
        $request->validate([
            'user_name' => 'required',
            'user_phone' => 'required',
            'user_email' => 'required|email',
            'user_address' => 'required',
        ]);

        // Lấy items từ session
        $selectedIds = Session::get('checkout_selected_ids');
        if (!$selectedIds) return redirect()->route('cart.index');
        
        $cartItems = CartItem::whereIn('id', $selectedIds)->with('variant.product')->get();
        
        // Tính lại tổng tiền (Bảo mật: Tính phía server)
        $subtotal = 0;
        foreach ($cartItems as $item) {
            // Check kho
            if ($item->variant->quantity < $item->quantity) {
                return back()->with('error', 'Hết hàng: ' . $item->variant->product->name);
            }
            $price = $item->variant->product->price_sale ?? $item->variant->product->price;
            $subtotal += $price * $item->quantity;
        }
        
        $shippingFee = 30000;
        $totalPrice = $subtotal + $shippingFee;

        // Tạo mã đơn hàng duy nhất cho MoMo
        $orderCode = 'ORD_' . time() . '_' . Auth::id(); 
        $requestId = time() . '_' . uniqid();
        $amount = (string)$totalPrice;
        $orderInfo = "Thanh toan don hang " . $orderCode;
        
        DB::beginTransaction();
        try {
            // A. Tạo Order (Khớp với Model của bạn)
            $order = Order::create([
                'user_id' => Auth::id(),
                'user_name' => $request->user_name,
                'user_email' => $request->user_email,
                'user_phone' => $request->user_phone,
                'user_address' => $request->user_address,
                'user_note' => $request->user_note,
                'is_ship_user_same_user' => true,
                
                'status_order' => 'pending',
                'status_payment' => 'unpaid',
                'total_price' => $totalPrice,
                
                // Các trường MoMo
                'order_code' => $orderCode,
                'request_id' => $requestId
            ]);

            // B. Tạo Order Items
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item->variant->id,
                    'product_name' => $item->variant->product->name,
                    'product_sku' => $item->variant->sku ?? 'SKU-'.$item->variant->id,
                    'product_img_thumbnail' => $item->variant->product->img_thumbnail,
                    'product_price' => $item->variant->product->price_sale ?? $item->variant->product->price,
                    'variant_size_name' => $item->variant->size,
                    'variant_color_name' => $item->variant->color,
                    'quantity' => $item->quantity
                ]);
            }

            DB::commit();

            // C. Gửi request sang MoMo API
            $extraData = ""; 
            $rawHash = "accessKey=" . $this->accessKey .
                    "&amount=" . $amount .
                    "&extraData=" . $extraData .
                    "&ipnUrl=" . $this->ipnUrl .
                    "&orderId=" . $orderCode . 
                    "&orderInfo=" . $orderInfo .
                    "&partnerCode=" . $this->partnerCode .
                    "&redirectUrl=" . $this->redirectUrl .
                    "&requestId=" . $requestId .
                    "&requestType=captureWallet";

            $signature = hash_hmac("sha256", $rawHash, $this->secretKey);
            
            $data = [
                'partnerCode' => $this->partnerCode,
                'partnerName' => 'Laravel Store',
                'storeId' => 'MomoStore',
                'requestId' => $requestId,
                'amount' => $amount,
                'orderId' => $orderCode,
                'orderInfo' => $orderInfo,
                'redirectUrl' => $this->redirectUrl,
                'ipnUrl' => $this->ipnUrl,
                'lang' => 'vi',
                'extraData' => $extraData,
                'requestType' => 'captureWallet',
                'signature' => $signature
            ];

            $result = $this->execPostRequest($this->endpoint, json_encode($data));
            $jsonResult = json_decode($result, true);

            if (isset($jsonResult['payUrl'])) {
                return redirect($jsonResult['payUrl']);
            }
            
            // Nếu MoMo lỗi
            return back()->with('error', 'Lỗi MoMo: ' . ($jsonResult['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // 3. MOMO CALLBACK (Xử lý khi user thanh toán xong và quay lại)
    public function callback(Request $request)
    {
        $orderCode = $request->orderId;
        $resultCode = $request->resultCode;
        
        // Tìm đơn hàng theo mã order_code
        $order = Order::where('order_code', $orderCode)->first();
        
        if (!$order) {
            return redirect('/')->with('error', 'Không tìm thấy đơn hàng');
        }

        if ($resultCode == 0) { // Thanh toán thành công
            DB::beginTransaction();
            try {
                // Update Order
                $order->update([
                    'status_payment' => 'paid',
                    'status_order' => 'processing', // Chờ xử lý
                    'trans_id' => $request->transId,
                    'pay_type' => $request->payType
                ]);

                // Xử lý kho và giỏ hàng
                $orderItems = $order->orderItems;
                foreach ($orderItems as $orderItem) {
                    // Trừ kho
                    $variant = ProductVariant::find($orderItem->product_variant_id);
                    if($variant) {
                        $variant->decrement('quantity', $orderItem->quantity);
                    }
                    
                    // Xóa item này khỏi giỏ hàng của user
                    CartItem::whereHas('cart', function($q) use ($order) {
                        $q->where('user_id', $order->user_id);
                    })->where('product_variant_id', $orderItem->product_variant_id)
                      ->delete();
                }

                DB::commit();
                Session::forget('checkout_selected_ids'); // Xóa session checkout
                
                return view('payment.success', compact('order'));

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error($e);
                return view('payment.failed', compact('order'))->with('error', 'Đã thanh toán nhưng lỗi cập nhật hệ thống. Vui lòng liên hệ Admin.');
            }
        } else {
            // Thanh toán thất bại hoặc hủy
            $order->update([
                'status_payment' => 'failed',
                'status_order' => 'cancelled'
            ]);
            return view('payment.failed', compact('order'));
        }
    }

    // Helper: Gửi Request Curl
    private function execPostRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}