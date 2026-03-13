<?php

namespace Modules\Location\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Modules\Location\Models\Location;
use Modules\Location\Models\StockLog;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariationStock;
use Modules\Product\Models\Variations;
use Modules\Product\Models\VariationValue;

class StocksController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return Renderable
     */
    public function create()
    {
        $products = Product::latest()->where('status', 1)->get();
        $locations = Location::latest()->where('status', 1)->get();
        $pr = [];
        foreach ($products as $key => $value) {
            $pr[] = [
                'value' => $value->id,
                'label' => $value->name,
            ];
        }
        $products = $pr;

        $lo = [];
        foreach ($locations as $key => $value) {
            $lo[] = [
                'value' => $value->id,
                'label' => $value->name,
            ];
        }
        $locations = $lo;
        $module_title = 'product.add_stock';

        return view('location::backend.stocks.create', compact('products', 'locations', 'module_title'));
    }

    public function getVariationStocks(Request $request)
    {
        //
        $product = Product::findOrFail((int) $request->product_id);
        $location_id = $request->location_id;
        $stokeData = [];
        if ($product->has_variation) {
            foreach ($product->product_variations as $key => $variation) {
                $code_array = array_filter(explode('/', $variation->variation_key));
                $lstKey = array_key_last($code_array);
                $name = '';
                foreach ($code_array as $key2 => $comb) {
                    $comb = explode(':', $comb);

                    $option_name = Variations::find($comb[0])->name;
                    $choice_name = VariationValue::find($comb[1])->name;

                    $name .= $choice_name;

                    if ($lstKey != $key2) {
                        $name .= '-';
                    }
                }
                $stock = $variation->product_variation_stock()
                    ->where('location_id', $location_id)
                    ->first();
                $stokeData[] = [
                    'name' => $name,
                    'product_variation_id' => $variation->id,
                    'stock' => $stock ? $stock->stock_qty : 0,
                ];
            }
        } else {
            $first_variation = $product->product_variations->first();
            $first_variation_stock = $first_variation
                ->product_variation_stock()
                ->where('location_id', $location_id)
                ->first();

            $price = $first_variation->price;
            $stock_qty = 0;
            if ($first_variation_stock) {
                $stock_qty = $first_variation_stock->stock_qty;
            }
            $sku = $first_variation->sku;
            $stokeData = [
                'product_variation_id' => $first_variation->id,
                'stock' => $stock_qty,
            ];
        }
        $data = [
            'has_variation' => $product->has_variation,
            'brand_id' => $product->brand_id,
            'category_ids' => $product->categories->pluck('id')->toArray(),
            'data' => $stokeData,
        ];

        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Renderable
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'location_id' => 'required|integer',
        ]);

        $product = Product::findOrFail((int) $request->product_id);

        // Update Brand and Categories as requested by user
        if ($request->has('brand_id')) {
            $product->brand_id = $request->brand_id;
        }
        if ($request->has('category_id')) {
            $product->categories()->sync($request->category_id);
        }

        if ($request->product_variation_id) {
            $productVariationStock = ProductVariationStock::where('product_variation_id', $request->product_variation_id)
                ->where('location_id', $request->location_id)->first();
            if (is_null($productVariationStock)) {
                $productVariationStock = new ProductVariationStock;
                $productVariationStock->product_variation_id = $request->product_variation_id;
                $productVariationStock->location_id = $request->location_id;
                $productVariationStock->stock_qty = 0;
            }
            if ($productVariationStock->stock_qty != $request->stock) {
                $stockLog = new StockLog();
                $stockLog->location_id = $request->location_id;
                $stockLog->product_id = $request->product_id;
                $stockLog->product_variation_id = $request->product_variation_id;
                
                if ($productVariationStock->stock_qty > $request->stock) {
                    $stockLog->type = 'outward';
                    $stockLog->quantity = $productVariationStock->stock_qty - $request->stock;
                } else {
                    $stockLog->type = 'inward';
                    $stockLog->quantity = $request->stock - $productVariationStock->stock_qty;
                }
                $stockLog->save();
            }
            $productVariationStock->stock_qty = $request->stock;
            $productVariationStock->save();
        } else if ($request->has('variations')) {
            foreach ($request->variations as $key => $value) {
                $productVariationStock = ProductVariationStock::where('product_variation_id', $value['product_variation_id'])
                    ->where('location_id', $request->location_id)->first();

                if (is_null($productVariationStock)) {
                    $productVariationStock = new ProductVariationStock;
                    $productVariationStock->product_variation_id = $value['product_variation_id'];
                    $productVariationStock->location_id = $request->location_id;
                    $productVariationStock->stock_qty = 0;
                }

                if ($productVariationStock->stock_qty != $value['stock']) {
                    $stockLog = new StockLog();
                    $stockLog->location_id = $request->location_id;
                    $stockLog->product_id = $request->product_id;
                    $stockLog->product_variation_id = $value['product_variation_id'];

                    if ($productVariationStock->stock_qty > $value['stock']) {
                        $stockLog->type = 'outward';
                        $stockLog->quantity = $productVariationStock->stock_qty - $value['stock'];
                    } else {
                        $stockLog->type = 'inward';
                        $stockLog->quantity = $value['stock'] - $productVariationStock->stock_qty;
                    }
                    $stockLog->save();
                }
                $productVariationStock->stock_qty = $value['stock'];
                $productVariationStock->save();
            }
        }

        // Update total stock quantity on product
        $product->stock_qty = ProductVariationStock::whereIn('product_variation_id', $product->product_variations->pluck('id'))
            ->sum('stock_qty');
        
        $product->save();

        return response()->json(['status' => true, 'message' => __('product.stock_update')]);
    }
}
