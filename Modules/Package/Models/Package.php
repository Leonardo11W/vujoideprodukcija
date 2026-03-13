<?php

namespace Modules\Package\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Package\Models\PackageService;
use App\Models\Branch;
use Carbon\Carbon;
use Modules\Service\Models\Service;

class Package extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'packages';



    const CUSTOM_FIELD_MODEL = 'Modules\Package\Models\Package';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Package\database\factories\PackageFactory::new();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }



    public function scopeBranch($query)
    {
        $branch_id = request()->selected_session_branch_id;
        if (isset($branch_id)) {
            return $query->where('branch_id', $branch_id);
        } else {
            return $query->whereNotNull('branch_id');
        }
    }

    public function employees()
    {
        return $this->belongsToMany(PackageEmployee::class, 'package_employees', 'package_id', 'employee_id');
    }

    public function employee()
    {
        return $this->hasMany(PackageEmployee::class, 'package_id');
    }

    public function services()
    {
        return $this->hasMany(PackageService::class, 'package_id');
    }

    public function service()
    {
        // Alias to access package services with singular name used elsewhere
        return $this->hasMany(PackageService::class, 'package_id');
    }

    public function serviceItems()
    {
        return $this->belongsToMany(\Modules\Service\Models\Service::class, 'package_services', 'package_id', 'service_id')
            ->withPivot(['qty', 'service_price', 'discounted_price', 'service_name']);
    }

    public function userPackage()
    {
        return $this->hasMany(UserPackage::class, 'package_id');
    }

    protected function getFeatureImageAttribute()
    {
        $media = $this->getFirstMediaUrl('package_image');

        return isset($media) && ! empty($media) ? $media : default_feature_image();
    }


    public function items() // You can rename this to services() if preferred
    {
        return $this->hasMany(PackageService::class, 'package_id');
    }

    public function packageServices()
    {
        return $this->hasMany(PackageService::class, 'package_id');
    }
}
