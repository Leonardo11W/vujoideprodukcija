<?php

namespace Modules\Product\Http\Controllers\Backend\API;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductGallery;
use Modules\Product\Transformers\ProductResource;
use Modules\Product\Transformers\ProductSchemaResource;
use Modules\Product\Http\Resources\ProductDetailResource;
use Modules\Product\Http\Requests\API\AddProductBasicRequest;
use Modules\Product\Models\Brands;
use Modules\Product\Models\ProductCategory;
use Modules\Tag\Models\Tag;
use Modules\Product\Models\ProductVariation;
use Modules\Product\Models\ProductVariationStock;
use Modules\Location\Models\Location;
use Modules\Product\Http\Requests\API\AddProductInventoryRequest;
use Modules\Product\Models\Variations;
use Modules\Product\Models\VariationValue;
use Modules\Product\Models\ProductVariationCombination;
use Modules\Product\Http\Requests\API\AddProductDiscountRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProductsController extends Controller
{
    public function ProductList(Request $request)
    {
        if ($request->has('filter_type')) {
            return $this->productfilter($request);
        }

        $perPage = $request->input('per_page', 10);

        $productQuery = Product::with('media', 'categories', 'brand', 'unit', 'product_variations', 'product_review', 'gallery');

        if ($request->has('category_id') && $request->category_id != '') {
            $category_id = $request->category_id;

            $productQuery->whereHas('categories', function ($query) use ($category_id) {
                $query->where('category_id', $category_id);
            });
        }

        if ($request->has('min') && $request->min >= 0 && $request->has('max') && $request->max > 0) {

            $min = $request->min ?? 0;
            $max = $request->max ?? 0;

            // $productQuery->whereHas('product_variations', function ($query) use ($min, $max) {

            //     $query->where('price', '>=', $min)
            //         ->where('price', '<=', $max);
            // });

            $productQuery = $productQuery->where('min_price', '>=', $min)->where('max_price', '<=', $max);
        }

        if ($request->has('search') && $request->search != '') {
            $productQuery = $productQuery->where('name', 'like', "%{$request->search}%")->inRandomOrder();
        }



        if ($request->has('is_featured') && $request->is_featured != '') {
            $is_featured = $request->is_featured;

            $productQuery = $productQuery->where('is_featured', 1)->inRandomOrder();
        }

        if ($request->has('best_seller') && $request->best_seller != '') {
            $productQuery = $productQuery->orderBy('total_sale_count', 'desc');
        }

        if ($request->has('best_discount') && $request->best_discount != '') {
            $productQuery = $productQuery->where('discount_type', 'percent')->orderBy('discount_value', 'desc');
        }

        if ($request->has('isdescending') && $request->isdescending) {
            $productQuery = $productQuery->orderBy('created_at', 'desc');
        }

        if ($request->has('isascending') && $request->isascending) {
            $productQuery = $productQuery->orderBy('created_at', 'asc');
        }

        $productQuery = $productQuery->paginate($perPage);

        $productCollection = ProductResource::collection($productQuery);

        return response()->json([
            'status' => true,
            'data' => $productCollection,
            'message' => __('product.product_list'),
        ], 200);
    }

    public function product_detail(Request $request)
    {
        $id = $request->id;

        $productdetails = Product::where('id', $id)->with('media', 'categories', 'brand', 'unit', 'product_variations', 'gallery', 'product_review')->first();


        if ($productdetails == null) {
            $message = __('product.product_not_found');

            return response()->json([
                'status' => false,
                'message' => $message,
            ], 200);
        }


        // $productDetailCollection = new ProductDetailResource($productdetails); // for manager product detail
        $productDetailCollection = ProductResource::make($productdetails);


        $categoryIds = $productdetails->categories->pluck('id')->toArray();

        $relatedProducts = Product::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('product_categories.id', $categoryIds);
        })
            ->where('products.id', '!=', $id)
            ->with('media', 'categories', 'brand', 'unit', 'product_variations', 'gallery')
            ->get();

        $relatedproductCollection = ProductResource::collection($relatedProducts);

        return response()->json([
            'status' => true,
            'data' => $productDetailCollection,
            'related-product' => $relatedproductCollection,
            'message' => __('product.product_detail'),
        ], 200);
    }

    /**
     * Get product detail by ID via route param: /api/product-detail/{id}
     */
    public function productDetailById(Request $request, $id)
    {
        $product = Product::where('id', $id)
            ->with([
                'media',
                'categories',
                'brand',
                'unit',
                'product_variations',
                'gallery',
                'product_review.user',
                'product_review.gallery',
            ])
            ->first();

        if (! $product) {
            return response()->json([
                'status' => false,
                'message' => __('product.product_not_found'),
            ], 404);
        }

        // Related products (same categories, excluding self)
        $categoryIds = $product->categories->pluck('id')->toArray();
        $relatedProducts = Product::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('product_categories.id', $categoryIds);
        })
            ->where('products.id', '!=', $id)
            ->with(['media', 'brand'])
            ->take(10)
            ->get();

        // Map related products to the required schema subset
        $relatedMapped = $relatedProducts->map(function ($item) {
            return [
                'product_id' => (string) $item->id,
                'product_image_url' => $item->feature_image,
                'product_brand_name' => optional($item->brand)->name,
                'product_name' => $item->name,
                'product_actual_price' => (float) ($item->max_price ?? 0),
                'product_price' => (float) ($item->min_price ?? 0),
            ];
        })->values();

        // Build main product payload from schema resource
        $payload = (new ProductSchemaResource($product))->resolve();
        $payload['related_products'] = $relatedMapped;

        return response()->json([
            'status' => true,
            'data' => $payload,
            'message' => __('product.product_detail'),
        ], 200);
    }

    public function ProductGallery(Request $request)
    {
        $productId = $request->input('product_id');

        // Retrieve service-wise gallery
        if ($productId) {
            $product = Product::find($productId);

            if (! $product) {
                return response()->json([
                    'status' => false,
                    'message' => __('product.product_not_found'),
                ], 404);
            }

            $data = ProductGallery::where('product_id', $productId)->get();

            $gallery = ['gallery' => $data, 'product' => $product];

            return response()->json([
                'status' => true,
                'data' => $gallery,
                'message' => __('product.product_gal_retrived'),
            ], 200);
        }

        // Retrieve all gallery
        $allData = ProductGallery::all();

        return response()->json([
            'status' => true,
            'data' => $allData,
            'message' => __('product.product_gallery'),
        ], 200);
    }

    public function addProduct(Request $request)
    {
        $level = $request->query('level');

        if ($level === 'basic') {
            return $this->addProductBasic($request);
        }

        if ($level === 'Inventory') {
            return $this->addProductInventory($request);
        }

        if ($level === 'discount') {
            return $this->addProductDiscount($request);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid level parameter'
        ], 400);
    }

    protected function addProductBasic(Request $request)
    {
        // Manual validation since we need to check $level first in addProduct
        $validator = app(\Modules\Product\Http\Requests\API\AddProductBasicRequest::class);
        $data = $request->validate($validator->rules());

        return DB::transaction(function () use ($data) {
            // 1. Handle Brand
            $brand = Brands::firstOrCreate(
                ['name' => $data['product_brand_name']],
                ['slug' => Str::slug($data['product_brand_name']) . '-' . Str::random(5)]
            );

            // 2. Create Product
            $product = new Product();
            $product->name = $data['product_name'];
            $product->slug = Str::slug($data['product_name']) . '-' . Str::random(5);
            $product->short_description = $data['product_short_description'];
            $product->description = $data['product_description'] ?? null;
            $product->brand_id = $brand->id;
            $product->unit_id = $data['product_unit'];
            $product->status = $data['product_status'] ? 1 : 0;
            $product->is_featured = isset($data['product_is_featured']) && $data['product_is_featured'] ? 1 : 0;
            
            // Default pricing/stock values for basic level
            $product->min_price = 0;
            $product->max_price = 0;
            $product->stock_qty = 0;
            $product->save();

            // 3. Handle Categories
            $categoryIds = [];
            foreach ($data['product_categories'] as $categoryName) {
                $category = ProductCategory::firstOrCreate(
                    ['name' => $categoryName],
                    ['slug' => Str::slug($categoryName) . '-' . Str::random(5)]
                );
                $categoryIds[] = $category->id;
            }
            $product->categories()->sync($categoryIds);

            // 4. Handle Tags
            $tagIds = [];
            foreach ($data['product_tags'] as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
            $product->tags_data()->sync($tagIds);

            // 5. Create Default Variation and Stock
            $location = Location::where('is_default', 1)->first() ?? Location::first();
            if ($location) {
                $variation = new ProductVariation();
                $variation->product_id = $product->id;
                $variation->sku = 'SKU-' . strtoupper(Str::random(8));
                $variation->price = 0;
                $variation->save();

                $stock = new ProductVariationStock();
                $stock->product_variation_id = $variation->id;
                $stock->location_id = $location->id;
                $stock->stock_qty = 0;
                $stock->save();
            }

            // 6. Handle Image
            if (!empty($data['product_image_url'])) {
                try {
                    $product->addMediaFromUrl($data['product_image_url'])
                        ->toMediaCollection('feature_images');
                } catch (\Exception $e) {
                    // Log error or ignore, product is already saved
                    Log::error('Failed to download product image from URL: ' . $data['product_image_url']);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Product created successfully',
                'data' => new ProductResource($product->load('brand', 'categories', 'tags_data', 'gallery'))
            ], 201);
        });
    }

    protected function addProductInventory(Request $request)
    {
        $validator = app(\Modules\Product\Http\Requests\API\AddProductInventoryRequest::class);
        $data = $request->validate($validator->rules());

        return DB::transaction(function () use ($data) {
            $product = Product::findOrFail($data['product_id']);

            if (!$data['product_has_variations']) {
                // Simple Product logic
                $product->has_variation = 0;
                $product->min_price = $data['product_price_included_tax'];
                $product->max_price = $data['product_price_included_tax'];
                $product->stock_qty = $data['product_stock'];
                $product->save();

                // Update or create single variation
                $variation = ProductVariation::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'sku' => $data['product_sku'] ?? 'SKU-' . strtoupper(Str::random(8)),
                        'code' => $data['product_code'] ?? null,
                        'price' => $data['product_price_included_tax'],
                    ]
                );

                $location = Location::where('is_default', 1)->first() ?? Location::first();
                if ($location) {
                    ProductVariationStock::updateOrCreate(
                        ['product_variation_id' => $variation->id, 'location_id' => $location->id],
                        ['stock_qty' => $data['product_stock']]
                    );
                }

                // Clean up old variations/combinations if any
                $product->product_variations()->where('id', '!=', $variation->id)->delete();
                $product->variation_combinations()->delete();

            } else {
                // Variations logic
                $product->has_variation = 1;
                
                // 1. Resolve Variation Groups
                $variationMap = []; // [typeName => [valName => valId]]
                if (!empty($data['product_variation_groups'])) {
                    foreach ($data['product_variation_groups'] as $group) {
                        $variationType = Variations::firstOrCreate(
                            ['name' => $group['variation_type']],
                            ['type' => 'select', 'status' => 1]
                        );
                        
                        foreach ($group['variation_values'] as $valName) {
                            $val = VariationValue::firstOrCreate(
                                ['variation_id' => $variationType->id, 'name' => $valName],
                                ['status' => 1, 'value' => $valName]
                            );
                            $variationMap[$group['variation_type']][$valName] = $val->id;
                            $variationTypeMap[$group['variation_type']] = $variationType->id;
                        }
                    }
                }

                // 2. Clear existing variations/combinations
                $product->product_variations()->delete();
                $product->variation_combinations()->delete();

                $totalStock = 0;
                $prices = [];

                // 3. Process Variants
                foreach ($data['product_variants'] as $variant) {
                    // Bulid variation_key
                    $keyParts = [];
                    $combinations = [];
                    foreach ($variant['variation_map'] as $typeName => $valName) {
                        $typeId = Variations::where('name', $typeName)->value('id') ?? ($variationTypeMap[$typeName] ?? null);
                        $valId = VariationValue::where('name', $valName)->where('variation_id', $typeId)->value('id') ?? ($variationMap[$typeName][$valName] ?? null);
                        
                        if ($typeId && $valId) {
                            $keyParts[] = "$typeId:$valId";
                            $combinations[] = ['variation_id' => $typeId, 'variation_value_id' => $valId];
                        }
                    }
                    $variationKey = implode('/', $keyParts) . '/';

                    $productVariation = ProductVariation::create([
                        'product_id' => $product->id,
                        'variation_key' => $variationKey,
                        'sku' => $variant['sku'] ?? 'SKU-' . strtoupper(Str::random(8)),
                        'code' => $variant['code'] ?? null,
                        'price' => $variant['price_included_tax'],
                    ]);

                    $location = Location::where('is_default', 1)->first() ?? Location::first();
                    if ($location) {
                        ProductVariationStock::create([
                            'product_variation_id' => $productVariation->id,
                            'location_id' => $location->id,
                            'stock_qty' => $variant['stock'],
                        ]);
                    }

                    foreach ($combinations as $comb) {
                        ProductVariationCombination::create([
                            'product_id' => $product->id,
                            'product_variation_id' => $productVariation->id,
                            'variation_id' => $comb['variation_id'],
                            'variation_value_id' => $comb['variation_value_id'],
                        ]);
                    }

                    $totalStock += $variant['stock'];
                    $prices[] = $variant['price_included_tax'];
                }

                $product->stock_qty = $totalStock;
                $product->min_price = !empty($prices) ? min($prices) : 0;
                $product->max_price = !empty($prices) ? max($prices) : 0;
                $product->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'Product inventory updated successfully',
                'data' => new ProductResource($product->load('product_variations', 'product_review', 'brand', 'categories', 'gallery'))
            ], 200);
        });
    }

    protected function addProductDiscount(Request $request)
    {
        $validator = app(\Modules\Product\Http\Requests\API\AddProductDiscountRequest::class);
        $data = $request->validate($validator->rules());

        return DB::transaction(function () use ($data) {
            $product = Product::findOrFail($data['product_id']);

            $product->discount_value = $data['product_discount_amount'];
            $product->discount_type = ($data['product_discount_type'] === 'percent') ? 'percentage' : 'fixed';
            $product->discount_start_date = Carbon::parse($data['product_discount_start'])->timestamp;
            $product->discount_end_date = Carbon::parse($data['product_discount_end'])->timestamp;
            $product->save();

            return response()->json([
                'status' => true,
                'message' => 'Product discount updated successfully',
                'data' => new ProductResource($product->load('product_variations', 'product_review', 'brand', 'categories', 'gallery'))
            ], 200);
        });
    }
    public function productfilter(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $filterType = $request->input('filter_type');

        $productQuery = Product::with('media', 'categories', 'brand', 'unit', 'product_variations', 'product_review', 'gallery');

        switch ($filterType) {
            case 'brand':
                if ($request->has('brand_id')) {
                    $productQuery->where('brand_id', $request->brand_id);
                }
                break;

            case 'category':
                if ($request->has('category_id')) {
                    $category_id = $request->category_id;
                    $productQuery->whereHas('categories', function ($query) use ($category_id) {
                        $query->where('product_categories.id', $category_id);
                    });
                }
                break;

            case 'price':
                if ($request->has('min_price') && $request->has('max_price')) {
                    $productQuery->where('min_price', '>=', $request->min_price)
                                 ->where('max_price', '<=', $request->max_price);
                }
                break;

            case 'status':
                if ($request->has('status')) {
                    $status = ($request->status == 'inactive' || $request->status == '0') ? 0 : 1;
                    $productQuery->where('status', $status);
                }
                break;
        }

        // Apply status filter if provided, regardless of filter_type
        if ($request->has('status')) {
            $status = ($request->status == 'inactive' || $request->status == '0') ? 0 : 1;
            $productQuery->where('status', $status);
        }

        $products = $productQuery->paginate($perPage);
        $productCollection = ProductResource::collection($products);

        return response()->json([
            'status' => true,
            'data' => $productCollection,
            'message' => __('product.product_list'),
        ], 200);
    }
}
