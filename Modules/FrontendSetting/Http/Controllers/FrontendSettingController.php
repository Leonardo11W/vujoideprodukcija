<?php

namespace Modules\FrontendSetting\Http\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Branch;
use Modules\Category\Models\Category;
use Modules\Package\Models\Package;
use Modules\Product\Models\Product;
use app\Models\User;
use Spatie\Permission\Models\Role;
use Modules\FrontendSetting\Models\FrontendSetting;
use PhpParser\Node\Stmt\Break_;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Page\Models\Page;
class FrontendSettingController extends Controller
{
    protected $configurations;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'frontend.frontendsetting_title';


        // module icon
        $this->module_icon = 'fa-solid fa-clipboard-list';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => $this->module_icon,

        ]);
    }

    public function frontendSettings(Request $request)
    {
        $auth_user = auth()->user();

        $global_booking = false;

        $pageTitle = __('messages.frontend_setting');
        $page = $request->page;

        if ($page == '') {
            if ($auth_user->hasAnyRole(['admin', 'demo_admin'])) {
                $page = 'landing-page-setting';
            }
        }

        return view('frontendsetting::index', compact('global_booking', 'page', 'pageTitle', 'auth_user'));
    }

    public function layoutPage(Request $request)
    {
        $page = $request->page;

        // Get the record by type = page (or you can hardcode a known type)
        $landing_page_data = FrontendSetting::where('type', $page)->first();
        $tabpage = '';

        if ($landing_page_data && $landing_page_data->value) {
            $decoded = is_string($landing_page_data->value)
                ? json_decode($landing_page_data->value, true)
                : (is_array($landing_page_data->value) ? $landing_page_data->value : []);

            $this->processLayoutPageData($page, $decoded, $landing_page_data, $tabpage);
        }

        if ($tabpage === 'why_choose_section') {
            // You can fetch data here if needed, or let the blade handle it
            return response()->json([
                'view' => view('frontendsetting::sections.why_choose_section')->render()
            ]);
        }
        return response()->json(view("frontendsetting::{$page}", [
            'landing_page_data' => $landing_page_data,
            'page' => $page,
            'tabpage' => $tabpage,
            'user_data' => auth()->user()
        ])->render());
    }


    protected function processLayoutPageData($page, $decoded, &$landing_page_data, &$tabpage)
    {
        $tabpage = $page === 'landing-page-setting' ? 'section_1' : '';
        $keysMap = [
            'header-menu-setting' => ['header-menu-setting', 'selectbranch', 'home', 'mybooking', 'category', 'service', 'shop'],
            'footer-setting' => ['footer_setting'],
            'login-register-setting' => ['login_register', 'title', 'description']
        ];

        foreach ($keysMap[$page] ?? [] as $key) {
            if (isset($decoded->$key)) {
                $landing_page_data[$key] = $decoded->$key;
            }
        }

        if ($page === 'login-register-setting') {
            $this->processLoginRegisterData($decoded, $landing_page_data);
        }
    }

    public function landingLayoutPage(Request $request)
    {
        $tabpage = $request->tabpage;
        $landing_page = FrontendSetting::where('key', $tabpage)->first();
        $user_data = auth()->user();
        $experts = [];

        if (!empty($landing_page['value'])) {
            $decodedata = is_string($landing_page['value'])
                ? json_decode($landing_page['value'], true)
                : $landing_page['value'];

            switch ($tabpage) {
                case 'section_1':
                    $landing_page['section_1'] = $decodedata['section_1'] ?? false;
                    $landing_page['title'] = $decodedata['title'] ?? '';
                    $landing_page['description'] = $decodedata['description'] ?? '';
                    $landing_page['enable_search'] = $decodedata['enable_search'] ?? false;
                    break;

                case 'section_2':
                    $landing_page['enable_quick_booking'] = $decodedata['enable_quick_booking'] ?? false;
                    $landing_page['book_now_id'] = $decodedata['book_now_id'] ?? 0;
                    break;

                case 'section_3':
                    $landing_page['branch_ids'] = $decodedata['branch_ids'] ?? [];
                    $landing_page['branch_names'] = $decodedata['branch_names'] ?? [];
                    $landing_page['status'] = $decodedata['status'] ?? 0;
                    break;

                case 'section_4':
                    $landing_page['select_category'] = $decodedata['select_category'] ?? [];
                    $landing_page['category_names'] = $decodedata['category_names'] ?? [];
                    $landing_page['status'] = $decodedata['status'] ?? 0;
                    break;

                case 'section_5':
                    $landing_page['product_id'] = $decodedata['product_id'] ?? [];
                    $landing_page['status'] = $decodedata['status'] ?? 0;
                    break;

                case 'section_6':
                    $landing_page['membership_id'] = $decodedata['membership_id'] ?? 0;
                    $landing_page['status'] = $decodedata['status'] ?? 0;
                    break;

                case 'section_7':
                    $landing_page['status'] = $decodedata['status'] ?? 0;
                    $landing_page['expert_id'] = $decodedata['expert_id'] ?? 0;

                    // 🔹 Fetch experts only for section_7
                    $experts = User::role('employee')
                        ->active()
                        ->select('id', 'first_name', 'last_name', 'username', 'email')
                        ->get();
                    break;

                case 'section_8':
                    $landing_page['status'] = $decodedata['status'] ?? 0;
                    $landing_page['product_id'] = $decodedata['product_id'] ?? [];
                    break;

                case 'section_9':
                    $landing_page['status'] = $decodedata['status'] ?? 0;
                    $landing_page['title_id'] = $decodedata['title_id'] ?? '';
                    $landing_page['subtitle_id'] = $decodedata['subtitle_id'] ?? '';
                    $landing_page['description_id'] = $decodedata['description_id'] ?? '';
                    break;

                case 'section_10':
                    $landing_page['status'] = $decodedata['status'] ?? 0;
                    $landing_page['customer_id'] = $decodedata['customer_id'] ?? 0;
                    break;

                case 'section_11':
                    $landing_page['status'] = $decodedata['status'] ?? 0;
                    $landing_page['title_id'] = $decodedata['title_id'] ?? '';
                    $landing_page['subtitle_id'] = $decodedata['subtitle_id'] ?? '';
                    $landing_page['select_blog_id'] = $decodedata['select_blog_id'] ?? '';
                    break;
            }
        }

        // Pass all data to the Blade view
        $data = view('frontendsetting::sections.' . $tabpage, compact(
            'user_data',
            'tabpage',
            'landing_page',
            'experts'
        ))->render();


        return response()->json(['view' => $data]);
    }


    public function landingpageSettingsUpdates(Request $request)
    {
        $data = $request->all();
        $page = $request->page;
        $type = $request->type;

        $status = isset($data['status']) && $data['status'] == 'on' ? 1 : 0;

        $this->configurations = [
            'section_1' => ['enable_search'],
            'section_2' => ['book_now_id'],
            'section_3' => ['branch_id'],
            'section_4' => ['category_id', 'sub_category_id', 'select_category'],
            'section_5' => ['package_id'],
            'section_6' => ['membership_id'],
            'section_7' => ['expert_id'],
            'section_8' => ['product_id'],
            'section_9' => ['title_id', 'subtitle_id', 'description_id'],
            'section_10' => ['customer_id'],
            'section_11' => ['title_id', 'subtitle_id', 'select_blog_id'],
        ];

        $landing_page_data = [
            $type => $status,
        ];
        if (!empty($data['title'])) {
            $landing_page_data['title'] = $data['title'];
        }
        if (!empty($data['subtitle'])) {
            $landing_page_data['subtitle'] = $data['subtitle'];
        }
        if (!empty($data['description'])) {
            $landing_page_data['description'] = $data['description'];
        }
        foreach ($configurations[$type] ?? [] as $field) {
            $landing_page_data[$field] = isset($data[$field]) ? $data[$field] : [];
        }

        $res = FrontendSetting::updateOrCreate(['id' => $request->id], [
            'type' => 'landing-page-setting',
            'key' => $type,
            'status' => $status,
            'value' => json_encode($landing_page_data),
        ]);


        return redirect()->route('frontend_setting.index', ['page' => $page, 'tabpage' => $type])->withSuccess(__('messages.landing_page_settings') . ' ' . __('messages.updated'));
    }


    public function getLandingLayoutPageConfig(Request $request)
    {
        try {
            $type = $request->type;
            $key = $request->page; // fallback to type if key not provided

            if (!$type) {
                return response()->json([
                    'success' => false,
                    'message' => 'Both "type"  parameters are required.'
                ], 422);
            }
            // Always use both type and key for lookup
            $setting = FrontendSetting::select('id', 'key', 'value', 'status', 'type')
                ->where('type', $type)
                ->where('key', $key)
                ->first();

            if (!$setting) {
                return response()->json([
                    'success' => true,
                    'data' => 'not found setting'
                ]);
            }
            $rawValue = $setting->value;
            $decodedValue = is_string($rawValue) ? json_decode($rawValue, true) : $rawValue;
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid JSON in database: ' . json_last_error_msg()
                ], 500);
            }
            $decodedValue['status'] = isset($decodedValue['status']) ? (int)$decodedValue['status'] : (int)$setting->status;
            if (!isset($decodedValue['expert_id']) || !is_array($decodedValue['expert_id'])) {
                $decodedValue['expert_id'] = [];
            }
            $setting->value = $decodedValue;


            return response()->json([
                'success' => true,
                'data' => $setting
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }



    public function landingPageLayout(Request $request)
    {
        $page = 'landing-page-setting';
        $tabpage = $request->query('tabpage', 'section_1');

        $settings = FrontendSetting::whereIn('key', ['landing_page'])->get();
        $user_data = auth()->user();

        // Initialize landing_page array
        $landing_page = [];

        // Map settings data to landing_page array
        foreach ($settings as $setting) {
            $landing_page[$setting->key] = $setting->value;
        }

        return view('frontendsetting::pages.landingpage', compact('page', 'tabpage'));
    }

    public function footerpagesettings(Request $request)
    {
        try {

            $validated = $request->validate([
                'id' => 'nullable|exists:frontend_settings,id',
            ]);

            $sectionKeys = ['about', 'category', 'quicklinks', 'stayconnected'];

            $values = [];
            foreach ($sectionKeys as $key) {
                $values[$key] = (int) $request->input($key, 0);
            }

            $values['select_category'] = $request->input('select_category', []);

            $values['social_links'] = $request->input('social_links', []);


            $setting = FrontendSetting::updateOrCreate(
                [
                    'type' => 'footer-setting',
                    'key' => 'footer-setting',
                ],
                [
                    'status' => (int) $request->input('status', 0),
                    'value' => json_encode($values),
                ]
            );

            return redirect()->back()->with('success', 'Footer settings saved successfully!');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function footerPage()
    {
        // Fetch footer setting
        $footerSetting = FrontendSetting::where('type', 'footer-setting')
            ->where('key', 'footer-setting')
            ->first();
    
        // ✅ Fetch Quick Links in correct sequence
        $quickLinks = Page::where('status', 1)
            ->orderBy('sequence', 'asc') // 👈 THIS FIXES ORDER
            ->get();
    
        return view('frontendsetting::pages.footer_page', [
            'page' => 'footer-setting',
            'footerSetting' => $footerSetting,
            'quickLinks' => $quickLinks
        ]);
    }

    // ----------------- Helpers -----------------


    // Header settings update toggle
    public function updateHeadingSettings(Request $request)
    {
        $data = $request->all();

        FrontendSetting::updateOrCreate(
            [
                'type' => $data['type'],
                'key' =>  $data['type']
            ],
            [
                'status' => $request->status,
                'value' => json_encode([
                    'selectbranch' => $request->selectbranch == 'on',
                    'home' => $request->home == 'on',
                    'mybooking' => $request->mybooking == 'on',
                    'category' => $request->category == 'on',
                    'service' => $request->service == 'on',
                    'shop' => $request->shop == 'on',
                    'header_offer_section' => $request->header_offer_section == '1',
                    'header_offer_title' => $request->header_offer_title ?? '',
                    'status' => $request->status == '1' || $request->status == 'on',
                    'enable_search' => $request->enable_search,
                    'enable_language' => $request->enable_language,
                    'enable_darknight_mode' => $request->enable_darknight_mode,
                ]),

            ]
        );


        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Header settings saved successfully!'
            ]);
        }

        return redirect()->back()->with('success', 'Header settings saved successfully!');
    }

    // In your controller method that returns this blade view:

    public function saveLandingLayoutPageConfig(Request $request)
    {
        try {
            $type = $request->input('type');
            $page = $request->input('page');
            if ($type === 'section_1') {
                $validator = \Validator::make($request->all(), [
                    'type' => 'required|string',
                    'page' => 'required|string',
                    'section_1' => 'required|in:0,1',
                    'title' => 'required_if:section_1,1|string|nullable',
                    'description' => 'required_if:section_1,1|string|nullable',
                    'enable_search' => 'nullable|in:0,1',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors()->first()
                    ], 422);
                }
                $status = (int) $request->input('section_1', 0);
                $data = [
                    'title' => $request->input('title', ''),
                    'description' => $request->input('description', ''),
                    'enable_search' => (int) $request->input('enable_search', 0),
                    'section_1' => $status,
                    'status' => $status, // Ensure status is present in value array
                ];
                $setting = \Modules\FrontendSetting\Models\FrontendSetting::updateOrCreate(
                    [
                        'type' => 'landing-page-setting',
                        'key' => 'section_1',
                    ],
                    [
                        'status' => $status,
                        'value' => json_encode($data),
                    ]
                );
                return response()->json([
                    'success' => true,
                    'message' => 'Saved successfully.'
                ]);
            }
            if ($type === 'section_2') {
                $validator = \Validator::make($request->all(), [
                    'type' => 'required|string',
                    'page' => 'required|string',
                    'section_2' => 'required|in:0,1',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors()->first()
                    ], 422);
                }
                $status = (int) $request->input('section_2', 0);
                $data = [
                    'section_2' => $status,
                    'status' => $status,
                ];
                $setting = \Modules\FrontendSetting\Models\FrontendSetting::updateOrCreate(
                    [
                        'type' => 'landing-page-setting',
                        'key' => 'section_2',
                    ],
                    [
                        'status' => $status,
                        'value' => json_encode($data),
                    ]
                );
                return response()->json([
                    'success' => true,
                    'message' => 'Saved successfully.'
                ]);
            }
            if ($type === 'section_7') {


                $validator = \Validator::make($request->all(), [
                    'type' => 'required|string',
                    'page' => 'required|string',
                    'status' => 'required|in:0,1',
                    'expert_id' => 'nullable|array',
                    'expert_id.*' => 'nullable|integer|exists:users,id',
                ]);
                if ($validator->fails()) {

                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors()->first()
                    ], 422);
                }


                $status = (int) $request->input('status', 0);
                $expert_id = $status ? (array) $request->input('expert_id', []) : [];
                // Filter to keep only numeric IDs
                $expert_id = array_filter($expert_id, function ($id) {
                    return is_numeric($id);
                });
                $data = [
                    'status' => $status,
                    'expert_id' => array_values($expert_id),
                ];
                $setting = \Modules\FrontendSetting\Models\FrontendSetting::updateOrCreate(
                    [
                        'type' => 'landing-page-setting',
                        'key' => 'section_7',
                    ],
                    [
                        'status' => $status,
                        'value' => json_encode($data),
                    ]
                );
                return response()->json([
                    'success' => true,
                    'message' => 'Saved successfully.'
                ]);
            }
            if ($type === 'section_4') {
                $validator = \Validator::make($request->all(), [
                    'type' => 'required|string',
                    'page' => 'required|string',
                    'status' => 'required|in:0,1',
                    'select_category' => 'nullable|array',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors()->first()
                    ], 422);
                }
                $status = (int) $request->input('status', 0);
                $data = [
                    'section_4' => $status,
                    'status' => $status,
                    'select_category' => $request->input('select_category', []),
                ];
                $setting = \Modules\FrontendSetting\Models\FrontendSetting::updateOrCreate(
                    [
                        'type' => 'landing-page-setting',
                        'key' => 'section_4',
                    ],
                    [
                        'status' => $status,
                        'value' => json_encode($data),
                    ]
                );
                return response()->json([
                    'success' => true,
                    'message' => 'Saved successfully.'
                ]);
            }
            if ($type === 'section_5') {
                $validator = \Validator::make($request->all(), [
                    'type' => 'required|string',
                    'page' => 'required|string',
                    'status' => 'required|in:0,1',
                    'package_ids' => 'nullable|array',
                    'package_ids.*' => 'nullable|integer|exists:packages,id',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors()->first()
                    ], 422);
                }
                $status = (int) $request->input('status', 0);
                $package_ids = $status ? (array) $request->input('package_ids', []) : [];
                // Filter to keep only numeric IDs
                $package_ids = array_filter($package_ids, function ($id) {
                    return is_numeric($id);
                });
                $data = [
                    'status' => $status,
                    'package_ids' => array_values($package_ids),
                ];
                $setting = \Modules\FrontendSetting\Models\FrontendSetting::updateOrCreate(
                    [
                        'type' => 'landing-page-setting',
                        'key' => 'section_5',
                    ],
                    [
                        'status' => $status,
                        'value' => json_encode($data),
                    ]
                );
                return response()->json([
                    'success' => true,
                    'message' => 'Saved successfully.'
                ]);
            }
            if ($type === 'section_8') {
                $validator = \Validator::make($request->all(), [
                    'type' => 'required|string',
                    'page' => 'required|string',
                    'status' => 'required|in:0,1',
                    'product_id' => 'nullable|array',
                    'product_id.*' => 'nullable|integer|exists:products,id',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors()->first()
                    ], 422);
                }
                $status = (int) $request->input('status', 0);
                $product_id = $status ? (array) $request->input('product_id', []) : [];
                // Filter to keep only numeric IDs
                $product_id = array_filter($product_id, function ($id) {
                    return is_numeric($id);
                });
                $data = [
                    'status' => $status,
                    'product_id' => array_values($product_id),
                ];
                $setting = \Modules\FrontendSetting\Models\FrontendSetting::updateOrCreate(
                    [
                        'type' => 'landing-page-setting',
                        'key' => 'section_8',
                    ],
                    [
                        'status' => $status,
                        'value' => json_encode($data),
                    ]
                );
                return response()->json([
                    'success' => true,
                    'message' => 'Saved successfully.'
                ]);
            }
            if ($type === 'section_9') {
                $validator = \Validator::make($request->all(), [
                    'type' => 'required|string',
                    'page' => 'required|string',
                    'status' => 'required|in:0,1',
                    'title_id' => 'nullable|string',
                    'subtitle_id' => 'nullable|string',
                    'description_id' => 'nullable|string',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors()->first()
                    ], 422);
                }
                $status = (int) $request->input('status', 0);
                $data = [
                    'status' => $status,
                    'title_id' => $request->input('title_id', ''),
                    'subtitle_id' => $request->input('subtitle_id', ''),
                    'description_id' => $request->input('description_id', ''),
                ];
                $setting = \Modules\FrontendSetting\Models\FrontendSetting::updateOrCreate(
                    [
                        'type' => 'landing-page-setting',
                        'key' => 'section_9',
                    ],
                    [
                        'status' => $status,
                        'value' => json_encode($data),
                    ]
                );
                return response()->json([
                    'success' => true,
                    'message' => 'Saved successfully.'
                ]);
            }
            if ($type === 'section_10') {
                $validator = \Validator::make($request->all(), [
                    'type' => 'required|string',
                    'page' => 'required|string',
                    'status' => 'required|in:0,1',
                    'customer_id' => 'nullable|integer',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors()->first()
                    ], 422);
                }
                $status = (int) $request->input('status', 0);
                $customer_id = (int) $request->input('customer_id', 0);
                $data = [
                    'status' => $status,
                    'customer_id' => $customer_id,
                ];
                $setting = \Modules\FrontendSetting\Models\FrontendSetting::updateOrCreate(
                    [
                        'type' => 'landing-page-setting',
                        'key' => 'section_10',
                    ],
                    [
                        'status' => $status,
                        'value' => json_encode($data),
                    ]
                );
                return response()->json([
                    'success' => true,
                    'message' => 'Saved successfully.'
                ]);
            }
            if ($type === 'section_11') {
                $validator = \Validator::make($request->all(), [
                    'type' => 'required|string',
                    'page' => 'required|string',
                    'status' => 'required|in:0,1',
                    'title_id' => 'nullable|string',
                    'subtitle_id' => 'nullable|string',
                    'select_blog_id' => 'nullable|string',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors()->first()
                    ], 422);
                }
                $status = (int) $request->input('status', 0);
                $data = [
                    'status' => $status,
                    'title_id' => $request->input('title_id', ''),
                    'subtitle_id' => $request->input('subtitle_id', ''),
                    'select_blog_id' => $request->input('select_blog_id', ''),
                ];
                $setting = \Modules\FrontendSetting\Models\FrontendSetting::updateOrCreate(
                    [
                        'type' => 'landing-page-setting',
                        'key' => 'section_11',
                    ],
                    [
                        'status' => $status,
                        'value' => json_encode($data),
                    ]
                );
                return response()->json([
                    'success' => true,
                    'message' => 'Saved successfully.'
                ]);
            }
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to save.'
            ], 500);
        }
    }



    public function landingLayoutPage1()
    {
        $branches = Branch::all(); // ✅ Fetch all branches from DB
        $tabpage = 'home';

        return view('frontendsetting::landing-page-setting', compact('branches'));
    }


    public function getBranches(Request $request)
    {
        $search = $request->input('q');

        $branches = Branch::where('name', 'like', '%' . $search . '%')
            ->select('id', 'name')
            ->get();

        return response()->json(
            $branches->map(fn($b) => ['id' => $b->id, 'text' => $b->name])
        );
    }

    public function fetchCategoryNames(Request $request)
    {
        if ($request->has('ids')) {
            // Handle preloading selected categories by ID array
            $ids = $request->input('ids');
            return Category::whereIn('id', $ids)
                ->where('status', 1)
                ->select('id', 'name')
                ->get();
        }

        // Handle live search by query term
        $query = $request->input('q');

        $categories = Category::where('status', 1)
            ->where('name', 'like', '%' . $query . '%')
            ->select('id', 'name')
            ->get();

        return response()->json($categories);
    }


    public function getPackages(Request $request)
    {
        $branchId = $request->input('branch_id', 1);

        $packages = Package::where('branch_id', $branchId)
            ->where('status', 1)
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'packages' => $packages
        ]);
    }

    public function getProducts(Request $request)
    {
        $products = Product::select('id', 'name')->get();

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }

    public function editLandingLayout($page)
    {
        $experts = User::role('employee')
            ->active()
            ->select('id', 'first_name', 'last_name', 'username', 'email')
            ->get();

        return view('frontendsetting::sections.section_7', [
            'experts' => $experts,
            'tabpage' => $page,
        ]);
    }
    
}
