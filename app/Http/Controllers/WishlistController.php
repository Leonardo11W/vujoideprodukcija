<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Product\Models\WishList;
use Modules\Product\Models\Product;
use Yajra\DataTables\DataTables;

class WishlistController extends Controller
{
    public function add(Request $request)
    {

        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Not authenticated'], 401);
        }

        $productId = $request->input('product_id');
        if (!$productId) {
            return response()->json(['status' => false, 'message' => 'Product ID required']);
        }

        $wishlist = WishList::where('user_id', $user->id)->where('product_id', $productId)->first();

        if ($wishlist) {

            $wishlist->delete();
            $response = ['status' => true, 'message' => 'Removed from wishlist', 'action' => 'removed'];
        } else {

            WishList::create(['user_id' => $user->id, 'product_id' => $productId]);
            $response = ['status' => true, 'message' => 'Added to wishlist', 'action' => 'added'];
        }

        return response()->json($response);
    }

    public function remove(Request $request)
    {


        $user = Auth::user();
        if (!$user) {

            return response()->json(['status' => false, 'message' => 'Not authenticated'], 401);
        }

        $productId = $request->input('product_id');
        if (!$productId) {

            return response()->json(['status' => false, 'message' => 'Product ID required']);
        }


        $deleted = WishList::where('user_id', $user->id)->where('product_id', $productId)->delete();


        $response = ['status' => true, 'message' => 'Removed from wishlist'];


        return response()->json($response);
    }

    public function wishlistData(Request $request)
    {
        try {
            if (!auth()->check()) {

                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $userId = auth()->id();
            $query = WishList::where('user_id', $userId)->with('product');

            return DataTables::of($query)
                ->addColumn('remove', function ($item) {
                    return '<button type="button" class="btn btn-link border-0 p-0 icon-color remove-from-wishlist-btn" data-product-id="' . $item->product_id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="' . __('frontend.remove') . '">'
                        . '<i class="ph ph-trash font-size-18"></i>'
                        . '</button>';
                })
                ->addColumn('product_name', function ($item) {
                    $product = $item->product;
                    if (!$product) return '<span class="text-danger">' . __('frontend.product_not_found') . '</span>';
                    $img = $product->media->first() ? $product->media->first()->getFullUrl() : asset('img/frontend/product.png');
                    $price = \Currency::format($product->max_price);
                    $discount = $product->discount_value > 0
                        ? '<del class="font-size-18">' . $price . '</del> <span class="text-primary font-size-18">' . \Currency::format($product->discount_type === "percent" ? $product->max_price - ($product->max_price * $product->discount_value / 100) : $product->max_price - $product->discount_value) . '</span>'
                        : '<span class="font-size-18">' . $price . '</span>';
                    return '<div class="d-flex align-items-center gap-3 flex-wrap flex-md-nowrap">
                        <div class="bg-gray-900 avatar avatar-70 rounded">
                            <img src="' . $img . '" alt="' . $product->name . '" class="avatar avatar-70 object-fixt-cover">
                        </div>
                        <div>
                            <p class="mb-2">' . $product->name . '</p>
                            <div class="d-flex align-items-center gap-2">' . $discount . '</div>
                        </div>
                    </div>';
                })
                ->addColumn('actions', function ($item) {
                    $product = $item->product;
                    if (!$product) return '';
                    $inCart = $product->isInCart();
                    $addBtnStyle = $inCart ? 'display: none;' : '';
                    $removeBtnStyle = !$inCart ? 'display: none;' : '';
                    return '
                        <button id="addToCartBtn_' . $product->id . '" class="btn btn-secondary add-to-cart" onclick="addToCart(' . $product->id . ')" style="' . $addBtnStyle . '">
                            ' . __('frontend.add_to_cart') . '
                        </button>
                        <button id="removeFromCartBtn_' . $product->id . '" class="btn btn-danger remove-from-cart" onclick="removeFromCart(' . $product->id . ')" style="' . $removeBtnStyle . '">
                            ' . __('frontend.remove_from_cart') . '
                        </button>';
                })
                ->filterColumn('product_name', function ($query, $keyword) {
                    $query->whereHas('product', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['remove', 'product_name', 'actions'])
                ->make(true);
        } catch (\Exception $e) {

            return response()->json(['message' => 'Server error'], 500);
        }
    }
}
