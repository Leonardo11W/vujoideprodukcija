<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Category\Models\Category;
use Modules\Employee\Models\BranchEmployee;
use Modules\Product\Models\Brands;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCategory;
use Modules\Product\Models\Unit;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBranches;
use Modules\Service\Models\ServiceEmployee;

/**
 * Adds extra service categories, subcategories, service experts, product categories, subcategories, brands, and products.
 * Idempotent: safe to run multiple times (uses unique vujo-x slugs and firstOrCreate where possible).
 *
 * php artisan db:seed --class=ExtraCatalogSeeder --force
 */
class ExtraCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->orderBy('id')->first();
        if (! $branch) {
            $this->command?->error('ExtraCatalogSeeder: no branch in database. Add a branch first.');

            return;
        }
        $branchId = (int) $branch->id;

        $unit = Unit::query()->orderBy('id')->first();
        if (! $unit) {
            $unit = Unit::firstOrCreate(
                ['name' => 'kom'],
                ['status' => 1]
            );
        }
        $unitId = (int) $unit->id;

        // --- 1) Service categories + subcategories (bookings) ---
        $bodyCat = Category::firstOrCreate(
            ['slug' => 'vujo-tijelo-regeneracija'],
            [
                'name' => 'Tijelo i regeneracija',
                'status' => 1,
                'parent_id' => null,
            ]
        );
        $subBody1 = Category::firstOrCreate(
            ['slug' => 'vujo-masaze-i-wrap'],
            [
                'name' => 'Masaže i tretmani',
                'status' => 1,
                'parent_id' => $bodyCat->id,
            ]
        );
        $subBody2 = Category::firstOrCreate(
            ['slug' => 'vujo-depilacija'],
            [
                'name' => 'Depilacija',
                'status' => 1,
                'parent_id' => $bodyCat->id,
            ]
        );

        $hairExtra = Category::firstOrCreate(
            ['slug' => 'vujo-koloracija-njega'],
            [
                'name' => 'Boja i njega kose',
                'status' => 1,
                'parent_id' => null,
            ]
        );
        $subHair1 = Category::firstOrCreate(
            ['slug' => 'vujo-balayage-ombre'],
            [
                'name' => 'Balayage & Ombre',
                'status' => 1,
                'parent_id' => $hairExtra->id,
            ]
        );
        $subHair2 = Category::firstOrCreate(
            ['slug' => 'vujo-keratin-terapija'],
            [
                'name' => 'Keratin i rekonstrukcija',
                'status' => 1,
                'parent_id' => $hairExtra->id,
            ]
        );

        // --- 2) Services for those subcategories + branch pricing ---
        $serviceDefs = [
            [
                'slug' => 'vujo-anticelulit-masaza',
                'name' => 'Anticelulit masaža',
                'sub' => $subBody1,
                'price' => 45.0,
                'min' => 50,
            ],
            [
                'slug' => 'vujo-klasicna-sportska-masaza',
                'name' => 'Klasična / sportska masaža',
                'sub' => $subBody1,
                'price' => 40.0,
                'min' => 45,
            ],
            [
                'slug' => 'vujo-depilacija-noge',
                'name' => 'Depilacija noge pune',
                'sub' => $subBody2,
                'price' => 35.0,
                'min' => 40,
            ],
            [
                'slug' => 'vujo-bojanje-ukorijenjivanje',
                'name' => 'Bojanje / ukorijenjivanje',
                'sub' => $subHair1,
                'price' => 55.0,
                'min' => 90,
            ],
            [
                'slug' => 'vujo-keratin-terapija-obnova',
                'name' => 'Keratin – obnova kose',
                'sub' => $subHair2,
                'price' => 65.0,
                'min' => 120,
            ],
        ];

        foreach ($serviceDefs as $def) {
            $sub = $def['sub'];
            $service = Service::firstOrCreate(
                ['slug' => $def['slug']],
                [
                    'name' => $def['name'],
                    'description' => '<p>'.e($def['name']).' — premium usluga salona.</p>',
                    'duration_min' => $def['min'],
                    'default_price' => $def['price'],
                    'category_id' => (int) $sub->parent_id,
                    'sub_category_id' => (int) $sub->id,
                    'status' => 1,
                ]
            );
            ServiceBranches::firstOrCreate(
                [
                    'service_id' => $service->id,
                    'branch_id' => $branchId,
                ],
                [
                    'service_price' => $def['price'],
                    'duration_min' => (float) $def['min'],
                ]
            );
        }

        // --- 3) Product categories + subcategories ---
        $pcSkin = ProductCategory::firstOrCreate(
            ['slug' => 'vujo-kozmetika-lica'],
            [
                'name' => 'Kozmetika za lice',
                'status' => 1,
                'parent_id' => null,
            ]
        );
        $pcSubCleanser = ProductCategory::firstOrCreate(
            ['slug' => 'vujo-cistaci-tonici'],
            [
                'name' => 'Čistači i tonici',
                'status' => 1,
                'parent_id' => $pcSkin->id,
            ]
        );
        $pcSubSerum = ProductCategory::firstOrCreate(
            ['slug' => 'vujo-serumi-kreme'],
            [
                'name' => 'Serumi i kreme',
                'status' => 1,
                'parent_id' => $pcSkin->id,
            ]
        );

        $pcHair = ProductCategory::firstOrCreate(
            ['slug' => 'vujo-profesionalna-kosa'],
            [
                'name' => 'Profesionalna njega kose',
                'status' => 1,
                'parent_id' => null,
            ]
        );
        $pcShampoo = ProductCategory::firstOrCreate(
            ['slug' => 'vujo-samponi-obnavljanje'],
            [
                'name' => 'Šamponi i obnavljanje',
                'status' => 1,
                'parent_id' => $pcHair->id,
            ]
        );
        $pcStyling = ProductCategory::firstOrCreate(
            ['slug' => 'vujo-styling-zastita'],
            [
                'name' => 'Styling i toplinska zaštita',
                'status' => 1,
                'parent_id' => $pcHair->id,
            ]
        );

        // --- 4) Brands ---
        $brandData = [
            ['slug' => 'vujo-lab', 'name' => 'Vujo Lab'],
            ['slug' => 'dalmatica-care', 'name' => 'Dalmatica Care'],
            ['slug' => 'adriatic-beauty', 'name' => 'Adriatic Beauty'],
        ];
        $brands = [];
        foreach ($brandData as $b) {
            $brands[] = Brands::firstOrCreate(
                ['slug' => $b['slug']],
                ['name' => $b['name'], 'status' => 1]
            );
        }
        $syncBrandIds = array_map(fn ($b) => $b->id, $brands);
        $pcSkin->brands()->syncWithoutDetaching($syncBrandIds);
        $pcHair->brands()->syncWithoutDetaching($syncBrandIds);

        // --- 5) Products + category mapping ---
        $productDefs = [
            ['slug' => 'vujo-hidratantni-serum', 'name' => 'Hidratantni serums Vujo', 'sub' => $pcSubSerum, 'price' => 32.0, 'brand' => 0, 'stock' => 40],
            ['slug' => 'vujo-micelarni-gel', 'name' => 'Micelarni gel za čišćenje', 'sub' => $pcSubCleanser, 'price' => 18.5, 'brand' => 0, 'stock' => 60],
            ['slug' => 'vujo-sampon-obnova-keratin', 'name' => 'Šampon obnova (keratin)', 'sub' => $pcShampoo, 'price' => 24.0, 'brand' => 1, 'stock' => 50],
            ['slug' => 'vujo-termo-zastita-200', 'name' => 'Termo zaštita za kosu 200°C', 'sub' => $pcStyling, 'price' => 21.0, 'brand' => 2, 'stock' => 45],
        ];
        foreach ($productDefs as $row) {
            $brand = $brands[$row['brand']];
            $product = Product::firstOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'short_description' => 'Salon asortiman',
                    'description' => '<p>'.e($row['name']).'.</p>',
                    'brand_id' => $brand->id,
                    'unit_id' => $unitId,
                    'min_price' => $row['price'],
                    'max_price' => $row['price'],
                    'discount_value' => 0.0,
                    'discount_type' => 'percent',
                    'stock_qty' => $row['stock'],
                    'status' => 1,
                    'is_featured' => 1,
                    'min_purchase_qty' => 1,
                    'max_purchase_qty' => 10,
                    'has_variation' => 0,
                    'has_warranty' => 0,
                    'total_sale_count' => 0.0,
                    'standard_delivery_hours' => 48,
                    'express_delivery_hours' => 24,
                ]
            );
            $cat = $row['sub'];
            $exists = DB::table('product_category_mappings')
                ->where('product_id', $product->id)
                ->where('category_id', $cat->id)
                ->whereNull('deleted_at')
                ->exists();
            if (! $exists) {
                DB::table('product_category_mappings')->insert([
                    'product_id' => $product->id,
                    'category_id' => $cat->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // --- 6) Expert staff + link to all new services in this seeder ---
        $experts = [
            [
                'first_name' => 'Ana',
                'last_name' => 'Stručnjak',
                'email' => 'ana.strucnjak@vujo.catalog',
            ],
            [
                'first_name' => 'Marko',
                'last_name' => 'Salon',
                'email' => 'marko.strucnjak@vujo.catalog',
            ],
        ];
        foreach ($experts as $e) {
            $user = User::firstOrCreate(
                ['email' => $e['email']],
                [
                    'first_name' => $e['first_name'],
                    'last_name' => $e['last_name'],
                    'password' => Hash::make('VujoCatalog2025!'),
                    'mobile' => '+385 91 000 0000',
                    'gender' => 'other',
                    'email_verified_at' => Carbon::now(),
                ]
            );
            if (! $user->hasRole('employee')) {
                $user->assignRole('employee');
            }
            BranchEmployee::firstOrCreate(
                [
                    'employee_id' => $user->id,
                    'branch_id' => $branchId,
                ],
                ['is_primary' => 0]
            );
        }

        $expertUserIds = User::query()->whereIn('email', array_column($experts, 'email'))->pluck('id');
        $allSeederServiceSlugs = array_column($serviceDefs, 'slug');
        $serviceIdList = Service::query()->whereIn('slug', $allSeederServiceSlugs)->pluck('id');
        foreach ($expertUserIds as $eid) {
            foreach ($serviceIdList as $sid) {
                ServiceEmployee::firstOrCreate(
                    [
                        'service_id' => $sid,
                        'employee_id' => $eid,
                    ],
                    []
                );
            }
        }

        $this->command?->info('ExtraCatalogSeeder: kategorije usluga, usluge, proizvodi, brendovi, kategorije proizvoda i stručnjaci ažurirani.');
    }
}
