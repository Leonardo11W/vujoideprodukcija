<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Product\Models\Product;

class ProductController extends Controller
{
    public function getProducts(Request $request)
    {
        $products = Product::select('id', 'name')->get();

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }
}
