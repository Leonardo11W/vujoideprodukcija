<?php

namespace App\Models;

use App\Models\Traits\HasSlug;
use App\Trait\CustomFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Modules\Booking\Models\Booking;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Employee\Models\BranchEmployee;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBranches;
use App\Models\User;
use Modules\Holiday\Models\Holiday;


class Branch extends BaseModel
{
    use CustomFieldsTrait;
    use HasFactory;
    use HasSlug;

    const CUSTOM_FIELD_MODEL = 'App\Models\Branch';

    protected $casts = [
        'contact_number' => 'string',
        'payment_method' => 'array',
        'city' => 'integer',
        'state' => 'integer',
        'country' => 'integer',
    ];

    protected $appends = ['feature_image'];

    /**
     * Get all the settings.
     *
     * @return mixed
     */
    public static function getAllBranches()
    {
        return Cache::rememberForever('branch.all', function () {
            return self::get();
        });
    }

    /**
     * Flush the cache.
     */
    public static function flushCache()
    {
        Cache::forget('branch.all');
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Add global scope for vendor filtering
        static::addGlobalScope('vendor', function ($builder) {
            if (app()->bound('active_vendor') && $vendor = app('active_vendor')) {
                $builder->where('vendor_id', $vendor->id);
            }
        });

        // Clear cache on model events
        static::updated(function () {
            static::flushCache();
        });

        static::created(function () {
            static::flushCache();
        });

        static::deleted(function () {
            static::flushCache();
        });
    }

    public function gallery()
    {
        return $this->hasMany(BranchGallery::class, 'id', 'feature_image');
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable')->with('country_data', 'state_data', 'city_data');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function branchEmployee()
    {
        return $this->hasMany(BranchEmployee::class, 'branch_id');
    }

    protected function getFeatureImageAttribute()
    {
        $media = $this->getFirstMediaUrl('feature_image');

        return isset($media) && ! empty($media) ? $media : default_feature_image();
    }

    public function gallerys()
    {
        return $this->hasMany(BranchGallery::class, 'branch_id', 'id');
    }

    public function businessHours()
    {
        return $this->hasMany(BussinessHour::class, 'branch_id');
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class, 'branch_id');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_branches')
            ->withPivot('service_price', 'duration_min');
    }

    public function branchServices()
    {
        return $this->hasMany(ServiceBranches::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    public function employeeRatings()
    {
        return $this->hasMany(\Modules\Employee\Models\EmployeeRating::class, 'branch_id');
    }
    public function bussinesshours()
    {
        return $this->hasMany(\Modules\BussinessHour\Models\BussinessHour::class, 'branch_id');
    }
}
