<?php

namespace Modules\Product\database\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Constant\Models\Constant;
use Modules\Logistic\Models\LogisticZone;
use Modules\Product\Models\Cart;
use Modules\Product\Models\Order;
use Modules\Product\Models\OrderGroup;
use Modules\Product\Models\OrderItem;
use Modules\Product\Models\ProductVariation;
use Modules\Product\Models\ProductVariationStock;

class OrderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (env('IS_DUMMY_DATA')) {
            // $this->createOrder([
            //     'user_id' => 79,
            //     'location_id' => 1,
            //     'shipping_address_id' => 1,
            //     'billing_address_id' => 1,
            //     'phone' => '981934574',
            //     'alternative_phone' => '981934574',
            //     'chosen_logistic_zone_id' => 1,
            //     'payment_method' => 'cash',
            // ]);

            // $this->createOrder([
            //     'user_id' => 80,
            //     'location_id' => 1,
            //     'shipping_address_id' => 1,
            //     'billing_address_id' => 1,
            //     'phone' => '981934574',
            //     'alternative_phone' => '981934574',
            //     'chosen_logistic_zone_id' => 1,
            //     'payment_method' => 'cash',
            // ]);

            // $this->createOrder([
            //     'user_id' => 81,
            //     'location_id' => 1,
            //     'shipping_address_id' => 1,
            //     'billing_address_id' => 1,
            //     'phone' => '981934574',
            //     'alternative_phone' => '981934574',
            //     'chosen_logistic_zone_id' => 1,
            //     'payment_method' => 'cash',
            // ]);

