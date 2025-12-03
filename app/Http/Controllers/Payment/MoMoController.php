<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

    /**
     * Hiển thị trang checkout
     */
    public function showCheckout()
    {
        return view('payment.checkout');
    }

    /**
     * Tạo thanh toán MoMo
     */
    public function createPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'order_info' => 'required|string|max:255',
        ]);

        $orderId = time() . '_' . Auth::id();
        $requestId = time() . '_' . uniqid();
        $amount = (string) $request->amount;
        $orderInfo = $request->order_info;
        $extraData = '';
        $requestType = 'captureWallet';

        // Tạo signature
        $rawHash = "accessKey=" . $this->accessKey .
                   "&amount=" . $amount .
                   "&extraData=" . $extraData .
                   "&ipnUrl=" . $this->ipnUrl .
                   "&orderId=" . $orderId .
                   "&orderInfo=" . $orderInfo .
                   "&partnerCode=" . $this->partnerCode .
                   "&redirectUrl=" . $this->redirectUrl .
                   "&requestId=" . $requestId .
                   "&requestType=" . $requestType;

        $signature = hash_hmac("sha256", $rawHash, $this->secretKey);

        // Data gửi đến MoMo
        $data = [
            'partnerCode' => $this->partnerCode,
            'partnerName' => 'SOLID TECH',
            'storeId' => 'SolidTechStore',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $this->redirectUrl,
            'ipnUrl' => $this->ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature,
        ];

        // Lưu order vào database
        Order::create([
            'user_id' => Auth::id(),
            'order_id' => $orderId,
            'request_id' => $requestId,
            'amount' => $amount,
            'order_info' => $orderInfo,
            'status' => 'pending',
        ]);

        // Gửi request đến MoMo
        $result = $this->execPostRequest($this->endpoint, json_encode($data));
        $jsonResult = json_decode($result, true);

        Log::info('MoMo Create Payment Response:', $jsonResult);

        if (isset($jsonResult['payUrl'])) {
            return redirect($jsonResult['payUrl']);
        }

        return back()->with('error', 'Không thể tạo thanh toán. Vui lòng thử lại!');
    }

    /**
     * Xử lý callback từ MoMo (user redirect về)
     */
    public function callback(Request $request)
    {
        Log::info('MoMo Callback Data:', $request->all());

        $orderId = $request->orderId;
        $resultCode = $request->resultCode;

        $order = Order::where('order_id', $orderId)->first();

        if (!$order) {
            return redirect('/')->with('error', 'Không tìm thấy đơn hàng!');
        }

        if ($resultCode == 0) {
            // Thanh toán thành công
            $order->update([
                'status' => 'completed',
                'trans_id' => $request->transId,
                'response_data' => $request->all(),
            ]);

            return view('payment.success', compact('order'));
        } else {
            // Thanh toán thất bại
            $order->update([
                'status' => 'failed',
                'response_data' => $request->all(),
            ]);

            return view('payment.failed', compact('order'));
        }
    }

    /**
     * Xử lý IPN từ MoMo (webhook)
     */
    public function ipn(Request $request)
    {
        Log::info('MoMo IPN Data:', $request->all());

        $orderId = $request->orderId;
        $resultCode = $request->resultCode;

        $order = Order::where('order_id', $orderId)->first();

        if ($order) {
            if ($resultCode == 0) {
                $order->update([
                    'status' => 'completed',
                    'trans_id' => $request->transId,
                    'response_data' => $request->all(),
                ]);
            } else {
                $order->update([
                    'status' => 'failed',
                    'response_data' => $request->all(),
                ]);
            }
        }

        return response()->json(['message' => 'IPN received'], 200);
    }

    /**
     * Kiểm tra trạng thái đơn hàng
     */
    public function checkStatus($orderId)
    {
        $order = Order::where('order_id', $orderId)
                     ->where('user_id', Auth::id())
                     ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    /**
     * Gửi POST request với cURL
     */
    private function execPostRequest($url, $data)
    {
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Tắt SSL verify cho local

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}