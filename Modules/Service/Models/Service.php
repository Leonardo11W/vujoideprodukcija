<?php

namespace Modules\Service\Models;

use App\Models\BaseModel;
use App\Models\Traits\HasSlug;
use App\Trait\CustomFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Category\Models\Category;

class Service extends BaseModel
{
    use CustomFieldsTrait;
    use HasFactory;
    use HasSlug;
    use SoftDeletes;

    protected $table = 'services';

    protected $fillable = ['slug', 'name', 'description', 'duration_min', 'default_price', 'category_id', 'sub_category_id', 'status'];

    protected $appends = ['feature_image'];

    protected $casts = [

        'duration_min' => 'integer',
        'default_price' => 'double',
        'category_id' => 'integer',
        'sub_category_id' => 'integer',
        'status' => 'integer',

    ];

    const CUSTOM_FIELD_MODEL = 'Modules\Service\Models\Service';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Service\database\factories\ServiceFactory::new();
    }

    protected static function boot()
    {
        parent::boot();

         static::addGlobalScope('vendor', function ($builder) {
            if (app()->bound('active_vendor') && $vendor = app('active_vendor')) {
                $builder->where('vendor_id', $vendor->id);
            }
            
           if (session()->has('selected_branch_id')) {
                $selectedBranchId = session('selected_branch_id');
                $builder->whereHas('branches', function ($query) use ($selectedBranchId) {
                    $query->where('branch_id', $selectedBranchId);
                });
            }
        });

        // create a event to happen on creating
        static::creating(function ($table) {
            //
        });

        static::saving(function ($table) {
            //
        });

        static::updating(function ($table) {
            //
        });
    }

    public function employee()
    {
        return $this->hasMany(ServiceEmployee::class, 'service_id', 'id');
    }

    public function gallery()
    {
        return $this->hasMany(ServiceGallery::class, 'service_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function sub_category()
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function branches()
    {
        return $this->hasMany(ServiceBranches::class, 'service_id');
    }

    public function branchRelation()
    {
        return $this->belongsToMany(Branch::class, 'service_branches');
    }

    protected function getFeatureImageAttribute()
    {
        $media = $this->getFirstMediaUrl('feature_image');

        return isset($media) && ! empty($media) ? $media : default_feature_image();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Resolve branch-specific duration and price.
     * Sets the attributes on the model instance.
     */
    public function resolveBranchSpecificData($branchId)
    {
        if (!$branchId) return;
        
        // Use eager loaded relation if available to avoid N+1
        $branchData = $this->branches->firstWhere('branch_id', (int) $branchId);
        
        if ($branchData) {
            if (isset($branchData->service_price) && !is_null($branchData->service_price)) {
                $this->default_price = (float) $branchData->service_price;
            }
            if (isset($branchData->duration_min) && !is_null($branchData->duration_min)) {
                $this->duration_min = (int) $branchData->duration_min;
            }
        }
    }
}
