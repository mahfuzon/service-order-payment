<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    private function getMidtransSnap($params)
    {
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = filter_var(env('MIDTRANS_PRODUCTION'), FILTER_VALIDATE_BOOLEAN);
        \Midtrans\Config::$is3ds = filter_var(env('MIDTRANS_3DS'), FILTER_VALIDATE_BOOLEAN);

        $snap_url = \Midtrans\Snap::createTransaction($params)->redirect_url;
        return $snap_url;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $request->input('user_id');
        $orders = Order::query();

        $orders->when($userId, function ($query, $userId) {
            return $query->where('user_id', $userId);
        });

        return response()->json([
            'status' => "success",
            'data' => $orders->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = $request->input('user');
        $course = $request->input('course');

        $order = Order::create([
            'user_id' => $user['id'],
            'course_id' => $course['id']
        ]);

        $transaction_details = [
            "order_id" => $order->id.Str::random(5),
            "gross_amount" => $course['price'],
        ];

        $item_details = [
            [
                "id" => $course['id'],
                "price" => $course['price'],
                "quantity" => 1,
                "name" => $course['name'],
                "brand" => "indexa",
                "category" => "Online Course"
            ]
        ];

        $customer_details = [
            "first_name" => "Mahfuzon Akhiar",
            "email" => "Mahfuzon0@gmail.com"
        ];

        $midtransParams = [
            "transaction_details" => $transaction_details,
            "item_details" => $item_details,
            "customer_details" => $customer_details
        ];

        $midtransSnapUrl = $this->getMidtransSnap($midtransParams);

        $order->snap_url = $midtransSnapUrl;
        $order->metadata = [
            'course_id' => $course['id'],
            'course_price' => $course['price'],
            'course_name' => $course['name'],
            'course_thumbnail' => $course['thumbnail'],
            'course_level' => $course['level'],
        ];
        $order->save();

        return response()->json([
            'status' => 'success',
            'data' => $order
        ]);
        // return response()->json($order);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }
}
