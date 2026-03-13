<?php

namespace Modules\Product\Http\Controllers\Backend;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Location\Models\Location;
use Modules\Product\Models\Cart;
use Modules\Product\Models\Order;
use Modules\Product\Models\OrderGroup;
use Modules\Product\Models\OrderItem;
use Modules\Product\Models\OrderUpdate;
use Modules\Product\Trait\OrderTrait;
use Yajra\DataTables\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Modules\Tax\Models\Tax;

class OrdersController extends Controller
{
    use OrderTrait;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'orders.title';
        // module name
        $this->module_name = 'orders';

        // module icon
        $this->module_icon = 'fa-solid fa-clipboard-list';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => $this->module_icon,
            'module_name' => $this->module_name,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Renderable
     */
    public function index(Request $request)
    {
        $export_import = false;

        $locations = Location::where('status', 1)->latest()->get();

        return view('product::backend.order.index_datatable', compact('export_import', 'locations'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {

        $orders = Order::with('orderGroup', 'user', 'location');

        if ($request->has('filter')) {
            $filter = $request->filter;

            if (isset($filter['delivery_status']) && $filter['delivery_status']) {
                $orders->where('delivery_status', $filter['delivery_status']);
            }

            if (isset($filter['payment_status']) && $filter['payment_status']) {
                $orders->where('payment_status', $filter['payment_status']);
            }

            if (isset($filter['location_id']) && $filter['location_id']) {
                $orders->where('location_id', $filter['location_id']);
            }

            if (isset($filter['code']) && $filter['code']) {
                $code = $filter['code'];
                $orders->where(function($q) use ($code) {
                    $q->where('id', 'like', '%' . $code . '%')
                      ->orWhere('applied_coupon_code', 'like', '%' . $code . '%')
                      ->orWhereHas('orderGroup', function($q2) use ($code) {
                          $q2->where('order_code', 'like', '%' . $code . '%');
                      });
                });
            }
        }
        // Removed order_group_id filter to fetch all orders
        return $datatable->eloquent($orders)
        ->addColumn('check', function ($row) {

            $user = auth()->user();

            // Permissions that allow bulk actions
            $hasActionPermission =
                $user->can('edit_orders') ||
                $user->can('delete_orders') ||
                $user->can('status_orders');

            // If NO permission → return empty (no checkbox)
            if (!$hasActionPermission) {
                return '';
            }

            return '<input
                type="checkbox"
                class="form-check-input select-table-row"
                id="datatable-row-' . $row->id . '"
                name="datatable_ids[]"
                value="' . $row->id . '"
                onclick="dataTableRowCheck(' . $row->id . ')"
            >';
        })
            ->addColumn('action', function ($data) {
                return view('product::backend.order.columns.action_column', compact('data'));
            })
            ->editColumn('order_code', function ($data) {
                if ($data->orderGroup && $data->orderGroup->order_code) {
                    return $data->orderGroup->formatted_order_code;
                }
                $prefix = setting('inv_prefix') ?? '';
                return $prefix . $data->id; // fallback to order ID
            })
            ->editColumn('customer_name', function ($data) {
                $id = $data->user_id;
                $user = optional($data->user);
                $Profile_image = $user->profile_image ?? default_user_avatar();
                $name = $user->full_name ?? default_user_name();
                $email = $user->email ?? '--';
                return view('booking::backend.bookings.datatable.user_id', compact('Profile_image', 'name', 'email','id'));
            })
            ->addColumn('phone', function ($data) {
                return optional($data->user)->mobile ?? '-';
            })
            ->editColumn('placed_on', function ($data) {
                return customDate($data->created_at);
            })
            ->editColumn('items', function ($data) {
                // Show total number of products purchased in the order
                return $data->orderItems ? $data->orderItems->sum('quantity') : 0;
            })
            ->editColumn('type', function ($data) {
                return view('product::backend.order.columns.type_column', compact('data'));
            })
            ->editColumn('payment', function ($data) {
                return view('product::backend.order.columns.payment_column', compact('data'));
            })
            ->editColumn('status', function ($data) {
                return view('product::backend.order.columns.status_column', compact('data'));
            })
            ->editColumn('location', function ($data) {
                return $data->location ? $data->location->name : 'N/A';
            })
            ->filterColumn('customer_name', function ($query, $keyword) {
                if (! empty($keyword)) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('first_name', 'like', '%' . $keyword . '%');
                        $q->orWhere('last_name', 'like', '%' . $keyword . '%');
                        $q->orWhere('email', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->filterColumn('phone', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('mobile', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->editColumn('updated_at', function ($data) {
                $diff = Carbon::now()->diffInHours($data->updated_at);
                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('llll');
                }
            })
            ->orderColumns(['id'], '-:column $1')
            ->rawColumns(['action', 'check', 'phone'])
            ->toJson();
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function show(Request $request)
    {
        $order = Order::with([
            'orderGroup',
            'orderItems.product_variation.product',
            'orderGroup.shippingAddress',
            'orderGroup.billingAddress',
            'orderUpdates.user',
            'user',
            'location'
        ])->find($request->id);
        if ($order == null) {
            return abort(500);
        }
        $address = null;
        if ($order->orderGroup && $order->orderGroup->shipping_address_id) {
            $address = \App\Models\Address::find($order->orderGroup->shipping_address_id);
        }
        if (!$address && $order->user && $order->user->addresses) {
            $address = $order->user->addresses->first();
        }
        return view('product::backend.order.show', compact('order', 'address'));
    }

    // update payment status
    public function updatePaymentStatus(Request $request)
    {
        // Check if user has permission to update order status
        if (!auth()->user()->can('status_orders') && !auth()->user()->can('edit_orders')) {
            return response()->json(['status' => false, 'message' => __('messages.permission_denied')], 403);
        }
        
        $order = Order::findOrFail((int) $request->order_id);
        $order->payment_status = $request->status;
        $order->save();

        OrderUpdate::create([
            'order_id' => $order->id,
            'user_id' => auth()->user()->id,
            'note' => 'Payment status updated to ' . ucwords(str_replace('_', ' ', $request->status)) . '.',
        ]);

        // todo::['mail notification']
        return response()->json(['status' => true, 'message' => 'Payment Status Has Been Updated']);
    }

    // update delivery status
    public function updateDeliveryStatus(Request $request)
    {
        // Check if user has permission to update order status
        if (!auth()->user()->can('status_orders') && !auth()->user()->can('edit_orders')) {
            return response()->json(['status' => false, 'message' => __('messages.permission_denied')], 403);
        }
        
        $order = Order::findOrFail((int) $request->order_id);

        if ($order->delivery_status != 'cancelled' && $request->status == 'cancelled') {
            $this->addQtyToStock($order);
        }

        if ($order->delivery_status == 'cancelled' && $request->status != 'cancelled') {
            $this->removeQtyFromStock($order);
        }

        $order->delivery_status = $request->status;
        $order->save();

        // Remove future status updates if status is moved backwards
        $statusHierarchy = [
            'order_placed' => 0,
            'pending' => 1,
            'processing' => 2,
            'delivered' => 3,
            'cancelled' => 4,
        ];

        $newStatusRank = $statusHierarchy[$request->status] ?? 0;

        $futureStatuses = array_filter($statusHierarchy, function ($rank) use ($newStatusRank) {
            return $rank >= $newStatusRank;
        });

        foreach (array_keys($futureStatuses) as $statusName) {
            OrderUpdate::where('order_id', $order->id)
                ->where('note', 'like', '%' . ucwords(str_replace('_', ' ', $statusName)) . '%')
                ->delete();
        }

        OrderUpdate::create([
            'order_id' => $order->id,
            'user_id' => auth()->user()->id,
            'note' => 'Delivery status updated to ' . ucwords(str_replace('_', ' ', $request->status)) . '.',
        ]);

        $notify_type = null;

        $status = $request->status;

        switch ($status) {
            case 'processing':
                $notify_type = 'order_proccessing';
                $messageTemplate = 'Order #[[order_id]] is now being processed.';
                $notify_message = str_replace('[[order_id]]', $order->id, $messageTemplate);
                break;
            case 'delivered':
                $notify_type = 'order_delivered';
                $messageTemplate = 'Order #[[order_id]] has been delivered.';
                $notify_message = str_replace('[[order_id]]', $order->id, $messageTemplate);
                break;
            case 'cancelled':
                $notify_type = 'order_cancelled';
                $messageTemplate = 'Order #[[order_id]] has been cancelled.';
                $notify_message = str_replace('[[order_id]]', $order->id, $messageTemplate);
                break;
        }

        try {
            $notification_data = [

                'id' => $order->id,
                'order_code' => optional($order->orderGroup)->formatted_order_code,
                'user_id' => $order->user_id,
                'user_name' => optional($order->user)->first_name . ' ' . optional($order->user)->last_name ?? default_user_name(),
                'order_date' => $order->updated_at->format('d/m/Y'),
                'order_time' => $order->updated_at->format('h:i A'),
            ];

            $this->sendNotificationOnOrderUpdate($notify_type, $notify_message, $notification_data);
        } catch (\Exception $e) {
        }

        // todo::['mail notification']
        return response()->json(['status' => true, 'message' => 'Order delivery status has been updated']);
    }

    // add qty to stock
    private function addQtyToStock($order)
    {
        $orderItems = OrderItem::where('order_id', $order->id)->get();
        foreach ($orderItems as $orderItem) {
            $stock = $orderItem->product_variation->product_variation_stock;
            $stock->stock_qty += $orderItem->qty;
            $stock->save();

            $product = $orderItem->product_variation->product;
            $product->total_sale_count += $orderItem->qty;
            $product->save();

            if ($product->categories()->count() > 0) {
                foreach ($product->categories as $category) {
                    $category->total_sale_count += $orderItem->qty;
                    $category->save();
                }
            }
        }
    }

    // remove qty from stock
    private function removeQtyFromStock($order)
    {
        $orderItems = OrderItem::where('order_id', $order->id)->get();
        foreach ($orderItems as $orderItem) {
            $stock = $orderItem->product_variation->product_variation_stock;
            $stock->stock_qty -= $orderItem->qty;
            $stock->save();

            $product = $orderItem->product_variation->product;
            $product->total_sale_count -= $orderItem->qty;
            $product->save();

            if ($product->categories()->count() > 0) {
                foreach ($product->categories as $category) {
                    $category->total_sale_count -= $orderItem->qty;
                    $category->save();
                }
            }
        }
    }

    // Order Creation
    public function complete(Request $request)
    {
        $user = auth()->user();

        $userId = $user->id;

        $location_id = $request->location_id;

        $carts = Cart::where('user_id', $userId)->where('location_id', $location_id)->get();

        if (count($carts) > 0) {
            // check carts available stock -- todo::[update version] -> run this check while storing OrderItems
            foreach ($carts as $cart) {
                $productVariationStock = $cart->product_variation->product_variation_stock ? $cart->product_variation->product_variation_stock->stock_qty : 0;
                if ($cart->qty > $productVariationStock) {
                    $message = $cart->product_variation->product->name . ' is out of stock';

                    return response()->json(['message' => $message, 'status' => false]);
                }
            }

            // create new order group
            $orderGroup = new OrderGroup;
            $orderGroup->user_id = $userId;
            $orderGroup->shipping_address_id = $request->shipping_address_id;
            $orderGroup->billing_address_id = $request->billing_address_id;
            $orderGroup->location_id = $location_id;
            $orderGroup->phone_no = $request->phone;
            $orderGroup->alternative_phone_no = $request->alternative_phone;
            $orderGroup->sub_total_amount = getSubTotal($carts, false, '', false);
            $orderGroup->total_tax_amount = 0;
            $orderGroup->total_coupon_discount_amount = 0;
            $orderGroup->type = 'online';
            $logisticZone = LogisticZone::where('id', $request->chosen_logistic_zone_id)->first();
            // todo::[for eCommerce] handle exceptions for standard & express
            $orderGroup->total_shipping_cost = $logisticZone->standard_delivery_charge;
            $orderGroup->total_tips_amount = $request->tips;

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
                $priceData = variationDiscountedPrice($cart->product_variation->product, $cart->product_variation);
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

                $product = null;
                if (isset($orderItem->product_id)) {
                    $product = \Modules\Product\Models\Product::find($orderItem->product_id);
                }
                if (!$product && isset($orderItem->product_variation_id)) {
                    $variation = \Modules\Product\Models\ProductVariation::find($orderItem->product_variation_id);
                    $product = $variation ? $variation->product : null;
                }
                if ($product) {
                    $old_sale = $product->total_sale_count;
                    $old_stock = $product->stock_qty;
                    $product->increment('total_sale_count', $orderItem->qty);
                    $product->decrement('stock_qty', $orderItem->qty);
                    $product->save();
                }

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
            $orderGroup->payment_method = $request->payment_method;
            $orderGroup->save();

            return true;
        }
    }

    // download invoice

    public function downloadInvoice($id)
    {
        if (session()->has('locale')) {
            $language_code = session()->get('locale', config('app.locale'));
        } else {
            $language_code = env('DEFAULT_LANGUAGE');
        }

        $font_family = "'Roboto','sans-serif'";

        $order = Order::with(['orderItems.product_variation.product', 'orderGroup'])->findOrFail((int) $id);
        $app_name = Setting::where('name', 'app_name')->value('val');
        $inquriy_email = Setting::where('name', 'inquriy_email')->value('val');
        $helpline_number = Setting::where('name', 'helpline_number')->value('val');

        $taxDetails = json_decode($order->orderGroup->taxes);
        // $imagePath = asset('public/img/logo/logo.png');
        $view = view('product::backend.order.invoice', [
            'order' => $order,
            'font_family' => $font_family,
            'app_name' => $app_name,
            'inquriy_email' => $inquriy_email,
            'helpline_number' => $helpline_number,
            'taxDetails' => $taxDetails,

        ])->render();
        // dd($imagePath);
        $pdf = Pdf::loadHTML($view);
        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->output();
            },
            "invoice.pdf",
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="invoice.pdf"',
            ]
        );
    }


    public function OrderInvoicedownload(Request $request)
    {
        // Use the same invoice design as the frontend `ProductController::downloadInvoice`
        $order = Order::with(['orderItems.product_variation.product', 'orderGroup', 'user.addresses'])
            ->findOrFail((int) ($request->id));

        // Resolve address exactly like frontend
        $address = null;
        if ($order->orderGroup && $order->orderGroup->shipping_address_id) {
            $address = \App\Models\Address::find($order->orderGroup->shipping_address_id);
        }

        if (!$address && $order->user && $order->user->addresses) {
            $address = $order->user->addresses->first();
        }

        // Load logo the same way as frontend
        $logo = null;
        $logoSetting = setting('logo');

        if ($logoSetting) {
            $logoPath = public_path(parse_url($logoSetting, PHP_URL_PATH) ?? $logoSetting);

            if (file_exists($logoPath) && is_readable($logoPath)) {
                try {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $logoPath);
                    finfo_close($finfo);

                    $extensions = [
                        'image/jpeg' => 'jpeg',
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'image/svg+xml' => 'svg+xml',
                        'image/webp' => 'webp',
                    ];

                    $extension = $extensions[$mimeType] ?? 'jpeg';

                    $logoData = file_get_contents($logoPath);
                    $logo = 'data:image/' . $extension . ';base64,' . base64_encode($logoData);
                } catch (\Exception $e) {
                    \Log::error('Logo processing error (API invoice): ' . $e->getMessage());
                }
            }
        }

        // Fallback logo if none from settings
        if (!$logo) {
            $defaultLogoPath = public_path('img/logo/logo.png');
            if (file_exists($defaultLogoPath)) {
                try {
                    $logoData = file_get_contents($defaultLogoPath);
                    $logo = 'data:image/png;base64,' . base64_encode($logoData);
                } catch (\Exception $e) {
                    \Log::error('Default logo processing error (API invoice): ' . $e->getMessage());
                }
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoice', [
            'order' => $order,
            'address' => $address,
            'logo' => $logo,
        ]);

        $pdf->setPaper('a4')
            ->setOptions([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        if ($request->is('api/*')) {
            // Handle API request
            $baseDirectory = storage_path('app/public');
            $highestDirectory = collect(File::directories($baseDirectory))->map(function ($directory) {
                return basename($directory);
            })->max() ?? 0;
            $nextDirectory = intval($highestDirectory) + 1;
            while (File::exists($baseDirectory . '/' . $nextDirectory)) {
                $nextDirectory++;
            }
            $newDirectory = $baseDirectory . '/' . $nextDirectory;
            File::makeDirectory($newDirectory, 0777, true);

            $filename = 'invoice_' . $order->id . '.pdf';
            $filePath = $newDirectory . '/' . $filename;

            $pdf->save($filePath);

            $url = url('storage/' . $nextDirectory . '/' . $filename);
            if (!empty($url)) {
                return response()->json(['status' => true, 'link' => $url], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'Url Not Found'], 404);
            }
        } else {
            // Handle non-API request
            return response()->streamDownload(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                "invoice_{$order->id}.pdf",
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="invoice_' . $order->id . '.pdf"',
                ]
            );
        }
    }
}
