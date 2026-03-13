<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tag\Models\Tag;
use Modules\Category\Models\Category;
use Modules\Product\Models\Brands;
use Modules\Product\Models\Unit;
use Modules\Product\Models\Review;
use Modules\Product\Models\ProductVariation;
use Modules\Product\Models\ProductGallery;
use App\Models\BaseModel;
use Modules\Product\Models\Cart;
use App\Models\Branch;

class Product extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'brand_id',
        'unit_id',
        'product_tags',
        'min_price',
        'max_price',
        'discount_value',
        'discount_type',
        'discount_start_date',
        'discount_end_date',
        'sell_target',
        'stock_qty',
        'status',
        'is_featured',
        'min_purchase_qty',
        'max_purchase_qty',
        'has_variation',
        'has_warranty',
        'total_sale_count',
        'standard_delivery_hours',
        'express_delivery_hours',
        'size_guide',
        'reward_points',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'branch_id',
    ];

    protected $appends = ['feature_image', 'multiple_feature_images'];

    protected $casts = [
        'min_purchase_qty' => 'double',
        'max_purchase_qty' => 'double',
        'min_price' => 'double',
        'max_price' => 'double',
        'brand_id' => 'integer',
        'branch_id' => 'integer',
        'unit_id' => 'integer',
        'stock_qty' => 'integer',
        'is_featured' => 'integer',
        'status' => 'integer',
        'has_variation' => 'integer',
        'has_warranty' => 'integer',
    ];

    const CUSTOM_FIELD_MODEL = 'Modules\\Product\\Models\\Product';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Product\database\factories\ProductFactory::new();
    }

    public function scopeIsPublished($query)
    {
        return $query->where('status', 1);
    }

    // public function categories()
    // {
    //     return $this->belongsToMany(Category::class, 'product_category_mappings');
    // }

    public function categories()
    {
        return $this->belongsToMany(ProductCategory::class, 'product_category_mappings', 'product_id', 'category_id');
    }

    public function product_category()
    {
        return $this->hasMany(ProductCategoryMapping::class);
    }


    public function gallery()
    {
        return $this->hasMany(ProductGallery::class);
    }

    public function product_review()
    {
        return $this->hasMany(Review::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function product_variations()
    {
        return $this->hasMany(ProductVariation::class)->with('product_variation_stock', 'combinations');
    }

    public function variation_combinations()
    {
        return $this->hasMany(ProductVariationCombination::class);
    }

    public function tags_data()
    {
        return $this->belongsToMany(Tag::class, 'product_tags', 'product_id', 'tag_id');
    }

    protected function getFeatureImageAttribute()
    {
        $media = $this->getFirstMediaUrl('feature_images');

        if (empty($media)) {
            $media = $this->getFirstMediaUrl();
        }

        return isset($media) && ! empty($media) ? $media : 'https://dummyimage.com/600x300/cfcfcf/000000.png';
    }

    protected function getMultipleFeatureImagesAttribute()
    {
        $mediaItems = $this->getMedia('feature_images');

        if ($mediaItems->isEmpty()) {
            return ['https://dummyimage.com/600x300/cfcfcf/000000.png'];
        }

        return $mediaItems->map(function ($media) {
            return $media->getUrl();
        })->toArray();
    }

    public function isInCart()
    {
        if (!auth()->check()) {
            return false;
        }

        return Cart::where('user_id', auth()->id())
            ->where('product_id', $this->id)
            ->exists();
    }
    /**
     * Scope to filter products based on branch and user role availability.
     * Includes global products (branch_id = null).
     */
    public function scopeAvailableData($query, $branchId = null, $managerBranchIds = [])
    {
        // Always filtered by active status
        $query->where('status', 1);

        // Apply Branch Logic
        $query->where(function($q) use ($branchId, $managerBranchIds) {
            // Always include global products
            $q->whereNull('branch_id');

            // Add branch specific logic
            if (!empty($managerBranchIds)) {
                // Manager Role
                if ($branchId && in_array($branchId, $managerBranchIds)) {
                     // Specific Branch selected and valid
                     $q->orWhere('branch_id', $branchId);
                } else {
                     // All assigned branches
                     $q->orWhereIn('branch_id', $managerBranchIds);
                }
            } else {
                // Non-Manager (User/Guest)
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                } else {
                    // No branch selected, allow all branches (and global from above)
                    $q->orWhereNotNull('branch_id');
                }
            }
        });

        return $query;
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