            // $this->createOrder([
            //     'user_id' => 82,
            //     'location_id' => 1,
            //     'shipping_address_id' => 1,
            //     'billing_address_id' => 1,
            //     'phone' => '981934574',
            //     'alternative_phone' => '981934574',
            //     'chosen_logistic_zone_id' => 1,
            //     'payment_method' => 'cash',
            // ]);
            $this->seedDummyOrders();

        }

        $order_status = [
            [
                'type' => 'ORDER_STATUS',
                'name' => 'order_placed',
                'value' => 'Order Placed',
                'sequence' => 0,
            ],
            [
                'type' => 'ORDER_STATUS',
                'name' => 'pending',
                'value' => 'Pending',
                'sequence' => 1,
            ],
            [
                'type' => 'ORDER_STATUS',
                'name' => 'processing',
                'value' => 'Processing',
                'sequence' => 2,
            ],
            [
                'type' => 'ORDER_STATUS',
                'name' => 'delivered',
                'value' => 'Delivered',
                'sequence' => 3,
            ],
            [
                'type' => 'ORDER_STATUS',
                'name' => 'cancelled',
                'value' => 'Cancelled',
                'sequence' => 4,
            ],
        ];

        foreach ($order_status as $key => $val) {
            Constant::create($val);
        }
    }

    protected function createOrder($request)
    {
        $userId = $request['user_id'];

        $location_id = $request['location_id'];

        $carts = Cart::with(['product_variation.product', 'product_variation.product_variation_stock'])
            ->where('user_id', $userId)
            ->where('location_id', $location_id)
            ->get();

        $carts = $carts->filter(function ($cart) {
            return $cart->product_variation !== null
                && $cart->product_variation->product !== null;
        });

        if ($carts->isEmpty()) {
            return null;
        }

        foreach ($carts as $cart) {
            $stockQty = optional(optional($cart->product_variation)->product_variation_stock)->stock_qty ?? 0;
            if ($cart->qty > $stockQty) {
                return null;
            }
        }

            // create new order group
            $orderGroup = new OrderGroup;
            $orderGroup->user_id = $userId;
            $orderGroup->shipping_address_id = $request['shipping_address_id'];
            $orderGroup->billing_address_id = $request['billing_address_id'];
            $orderGroup->location_id = $location_id;
            $orderGroup->phone_no = $request['phone'];
            $orderGroup->alternative_phone_no = $request['alternative_phone'];
            $orderGroup->sub_total_amount = getSubTotal($carts, false, '', false);
            $orderGroup->total_tax_amount = 0;
            $orderGroup->total_coupon_discount_amount = 0;
            $orderGroup->type = 'online';
            $logisticZone = LogisticZone::where('id', $request['chosen_logistic_zone_id'])->first();
            // todo::[for eCommerce] handle exceptions for standard & express
            $orderGroup->total_shipping_cost = $logisticZone->standard_delivery_charge;
            $orderGroup->total_tips_amount = $request['tips'] ?? 0;

            $orderGroup->grand_total_amount = $orderGroup->sub_total_amount + $orderGroup->total_tax_amount + $orderGroup->total_shipping_cost + $orderGroup->total_tips_amount - $orderGroup->total_coupon_discount_amount;
            $orderGroup->save();

            // order -> todo::[update version] make array for each vendor, create order in loop
            $order = new Order;
            $order->order_group_id = $orderGroup->id;
            $order->user_id = $userId;
            $order->location_id = $location_id;
            $order->total_admin_earnings = $orderGroup->grand_total_amount;
            $order->logistic_id = $logisticZone->logistic_id;
            $order->logistic_name = optional($logisticZone->logistic)->name;

            $order->shipping_cost = $orderGroup->total_shipping_cost; // todo::[update version] calculate for each vendors
            $order->tips_amount = $orderGroup->total_tips_amount; // todo::[update version] calculate for each vendors

            $order->save();

            // order items
            $total_points = 0;
            foreach ($carts as $cart) {
                $variation = $cart->product_variation;
                $product = $variation->product;
                $priceData = variationDiscountedPrice($product, $variation);
                $discounted_price = $priceData['discounted_price'];

                $orderItem = new OrderItem;
                $orderItem->order_id = $order->id;
                $orderItem->product_variation_id = $cart->product_variation_id;
                $orderItem->qty = $cart->qty;
                $orderItem->location_id = $location_id;
                $orderItem->unit_price = $discounted_price;
                $orderItem->discount_price = $priceData['discount_amount'];
                $orderItem->original_price = $priceData['original_price'];
                $orderItem->total_tax = 0;
                $orderItem->total_price = $orderItem->unit_price * $orderItem->qty;
                $orderItem->save();

                $product->total_sale_count += $orderItem->qty;

                $productVariationStock = $variation->product_variation_stock;
                if ($productVariationStock !== null) {
                    $productVariationStock->stock_qty -= $orderItem->qty;
                    $productVariationStock->save();
                }

                $product->stock_qty -= $orderItem->qty;
                $product->save();

                // category sales count
                if ($product->categories()->count() > 0) {
                    foreach ($product->categories as $category) {
                        $category->total_sale_count += $orderItem->qty;
                        $category->save();
                    }
                }
                $cart->delete();
            }

            $order->save();
            // payment gateway integration & redirection
            $orderGroup->payment_method = $request['payment_method'];
            $orderGroup->save();

            return $order;

        return null;
    }

    /**
     * Create 10 dummy orders with proper OrderGroup, Order, OrderItem using existing users and product variations with stock.
     */
    protected function seedDummyOrders(): void
    {
        $locationId = 1;
        $paymentMethods = ['cash', 'card', 'cash_on_delivery', 'stripe', 'razorpay'];
        $deliveryStatuses = ['order_placed', 'pending', 'processing', 'delivered', 'cancelled'];
        $logisticZone = LogisticZone::first();
        if (!$logisticZone) {
            return;
        }

        $users = User::role('user')->limit(15)->pluck('id')->toArray();
        if (empty($users)) {
            $users = User::whereNotNull('id')->limit(15)->pluck('id')->toArray();
        }
        if (empty($users)) {
            return;
        }

        $variations = ProductVariation::with(['product', 'product_variation_stock'])
            ->whereHas('product')
            ->whereHas('product_variation_stock', function ($q) {
                $q->where('stock_qty', '>', 0);
            })
            ->get();

        if ($variations->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 10; $i++) {
            $userId = $users[array_rand($users)];
            $items = [];
            $numItems = rand(1, 3);
            $subTotal = 0;
            $reservedQty = [];

            for ($j = 0; $j < $numItems; $j++) {
                $variation = $variations->random();
                $stock = $variation->product_variation_stock;
                $available = $stock ? $stock->stock_qty : 0;
                $alreadyReserved = $reservedQty[$variation->id] ?? 0;
                $maxQty = min($available - $alreadyReserved, 3);
                if ($maxQty < 1) {
                    continue;
                }
                $qty = rand(1, (int) $maxQty);
                $reservedQty[$variation->id] = $alreadyReserved + $qty;
                $priceData = variationDiscountedPrice($variation->product, $variation);
                $unitPrice = $priceData['discounted_price'];
                $items[] = [
                    'variation' => $variation,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $priceData['discount_amount'],
                    'original_price' => $priceData['original_price'],
                ];
                $subTotal += $unitPrice * $qty;
            }

            if (empty($items)) {
                continue;
            }

            $shippingCost = $logisticZone->standard_delivery_charge ?? 0;
            $grandTotal = $subTotal + $shippingCost;

            $orderGroup = new OrderGroup();
            $orderGroup->user_id = $userId;
            $orderGroup->shipping_address_id = 1;
            $orderGroup->billing_address_id = 1;
            $orderGroup->location_id = $locationId;
            $orderGroup->phone_no = '+1 555 ' . rand(100, 999) . ' ' . rand(1000, 9999);
            $orderGroup->alternative_phone_no = null;
            $orderGroup->sub_total_amount = $subTotal;
            $orderGroup->total_tax_amount = 0;
            $orderGroup->total_coupon_discount_amount = 0;
            $orderGroup->total_shipping_cost = $shippingCost;
            $orderGroup->total_tips_amount = 0;
            $orderGroup->grand_total_amount = $grandTotal;
            $orderGroup->payment_method = $paymentMethods[array_rand($paymentMethods)];
            $orderGroup->type = 'online';
            $orderGroup->save();

            $order = new Order();
            $order->order_group_id = $orderGroup->id;
            $order->user_id = $userId;
            $order->location_id = $locationId;
            $order->delivery_status = $deliveryStatuses[array_rand($deliveryStatuses)];
            $order->payment_status = (rand(0, 1) === 1) ? 'paid' : 'unpaid';
            $order->total_admin_earnings = $grandTotal;
            $order->logistic_id = $logisticZone->logistic_id;
            $order->logistic_name = optional($logisticZone->logistic)->name;
            $order->shipping_cost = $shippingCost;
            $order->tips_amount = 0;
            $order->save();

            foreach ($items as $item) {
                $variation = $item['variation'];
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_variation_id = $variation->id;
                $orderItem->qty = $item['qty'];
                $orderItem->location_id = $locationId;
                $orderItem->unit_price = $item['unit_price'];
                $orderItem->total_tax = 0;
                $orderItem->total_price = $item['unit_price'] * $item['qty'];
                $orderItem->discount_price = $item['discount_amount'];
                $orderItem->original_price = $item['original_price'];
                $orderItem->save();

                $product = $variation->product;
                $product->total_sale_count += $orderItem->qty;
                $product->stock_qty -= $orderItem->qty;
                $product->save();

                $stock = $variation->product_variation_stock;
                if ($stock !== null) {
                    $stock->stock_qty -= $orderItem->qty;
                    $stock->save();
                }

                if ($product->categories()->count() > 0) {
                    foreach ($product->categories as $category) {
                        $category->total_sale_count += $orderItem->qty;
                        $category->save();
                    }
                }
            }
        }
    }
}
