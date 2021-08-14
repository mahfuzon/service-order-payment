<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function midtransHandler(Request $request)
    {
        $data = $request->all();
        $signatureKey = $data['signature_key'];
        $orderId = $data['order_id'];
        $statusCode = $data['status_code'];
        $grossAmount = $data['gross_amount'];
        $serverKey = env('MIDTRANS_SERVER_KEY');

        $mySignatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $transactionStatus = $data['transaction_status'];
        $type = $data['payment_type'];
        $fraudStatus = $data['fraud_status'];

        if ($signatureKey !== $mySignatureKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'invalid signature'
            ], 400);
        }

        $realOrderId = explode('-', $orderId);

        $order = Order::find($realOrderId[0]);


        if (!$order) {
            return response()->json([
                "status" => "error",
                "message" => "order id not found"
            ], 404);
        }

        if ($order->status === 'success') {
            return response()->json([
                'status' => 'error',
                'message' => 'operation not permitted'
            ], 405);
        }

        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                // TODO set transaction status on your database to 'challenge'
                $order->status = 'challenge';
                // and response with 200 OK
            } else if ($fraudStatus == 'accept') {
                // TODO set transaction status on your database to 'success'
                $order->status = 'success';
                // and response with 200 OK
            }
        } else if ($transactionStatus == 'settlement') {
            // TODO set transaction status on your database to 'success'
            $order->status = 'success';
            // and response with 200 OK
        } else if (
            $transactionStatus == 'cancel' ||
            $transactionStatus == 'deny' ||
            $transactionStatus == 'expire'
        ) {
            // TODO set transaction status on your database to 'failure'
            $order->status = 'failure';
            // and response with 200 OK
        } else if ($transactionStatus == 'pending') {
            // TODO set transaction status on your database to 'pending' / waiting payment
            $order->status = 'pending';
            // and response with 200 OK
        }



        $logData = [
            'status' => $transactionStatus,
            'raw_response' => json_encode($data),
            'order_id' => $realOrderId[0],
            'payment_type' => $type
        ];


        Payment::create($logData);

        $order->save();

        if ($order->status === 'success') {
            createPremiumAccess([
                'user_id' => $order->user_id,
                'course_id' => $order->course_id
            ]);
        }

        return response()->json('ok');
    }
}
