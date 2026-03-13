<?php

namespace Modules\Booking\database\seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingTransaction;
use Modules\Booking\Trait\BookingTrait;
use Modules\Commission\Models\CommissionEarning;
use Modules\Service\Models\ServiceBranches;
use Modules\Tax\Models\Tax;
use Modules\Employee\Models\BranchEmployee;
class BookingDatabaseSeeder extends Seeder
{
    use BookingTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        /*
         * Bookings Seed
         * ------------------
         */

        DB::table('bookings')->truncate();
        if (env('IS_FAKE_DATA')) {
            // Build default tax payload used in booking_transactions.tax_percentage
            // Shape expected by invoice/UI/helpers: [{ name, type, percent? OR tax_amount?/amount? }]
            $activeTaxes = Tax::active()
                ->where(function ($q) {
                    $q->whereNull('module_type')->orWhere('module_type', 'services');
                })
                ->where('status', 1)
                ->get();

            $taxPayload = $activeTaxes->map(function ($tax) {
                if (($tax->type ?? '') === 'percent') {
                    return [
                        'name' => $tax->title ?? 'Tax',
                        'type' => 'percent',
                        'percent' => (float) ($tax->value ?? 0),
                    ];
                }

                return [
                    'name' => $tax->title ?? 'Tax',
                    'type' => 'fixed',
                    'tax_amount' => (float) ($tax->value ?? 0),
                    'amount' => (float) ($tax->value ?? 0),
                ];
            })->values()->toArray();

            $bookings = [
                [
                    'branch_id' => 1,
                    'start_date_time' => Carbon::now(),
                    'note' => '',
                    'user_id' => 2,
                    'status' => 'confirmed',
                    'services' => [
                        [
                            'employee_id' => 43,
                            'service_id' => 1,
                            'service_price' => 300,
                            'duration_min' => 30,
                            'start_date_time' => Carbon::now(),
                        ],
                    ],
                ],
            ];

            Booking::factory(15)->create()->each(function ($bk) use ($taxPayload) {
                $time = $bk->start_date_time;
                $service = ServiceBranches::where(['service_id' => rand(1, 22), 'branch_id' => $bk->branch_id])->first();
                $emp = BranchEmployee::where('branch_id', $bk->branch_id)->inRandomOrder()->first()->employee_id;
                $services = [
                    [
                        'employee_id' => $emp,
                        'service_id' => $service->service_id,
                        'service_price' => $service->service_price,
                        'duration_min' => $service->duration_min,
                        'start_date_time' => $time,
                    ],
                ];
                $this->updateBookingService($services, $bk->id);

                $payment_status = $bk->status === 'completed' ? 1 : 0;

                BookingTransaction::updateOrCreate(
                    ['booking_id' => $bk->id],
                    [
                        'external_transaction_id' => null,
                        'transaction_type' => 'cash',
                        'discount_percentage' => 0,
                        'discount_amount' => 10,
                        'tip_amount' => $bk->status === 'completed' ? rand(1, 35) : 0,
                        'tax_percentage' => $taxPayload,
                        'payment_status' => $payment_status,
                    ]
                );

                if ($bk->status === 'completed') {
                    $bk->commission()->save(new CommissionEarning([
                        'employee_id' => $emp,
                        'commission_amount' => 100,
                        'commission_status' => 'unpaid',
                        'payment_date' => date('Y-m-d'),
                    ]));
                }
            });

            // Create at least 5 bookings for user John (id: 2)
            $johnUserId = 2;
            $branchId = 1;
            $statuses = ['pending', 'confirmed', 'check_in', 'checkout', 'completed'];

            $service = ServiceBranches::where('branch_id', $branchId)->first();
            $branchEmployee = BranchEmployee::where('branch_id', $branchId)->inRandomOrder()->first();

            for ($i = 0; $i < 5; $i++) {
                $startTime = Carbon::now()->addDays($i - 2)->setTime(10 + ($i % 14), 0, 0);
                $bk = Booking::create([
                    'branch_id' => $branchId,
                    'user_id' => $johnUserId,
                    'start_date_time' => $startTime,
                    'note' => 'Booking for John (user #2) - #' . ($i + 1),
                    'status' => $statuses[$i] ?? 'confirmed',
                ]);

                if ($service && $branchEmployee) {
                    $services = [
                        [
                            'employee_id' => $branchEmployee->employee_id,
                            'service_id' => $service->service_id,
                            'service_price' => $service->service_price ?? 100,
                            'duration_min' => $service->duration_min ?? 30,
                            'start_date_time' => $startTime,
                        ],
                    ];
                    $this->updateBookingService($services, $bk->id);

                    $paymentStatus = $bk->status === 'completed' ? 1 : 0;
                    BookingTransaction::updateOrCreate(
                        ['booking_id' => $bk->id],
                        [
                            'external_transaction_id' => null,
                            'transaction_type' => 'cash',
                            'discount_percentage' => 0,
                            'discount_amount' => 0,
                            'tip_amount' => $bk->status === 'completed' ? rand(5, 25) : 0,
                            'tax_percentage' => $taxPayload,
                            'payment_status' => $paymentStatus,
                        ]
                    );

                    if ($bk->status === 'completed') {
                        $bk->commission()->save(new CommissionEarning([
                            'employee_id' => $branchEmployee->employee_id,
                            'commission_amount' => 50,
                            'commission_status' => 'unpaid',
                            'payment_date' => date('Y-m-d'),
                        ]));
                    }
                }
            }

            // Enable foreign key checks!
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
