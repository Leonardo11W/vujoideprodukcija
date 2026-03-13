<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Currency\Models\Currency;
use Modules\Page\Models\Page;
use Modules\Page\Transformers\PageResource;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\URL;

class SettingController extends Controller
{
    public function appConfiguraton(Request $request)
    {
        $settings = Setting::all()->pluck('val', 'name');
        $pages = Page::where('status', 1)->get();
        $currencies = Currency::all();
        $response = [];

        // Define the specific names you want to include
        $specificNames = ['app_name', 'footer_text', 'primary', 'razorpay_secretkey', 'razorpay_publickey', 'stripe_secretkey', 'stripe_publickey', 'paystack_secretkey', 'paystack_publickey', 'paypal_secretkey', 'paypal_clientid', 'flutterwave_secretkey', 'flutterwave_publickey','razorpay_secretkey', 'razorpay_publickey','cinet_clientid','cinet_apikey','cinet_secretkey','sadad_clientid','sadad_secretkey','sadad_domain','airtelmoney_is_status','airtelmoney_clientid','airtelmoney_secretkey','phonepay_is_status','phonepay_appid','phonepay_merchentid','phonepay_saltid','phonepay_saltkey','midtrans_is_status','midtrans_clientid', 'google_maps_key', 'helpline_number', 'copyright', 'inquriy_email', 'site_description', 'customer_app_play_store', 'customer_app_app_store', 'isForceUpdate', 'version_code'];
        foreach ($settings as $name => $value) {
            if (in_array($name, $specificNames)) {
                if (strpos($name, 'razorpay_') === 0 && $request->is_authenticated == 1 && $settings['razor_payment_method'] == 1) {
                    $nestedKey = 'razor_pay';
                    $nestedName = str_replace('', 'razorpay_', $name);
                    if (! isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                } elseif (strpos($name, 'stripe_') === 0 && $request->is_authenticated == 1 && $settings['str_payment_method'] == 1) {
                    $nestedKey = 'stripe_pay';
                    $nestedName = str_replace('', 'stripe_', $name);
                    if (! isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                } elseif (strpos($name, 'paystack_') === 0 && $request->is_authenticated == 1 && $settings['paystack_payment_method'] == 1) {
                    $nestedKey = 'paystack_pay';
                    $nestedName = str_replace('', 'paystack_', $name);
                    if (! isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                } elseif (strpos($name, 'paypal_') === 0 && $request->is_authenticated == 1 && $settings['paypal_payment_method'] == 1) {
                    $nestedKey = 'paypal_pay';
                    $nestedName = str_replace('', 'paypal_', $name);
                    if (! isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                } elseif (strpos($name, 'flutterwave_') === 0 && $request->is_authenticated == 1 && $settings['flutterwave_payment_method'] == 1) {
                    $nestedKey = 'flutterwave_pay';
                    $nestedName = str_replace('', 'flutterwave_', $name);
                    if (! isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'cinet_') === 0 && $request->is_authenticated == 1 && $settings['cinet_payment_method'] == 1) {
                    $nestedKey = 'cinet_pay';
                    $nestedName = str_replace('', 'cinet_', $name);
                    if (! isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'sadad_') === 0 && $request->is_authenticated == 1 && $settings['sadad_payment_method'] == 1) {
                    $nestedKey = 'sadad_pay';
                    $nestedName = str_replace('', 'sadad_', $name);
                    if (! isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'airtelmoney_') === 0 && $request->is_authenticated == 1 && $settings['airtelmoney_payment_method'] == 1) {
                    $nestedKey = 'airtelmoney_pay';
                    $nestedName = str_replace('', 'airtelmoney_', $name);
                    if (! isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'phonepay_') === 0 && $request->is_authenticated == 1 && $settings['phonepay_payment_method'] == 1) {
                    $nestedKey = 'phonepay_pay';
                    $nestedName = str_replace('', 'phonepay_', $name);
                    if (! isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'midtrans_') === 0 && $request->is_authenticated == 1 && $settings['midtrans_payment_method'] == 1) {
                    $nestedKey = 'midtrans_pay';
                    $nestedName = str_replace('', 'midtrans_', $name);
                    if (! isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }

                if (! strpos($name, 'stripe_') === 0) {
                    $response[$name] = $value;
                } elseif (! strpos($name, 'razorpay_') === 0) {
                    $response[$name] = $value;
                }elseif (! strpos($name, 'paystack_') === 0) {
                    $response[$name] = $value;
                }elseif (! strpos($name, 'paypal_') === 0) {
                    $response[$name] = $value;
                }elseif (! strpos($name, 'flutterwave_') === 0) {
                    $response[$name] = $value;
                }elseif (! strpos($name, 'cinet_') === 0) {
                    $response[$name] = $value;
                }elseif (! strpos($name, 'sadad_') === 0) {
                    $response[$name] = $value;
                }elseif (! strpos($name, 'airtelmoney_') === 0) {
                    $response[$name] = $value;
                }elseif (! strpos($name, 'phonepay_') === 0) {
                    $response[$name] = $value;
                }elseif (! strpos($name, 'midtrans_') === 0) {
                    $response[$name] = $value;
                }
            }
        }
        // Fetch currency data
        $currencies = Currency::all();

        $currencyData = null;
        if ($currencies->isNotEmpty()) {
            $currency = $currencies->where('is_primary', 1)->first();
            $currencyData = [
                'currency_name' => $currency->currency_name,
                'currency_symbol' => $currency->currency_symbol,
                'currency_code' => $currency->currency_code,
                'currency_position' => $currency->currency_position,
                'no_of_decimal' => $currency->no_of_decimal,
                'thousand_separator' => $currency->thousand_separator,
                'decimal_separator' => $currency->decimal_separator,
            ];
        }

        if (isset($settings['isForceUpdate']) && isset($settings['version_code'])) {
            $response['isForceUpdate'] = intval($settings['isForceUpdate']);

            $response['version_code'] = (string) $settings['version_code'];
        } else {
            $response['isForceUpdate'] = 0;

            $response['version_code'] = '0';
        }
        if ($request->user_id) {
            $user = User::withTrashed()->find($request->user_id);

            if ($user && $user->trashed()) {
                $response['is_user_authorized'] = false;
            } elseif ($user) {
                $response['is_user_authorized'] = true;
            } else {
                $response['is_user_authorized'] = false;
            }
        } else {
            $response['is_user_authorized'] = false;
        }

        $response['pages'] = PageResource::collection($pages);

        $response['currency'] = $currencyData;
        $response['google_login_status'] = 'false';
        $response['apple_login_status'] = 'false';
        $response['otp_login_status'] = 'false';
        $response['site_description'] = $settings['site_description'] ?? null;
        // Add locale language to the response
        $response['application_language'] =  setting('default_language') ?? app()->getLocale();
        $response['is_demo_login'] = isset($settings['is_demo_login']) ? (($settings['is_demo_login'] != null) ? true : false ) : true;
        $response['status'] = true;

        return response()->json($response);
    }

    /**
     * Manager App Configurations
     * Endpoint: manager-app-configurations?is_authenticated=1
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function managerAppConfigurations(Request $request)
    {
        try {
            $isAuthenticated = $request->input('is_authenticated', 0);
            $isManager = $request->boolean('is_manager');
            $settings = Setting::all()->pluck('val', 'name');
            
            // Get pages URLs
            $privacyPage = Page::where('slug', 'privacy-policy')->where('status', 1)->first();
            $termsPage = Page::where('slug', 'terms-conditions')->where('status', 1)->first();
            $aboutPage = Page::where('slug', 'about-us')->where('status', 1)->first();
            $helpPage = Page::where('slug', 'help')->where('status', 1)->first();
            
            $pagesUrl = [
                'privacy_policy' => $privacyPage ? URL::to('/page/' . $privacyPage->slug) : '',
                'terms_and_conditions' => $termsPage ? URL::to('/page/' . $termsPage->slug) : '',
                'about_us' => $aboutPage ? URL::to('/page/' . $aboutPage->slug) : '',
                'help_and_support' => $helpPage ? URL::to('/page/' . $helpPage->slug) : '',
                'playstore_url' => $settings['customer_app_play_store'] ?? '',
                'appstore_url' => $settings['customer_app_app_store'] ?? '',
            ];
            
            // Get currency data
            $currency = Currency::where('is_primary', 1)->first();
            $currencyData = [
                'currency_symbol' => $currency->currency_symbol ?? '',
                'currency_name' => $currency->currency_name ?? '',
                'is_currency_prefix' => ($currency && in_array($currency->currency_position, ['left', 'left_with_space'])) ? true : false,
            ];
            
            // Get staff permissions (employee role)
            $staffRole = Role::where('name', 'employee')->first();
            $staffPermissions = [
                'can_view_booking' => $staffRole ? $staffRole->hasPermissionTo('view_booking') : false,
                'can_add_booking' => $staffRole ? $staffRole->hasPermissionTo('add_booking') : false,
                'can_edit_booking' => $staffRole ? $staffRole->hasPermissionTo('edit_booking') : false,
                'can_delete_booking' => $staffRole ? $staffRole->hasPermissionTo('delete_booking') : false,
                'can_view_services' => $staffRole ? $staffRole->hasPermissionTo('view_service') : false,
                'can_add_services' => $staffRole ? $staffRole->hasPermissionTo('add_service') : false,
                'can_edit_services' => $staffRole ? $staffRole->hasPermissionTo('edit_service') : false,
                'can_delete_services' => $staffRole ? $staffRole->hasPermissionTo('delete_service') : false,
                'can_view_earnings' => $staffRole ? $staffRole->hasPermissionTo('view_earning') : false,
                'can_view_reviews' => $staffRole ? $staffRole->hasPermissionTo('view_review') : false,
                'can_view_reports' => $staffRole ? $staffRole->hasPermissionTo('view_reports') : false,
            ];
            
            // Get manager permissions
            $managerRole = Role::where('name', 'manager')->first();
            $managerPermissions = [
                'can_view_branch' => $managerRole ? $managerRole->hasPermissionTo('view_branch') : false,
                'can_view_booking' => $managerRole ? $managerRole->hasPermissionTo('view_booking') : false,
                'can_add_booking' => $managerRole ? $managerRole->hasPermissionTo('add_booking') : false,
                'can_edit_booking' => $managerRole ? $managerRole->hasPermissionTo('edit_booking') : false,
                'can_delete_booking' => $managerRole ? $managerRole->hasPermissionTo('delete_booking') : false,
                'can_view_services' => $managerRole ? $managerRole->hasPermissionTo('view_service') : false,
                'can_add_services' => $managerRole ? $managerRole->hasPermissionTo('add_service') : false,
                'can_edit_services' => $managerRole ? $managerRole->hasPermissionTo('edit_service') : false,
                'can_delete_services' => $managerRole ? $managerRole->hasPermissionTo('delete_service') : false,
                'can_view_categories' => $managerRole ? $managerRole->hasPermissionTo('view_category') : false,
                'can_view_sub_category' => $managerRole ? $managerRole->hasPermissionTo('view_subcategory') : false,
                'can_view_staff' => $managerRole ? $managerRole->hasPermissionTo('view_staff') : false,
                'can_add_staff' => $managerRole ? $managerRole->hasPermissionTo('add_staff') : false,
                'can_edit_staff' => $managerRole ? $managerRole->hasPermissionTo('edit_staff') : false,
                'can_delete_staff' => $managerRole ? $managerRole->hasPermissionTo('delete_staff') : false,
                'can_view_customer' => $managerRole ? $managerRole->hasPermissionTo('view_customer') : false,
            ];
            
            // is_manager true  -> staff_permissions empty, manager_permissions with data
            // is_manager false -> staff_permissions with data, manager_permissions empty
            $staffPermissionsData = $isManager ? (object) [] : $staffPermissions;
            $managerPermissionsData = $isManager ? $managerPermissions : (object) [];

            $data = [
                'pages_url' => $pagesUrl,
                'currency_data' => $currencyData,
                'staff_permissions' => $staffPermissionsData,
                'manager_permissions' => $managerPermissionsData,
            ];
            
            return response()->json([
                'status' => true,
                'message' => 'App Configurations fetched successfully.',
                'data' => $data,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch app configurations.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
