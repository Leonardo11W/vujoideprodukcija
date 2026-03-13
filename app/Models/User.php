<?php

namespace App\Models;

use App\Models\Presenters\UserPresenter;
use App\Models\Traits\HasHashedMediaTrait;
use App\Trait\CustomFieldsTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Commission\Models\CommissionEarning;
use Modules\Commission\Models\EmployeeCommission;
use Modules\Earning\Models\EmployeeEarning;
use Modules\Employee\Models\BranchEmployee;
use Modules\Employee\Models\EmployeeRating;
use Modules\Employee\Models\ManagerStaff;
use Modules\Package\Models\BookingPackages;
use Modules\Product\Models\Order;
use Modules\Service\Models\ServiceEmployee;
use Modules\Subscriptions\Models\Subscription;
use Modules\Tip\Models\TipEarning;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Traits\HasRoles;
use Modules\Wallet\Models\Wallet;

class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    use CustomFieldsTrait;
    use HasApiTokens;
    use HasFactory;
    use HasHashedMediaTrait;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;
    use UserPresenter;

    const CUSTOM_FIELD_MODEL = 'App\Models\User';

    protected $guarded = [
        'id',
        'updated_at',
        '_token',
        '_method',
        'password_confirmation',
    ];

    protected $dates = [
        'deleted_at',
        'date_of_birth',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'user_setting' => 'array',
    ];

    protected $appends = ['full_name', 'profile_image'];

    public function getFullNameAttribute() // notice that the attribute name is in CamelCase.
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function providers()
    {
        return $this->hasMany('App\Models\UserProvider');
    }

    /**
     * Get the list of users related to the current User.
     *
     * @return [array] roels
     */
    public function getRolesListAttribute()
    {
        return array_map('intval', $this->roles->pluck('id')->toArray());
    }

    /**
     * Route notifications for the Slack channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForSlack($notification)
    {
        return env('SLACK_NOTIFICATION_WEBHOOK');
    }

    /**
     * Get all of the branches for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptionPackage()
    {
        return $this->hasOne(Subscription::class, 'user_id', 'id')->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'));
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1)->where('is_banned', 0);
    }

    public function booking()
    {
        return $this->hasMany(Booking::class, 'user_id', 'id');
    }

    public function scopeCalenderResource($query)
    {
        $query->where('show_in_calender', 1);
    }

    protected function getProfileImageAttribute()
    {
        $media = $this->getFirstMediaUrl('profile_image');

        return isset($media) && !empty($media) ? $media : asset(config('app.avatar_base_path') . 'avatar.png');
    }

    // Employee Relations
    public function commission_earning()
    {
        return $this->hasMany(CommissionEarning::class, 'employee_id');
    }

    public function tip_earning()
    {
        return $this->hasMany(TipEarning::class, 'employee_id');
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_employee', 'employee_id', 'branch_id');
    }

    public function branch()
    {
        return $this->hasOne(BranchEmployee::class, 'employee_id')->with('getBranch');
    }

    public function mainBranch()
    {
        return $this->hasManyThrough(Branch::class, BranchEmployee::class, 'employee_id', 'id', 'id', 'branch_id');
    }

    public function services()
    {
        return $this->hasMany(ServiceEmployee::class, 'employee_id');
    }

    public function employeeBooking()
    {
        return $this->hasMany(BookingService::class, 'employee_id');
    }
    public function bookingPackages()
    {
        return $this->hasMany(BookingPackages::class, 'employee_id');
    }
    public function employeeEarnings()
    {
        return $this->hasMany(EmployeeEarning::class, 'employee_id');
    }

    public function commissions()
    {
        return $this->hasMany(EmployeeCommission::class, 'employee_id')->with('mainCommission');
    }

    // Manager-Staff relationships
    public function managedStaff()
    {
        return $this->belongsToMany(User::class, 'manager_staff', 'manager_id', 'staff_id');
    }

    public function managers()
    {
        return $this->belongsToMany(User::class, 'manager_staff', 'staff_id', 'manager_id');
    }

    public function wishlist()
    {
        return $this->belongsToMany(Product::class, 'wishlist', 'user_id', 'product_id');
    }

    public function scopeEmployee($query)
    {
        $query->role('employee');
    }

    public function scopeBranch($query)
    {
        $branch_id = request()->selected_session_branch_id;
        if (isset($branch_id)) {
            $query->join('branch_employee', 'users.id', '=', 'branch_employee.employee_id')
                ->where('branch_employee.branch_id', $branch_id);
        }
    }

    public function scopeVarified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeBookingEmployeesList($query)
    {
        return $query->select('users.*')
            ->active()
            ->varified()
            ->calenderResource()->employee()->branch()->orderBy('id', 'ASC');
    }

    public function rating()
    {
        return $this->hasMany(EmployeeRating::class, 'employee_id', 'id')->orderBy('updated_at', 'desc');
    }

    public function bookings()
    {
        return $this->hasManyThrough(Booking::class, BookingService::class, 'booking_id', 'id', 'id', 'employee_id');
    }



    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }
     public function employeeprofile()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    public static function staffReport(?int $branchId = null)
    {
        $bookingModel = addslashes(Booking::class);
        $branchId = $branchId ? (int) $branchId : null;
        $primaryBranchCondition = $branchId ? ' AND b.branch_id = ' . $branchId : '';
        $secondaryBranchCondition = $branchId ? ' AND b2.branch_id = ' . $branchId : '';
        $commissionBranchCondition = $branchId ? ' AND b.branch_id = ' . $branchId : '';
    
        return self::role(['manager', 'employee'])->select(
            'users.id',
            'users.first_name',
            'users.last_name',
            'users.email',
            'users.mobile',
            'users.updated_at',
    
            DB::raw('(
                SELECT 
                    COALESCE(SUM(bs.service_price), 0)
                FROM booking_services bs
                INNER JOIN bookings b 
                    ON b.id = bs.booking_id 
                    AND b.status = "completed"' . $primaryBranchCondition . '
                INNER JOIN booking_transactions bt 
                    ON bt.booking_id = b.id 
                    AND bt.payment_status = 1
                WHERE bs.employee_id = users.id
            ) 
            -
            COALESCE((
                SELECT SUM(
                    CASE
                        WHEN booking_total.total_price > 0
                        THEN (bs2.service_price / booking_total.total_price) 
                             *
                             (
                                CASE
                                    WHEN txn.discount_amount > 0
                                    THEN txn.discount_amount
    
                                    WHEN txn.discount_percentage > 0
                                    THEN booking_total.total_price * txn.discount_percentage / 100
    
                                    WHEN ucr.discount IS NOT NULL
                                    THEN ucr.discount
    
                                    ELSE 0
                                END
                             )
                        ELSE 0
                    END
                )
                FROM booking_services bs2
                INNER JOIN bookings b2 
                    ON b2.id = bs2.booking_id 
                    AND b2.status = "completed"' . $secondaryBranchCondition . '
                INNER JOIN booking_transactions txn 
                    ON txn.booking_id = b2.id 
                    AND txn.payment_status = 1
                LEFT JOIN user_coupon_redeem ucr 
                    ON ucr.booking_id = b2.id
    
                INNER JOIN (
                    SELECT booking_id, COALESCE(SUM(service_price), 0) AS total_price
                    FROM booking_services
                    GROUP BY booking_id
                ) booking_total 
                    ON booking_total.booking_id = b2.id
    
                WHERE bs2.employee_id = users.id
            ), 0) AS employee_booking_sum_service_price'),
    
            DB::raw("COALESCE((
                SELECT SUM(ce.commission_amount)
                FROM commission_earnings ce
                INNER JOIN bookings b 
                    ON b.id = ce.commissionable_id 
                    AND b.status = 'completed'" . $commissionBranchCondition . "
                INNER JOIN booking_transactions bt 
                    ON bt.booking_id = b.id 
                    AND bt.payment_status = 1
                WHERE ce.employee_id = users.id
                    AND ce.commissionable_type = '{$bookingModel}'
            ), 0) as commission_earning_sum_commission_amount")
        )
        ->withCount([
            'employeeBooking as employee_booking_count' => function ($query) use ($branchId) {
                $query->whereHas('booking', function ($bookingQuery) use ($branchId) {
                    $bookingQuery->where('status', 'completed');
                    if ($branchId) {
                        $bookingQuery->where('branch_id', $branchId);
                    }
                });
            },
        ])
        ->withSum([
            'tip_earning as tip_earning_sum_tip_amount' => function ($query) use ($branchId) {
                $query->where('tippable_type', Booking::class);
                if ($branchId) {
                    $query->whereHas('tippable', function ($tippableQuery) use ($branchId) {
                        $tippableQuery->where('branch_id', $branchId);
                    });
                }
            },
        ], 'tip_amount');
    }
    

    public function scopeWithTotalUnpaidServiceAmount($query)
    {
        return $query->leftJoin('commission_earnings', 'users.id', '=', 'commission_earnings.employee_id')
            ->leftJoin('booking_services', 'booking_services.booking_id', '=', 'commission_earnings.commissionable_id')
            ->leftJoin('booking_packages', 'booking_packages.booking_id', '=', 'commission_earnings.commissionable_id')
            ->where('commission_earnings.commission_status', 'unpaid')
            ->selectRaw('users.id as user_id,
                         COALESCE(SUM(booking_services.service_price), 0) + COALESCE(SUM(booking_packages.package_price), 0) as total_service_amount')
            ->groupBy('users.id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id', 'id')->with('orderGroup','orderItems');
    }
    
    public function getSpecialtyAttribute()
    {
        return $this->expert;
    }

    public function getExpertAttribute()
    {
        // Logic to determine expert specialty based on highest service category
        if ($this->employeeprofile && !empty($this->employeeprofile->expert)) {
            return $this->employeeprofile->expert;
        }

        if ($this->services->isEmpty()) {
            return null;
        }

        $categories = $this->services->map(function ($item) {
             // Access service -> category
             return $item->service->category->name ?? null; 
        })->filter()->countBy()->sortDesc();

        $top = $categories->keys()->first();
        return $top ? $top . ' Expert' : null;
    }
}
