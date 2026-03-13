<?php

namespace Modules\Booking\Models;

use App\Models\BaseModel;
use App\Models\Branch;
use App\Models\User;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\UserPackageServices;
use Modules\Package\Models\BookingPackageService;
use Modules\Promotion\Models\UserCouponRedeem;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Commission\Trait\CommissionTrait;
use Modules\Service\Models\Service;
use Modules\Tip\Trait\TipTrait;
use Modules\Package\Models\UserPackageRedeem;
use Modules\Package\Models\UserPackage;

class Booking extends BaseModel
{
    use CommissionTrait;
    use HasFactory;
    use SoftDeletes;
    use TipTrait;

    protected $table = 'bookings';

    protected $casts = [

        'user_id' => 'integer',
        'branch_id' => 'integer',

    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Booking\database\factories\BookingFactory::new();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function services()
    {
        return $this->hasMany(BookingService::class, 'booking_id')->with('employee')
            ->leftJoin('services', 'booking_services.service_id', 'services.id')
            ->select('services.name as service_name', 'booking_services.*');
    }

    public function packages()
    {
        return $this->hasMany(BookingPackages::class, 'booking_id')->with('employee')
            ->leftJoin('packages', 'booking_packages.package_id', 'packages.id')
            ->select('packages.name as name', 'packages.description', 'booking_packages.*');
    }
    public function userPackageReddem()
    {
        return $this->hasMany(userPackageRedeem::class)->with('package');
    }


    public function products()
    {
        return $this->hasMany(BookingProduct::class, 'booking_id')
            ->leftJoin('products', 'booking_products.product_id', 'products.id')
            ->selectRaw('IFNULL(CONCAT(products.name, " - ", booking_products.variation_name), products.name) as product_name, booking_products.*')
            ->with('employee')->with('product.media');
    }

    //     public function services()
    //    {
    //     return $this->hasMany(BookingService::class, 'booking_id')
    //         ->with('employee')
    //         ->leftJoin('services', 'booking_services.service_id', 'services.id')
    //         ->leftJoin('media', function ($join) {
    //             $join->on('services.id', '=', 'media.model_id')
    //                  ->where('media.model_type', '=', 'Modules\Service\Models\Service')
    //                  ->where('media.collection_name', '=', 'feature_image');
    //         })
    //         ->select('services.name as service_name', 'booking_services.*', 'media.file_name as service_image','media.id as media_id');
    //      }

    public function booking_service()
    {
        return $this->hasMany(BookingService::class, 'booking_id', 'id')->with('employee', 'service');

    }

    public function service()
    {
        return $this->hasMany(BookingService::class, 'id', 'booking_id')->with('employee');
    }

    public function mainServices()
    {
        return $this->hasManyThrough(Service::class, BookingService::class, 'booking_id', 'id', 'id', 'service_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function bookingTransaction()
    {
        return $this->hasMany(BookingTransaction::class, 'booking_id');
    }

    public function payment()
    {
        return $this->hasOne(BookingTransaction::class);
    }

    public function bookingService()
    {
        return $this->hasMany(BookingService::class);
    }


    public function userCouponRedeem()
    {
        return $this->hasOne(UserCouponRedeem::class, 'booking_id');
    }

    public function userPackages()
    {
        return $this->hasMany(UserPackage::class);
    }
    public function bookingPackages()
    {
        return $this->hasMany(BookingPackages::class, 'booking_id', 'id')
            ->leftJoin('packages', 'booking_packages.package_id', 'packages.id')
            ->select('packages.name as name', 'packages.description', 'packages.start_date', 'packages.end_date', 'booking_packages.*');
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
    public function getBookingNumberAttribute()
    {
        return get_formatted_booking_id($this->id);
    }

 public static function totalservice($taxAmount, $tipAmount)
    {
        return self::select(
            DB::raw('DATE(bookings.start_date_time) AS start_date_time'),
            DB::raw('COUNT(DISTINCT bookings.id) AS total_bookings'),
            DB::raw('COALESCE(SUM(booking_services.service_price), 0) + COALESCE(SUM(booking_packages.package_price), 0) as total_service_amount'),
            DB::raw('
                COALESCE(SUM(booking_services.service_price), 0) +
                COALESCE(SUM(booking_packages.package_price), 0) +
                ' . $taxAmount . ' +
                ' . $tipAmount . ' AS total_amount')
        )
            ->leftJoin('booking_services', 'bookings.id', '=', 'booking_services.booking_id')
            ->leftJoin('booking_packages', 'bookings.id', '=', 'booking_packages.booking_id') // Join with BookingPackages
            ->whereHas('branch', function ($q) {
                $q->where('created_by', auth()->id());
            })
            ->where('bookings.status', 'completed')
            ->groupBy(DB::raw('DATE(bookings.start_date_time)'));
    }
    public function getTotalServiceAmountAttribute(): float
    {
        $serviceTotal = $this->services->sum('service_price');
        $packageTotal = $this->packages->sum('package_price'); // add quantity if needed
        return $serviceTotal + $packageTotal;
    }

    public function getTotalTipAmountAttribute(): float
    {
        return $this->payment->tip_amount ?? 0;
    }

    public function getTotalTaxAmountAttribute(): float
    {
        $taxData = optional($this->payment)->tax_percentage;
        if (empty($taxData)) {
            return 0;
        }

        $serviceTotal = $this->services->sum('service_price');
        $packageTotal = $this->packages->sum('package_price');
        $couponAmount = optional($this->userCouponRedeem)->discount ?? 0;

        $calculatedTax = \getBookingTaxamount(
            $serviceTotal + $packageTotal,
            $couponAmount,
            $taxData
        );

        return (float) ($calculatedTax['total_tax_amount'] ?? 0);
    }


    public function getGrandTotalAmountAttribute(): float
    {
        // dd($this->total_service_amount, $this->total_tax_amount, $this->total_tip_amount);
        return $this->total_service_amount + $this->total_tax_amount + $this->total_tip_amount;
    }

    // Reports Query
    // public static function dailyReport()
    // {
    //     return self::select(
    //         DB::raw('DATE(bookings.start_date_time) AS start_date_time'),
    //         DB::raw('COUNT(DISTINCT bookings.id) AS total_booking'),
    //         DB::raw('COUNT(DISTINCT CONCAT(booking_services.booking_id, "-", booking_services.service_id)) AS total_service'),
    //         DB::raw('COALESCE(SUM( tip_earnings.tip_amount), 0) AS total_tip_amount'),
    //         DB::raw('COALESCE(SUM(booking_services.service_price), 0) - COALESCE(SUM(DISTINCT ucr.discount), 0) AS total_service_amount'),
    //         DB::raw('SUM(CASE WHEN booking_services.service_id = (SELECT   service_id FROM booking_services AS bs2 WHERE bs2.booking_id = ANY_VALUE(bookings.id) LIMIT 1) THEN booking_services.service_price ELSE 0 END) AS total_service_amount_per_service'),
    //         DB::raw('COALESCE(SUM(CASE WHEN booking_services.service_id = (SELECT service_id FROM booking_services AS bs2 WHERE bs2.booking_id = ANY_VALUE(bookings.id) LIMIT 1) THEN btx.tax_total ELSE 0 END), 0) AS total_tax_amount'),
    //       DB::raw('
    //       COALESCE(SUM(booking_services.service_price), 0) - COALESCE(SUM(DISTINCT ucr.discount), 0) +
    //       COALESCE(SUM(CASE WHEN booking_services.service_id = (SELECT service_id FROM booking_services AS bs2 WHERE bs2.booking_id = ANY_VALUE(bookings.id) LIMIT 1) THEN btx.tax_total ELSE 0 END), 0) +
    //       COALESCE(SUM( tip_earnings.tip_amount), 0)
    //       AS total_amount')
    //     )
    //         ->leftJoin('booking_services', 'bookings.id', '=', 'booking_services.booking_id')
    //         ->leftJoin('tip_earnings', function ($join) {
    //             $join->on('bookings.id', '=', 'tip_earnings.tippable_id')
    //                 ->where('tip_earnings.tippable_type', '=', 'Modules\\Booking\\Models\\Booking');
    //         })
    //         ->leftJoin('user_coupon_redeem AS ucr', 'bookings.id', '=', 'ucr.booking_id')
    //         ->leftJoin(DB::raw('(SELECT
    //             bt.booking_id,
    //             COALESCE(SUM(CASE
    //                 WHEN JSON_UNQUOTE(JSON_EXTRACT(bt.tax_percentage, CONCAT(\'$[\', idx, \'].type\'))) = \'percent\'
    //                 THEN (
    //                     (SELECT COALESCE(SUM(bs.service_price), 0) FROM booking_services bs WHERE bs.booking_id = bt.booking_id)
    //                     - COALESCE((SELECT ucr2.discount FROM user_coupon_redeem ucr2 WHERE ucr2.booking_id = bt.booking_id LIMIT 1), 0)
    //                 ) * JSON_UNQUOTE(JSON_EXTRACT(bt.tax_percentage, CONCAT(\'$[\', idx, \'].percent\'))) / 100
    //                 WHEN JSON_UNQUOTE(JSON_EXTRACT(bt.tax_percentage, CONCAT(\'$[\', idx, \'].type\'))) = \'fixed\'
    //                 THEN COALESCE(JSON_UNQUOTE(JSON_EXTRACT(bt.tax_percentage, CONCAT(\'$[\', idx, \'].tax_amount\'))), JSON_UNQUOTE(JSON_EXTRACT(bt.tax_percentage, CONCAT(\'$[\', idx, \'].amount\'))), 0)
    //                 ELSE 0
    //             END), 0) AS tax_total
    //         FROM booking_transactions bt
    //         CROSS JOIN (
    //             SELECT 0 AS idx UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
    //         ) AS indices
    //         WHERE bt.payment_status = 1
    //         AND bt.tax_percentage IS NOT NULL
    //         AND idx < JSON_LENGTH(bt.tax_percentage)
    //         GROUP BY bt.booking_id) AS btx'), 'bookings.id', '=', 'btx.booking_id')
    //         ->where('bookings.status', 'completed')
    //         ->branch()
    //         ->groupBy('start_date_time');
    // }

    public function scopeDailyReport($query)
    {
        return $query->select(
            DB::raw('DATE(bookings.start_date_time) AS start_date_time'),
            DB::raw('COUNT(DISTINCT bookings.id) AS total_booking'),
            DB::raw('COUNT(DISTINCT CONCAT(booking_services.booking_id, "-", booking_services.service_id)) AS total_service'),
            DB::raw('COUNT(DISTINCT CONCAT(booking_packages.booking_id, "-", booking_packages.package_id)) AS total_package_count'),
            DB::raw('COALESCE(SUM(DISTINCT booking_transactions.tip_amount), 0) AS total_tip_amount'),
            DB::raw('SUM(CASE WHEN booking_services.service_id = (SELECT   service_id FROM booking_services AS bs2 WHERE bs2.booking_id = bookings.id LIMIT 1) THEN booking_services.service_price ELSE 0 END) AS total_service_amount_per_service'),
            DB::raw('COALESCE(SUM(tx.total_tax_amount), 0) AS total_tax_amount'),
            DB::raw('COALESCE(SUM(booking_services.service_price), 0) +
        COALESCE(SUM(tx.total_tax_amount), 0) +
        COALESCE(SUM(booking_transactions.tip_amount), 0) AS total_amount')

        )
            ->leftJoin('booking_services', 'bookings.id', '=', 'booking_services.booking_id')
            ->leftJoin('booking_packages', 'bookings.id', '=', 'booking_packages.booking_id')
            ->leftJoin('booking_transactions', 'bookings.id', '=', 'booking_transactions.booking_id')
            ->leftJoin(DB::raw('(
                SELECT
                    booking_id,
                    SUM(
                        CAST(
                            JSON_UNQUOTE(
                                JSON_EXTRACT(tax_percentage, CONCAT("$[", idx, "].amount"))
                            ) AS DECIMAL(10,2)
                        )
                    ) AS total_tax_amount
                FROM booking_transactions
                CROSS JOIN (
                    SELECT 0 AS idx UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
                ) indices
                WHERE idx < JSON_LENGTH(tax_percentage)
                GROUP BY booking_id
            ) AS tx'), 'tx.booking_id', '=', 'bookings.id')

            ->where('bookings.status', 'completed')
            ->groupBy('start_date_time');
    }

public static function tipamount()
{
    return self::select(
        DB::raw('DATE(bookings.start_date_time) AS start_date_time'),
        DB::raw('COUNT(DISTINCT bookings.id) AS total_bookings'),
        DB::raw('COALESCE(SUM(tip_earnings.tip_amount), 0) AS total_tip_amount')
    )
    ->leftJoin('tip_earnings', function ($join) {
        $join->on('bookings.id', '=', 'tip_earnings.tippable_id')
            ->where('tip_earnings.tippable_type', '=', 'Modules\\Booking\\Models\\Booking');
    })
    ->where('bookings.status', 'completed')
    ->branch()
    ->groupBy(DB::raw('DATE(bookings.start_date_time)'));

}

    public static function overallReport()
    {
        return self::select(
            'bookings.id as id',
            DB::raw('COALESCE(SUM(DISTINCT booking_services.service_price), 0) as total_service_amount'),
            DB::raw('COUNT(DISTINCT booking_services.service_id) AS total_service'),

            DB::raw('SUM(CASE
            WHEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.type\')) = \'percent\' THEN booking_services.service_price * JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.percent\')) / 100
            WHEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.type\')) = \'fixed\' THEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.tax_amount\'))
            ELSE 0
        END) AS total_tax_amount'),
            DB::raw('COALESCE(SUM(DISTINCT booking_services.service_price), 0) +
        SUM(CASE
            WHEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.type\')) = \'percent\' THEN booking_services.service_price * JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.percent\')) / 100
            WHEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.type\')) = \'fixed\' THEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.tax_amount\'))
            ELSE 0
        END) + COALESCE(SUM(DISTINCT booking_transactions.tip_amount), 0) AS total_amount'),
            DB::raw('COALESCE(SUM(DISTINCT booking_transactions.tip_amount), 0) AS total_tip_amount'),
            'bookings.start_date_time'
        )
            ->leftJoin('booking_transactions', 'bookings.id', '=', 'booking_transactions.booking_id')
            ->leftJoin('booking_services', 'bookings.id', '=', 'booking_services.booking_id')
            ->leftJoin(DB::raw('(SELECT
                booking_id,
                CONCAT(
                    \'{ "type": "\', jt.type, \'", "percent": \', jt.percent, \', "tax_amount": \', jt.tax_amount, \' }\'
                ) AS tax_info
            FROM (
                SELECT
                    booking_id,
                    JSON_UNQUOTE(JSON_EXTRACT(tax_percentage, CONCAT(\'$[\', idx, \'].type\'))) AS type,
                    JSON_UNQUOTE(JSON_EXTRACT(tax_percentage, CONCAT(\'$[\', idx, \'].percent\'))) AS percent,
                    JSON_UNQUOTE(JSON_EXTRACT(tax_percentage, CONCAT(\'$[\', idx, \'].tax_amount\'))) AS tax_amount
                FROM booking_transactions
                CROSS JOIN (
                    SELECT 0 AS idx UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
                ) AS indices
                WHERE idx < JSON_LENGTH(tax_percentage)
            ) AS jt
            GROUP BY booking_id, jt.type, jt.percent, jt.tax_amount) AS tx'), 'bookings.id', '=', 'tx.booking_id')
            ->where('bookings.status', 'completed')
            ->groupBy('bookings.id', 'bookings.start_date_time');
    }

    public function calculateServiceDuration()
    {
        $bookingServiceDuration = BookingService::where('booking_id', $this->id)
            ->sum('duration_min');

        if ($bookingServiceDuration > 0) {
            return $bookingServiceDuration;
        }

        // return BookingPackageService::where('booking_id', $this->id)->with('services')->sum('services.duration_min');
        $bookingPackageServices = BookingPackageService::where('booking_id', $this->id)
            ->with('services')
            ->get();

        $totalDuration = $bookingPackageServices->sum(function ($bookingService) {
            return $bookingService->services->duration_min ?? 0;
        });
        return $totalDuration;
    }

    public function userPackageServices()
    {
        return $this->hasManyThrough(
            UserPackageServices::class, // Target model
            BookingPackages::class, // Intermediate model
            'booking_id', // Foreign key on BookingPackage
            'package_id', // Foreign key on UserPackageService
            'id', // Local key on Booking
            'package_id' // Local key on BookingPackage
        )->with('packageService.services');
    }

    public function bookedPackageService()
    {
        return $this->hasMany(BookingPackageService::class, 'booking_id', 'id');
    }


}
