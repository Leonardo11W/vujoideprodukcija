<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Constant\Models\Constant;
use Modules\Service\Models\Service;

class SearchController extends Controller
{
    public function get_search_data(Request $request)
    {
        $is_multiple = isset($request->multiple) ? explode(',', $request->multiple) : null;
        if (isset($is_multiple) && count($is_multiple)) {
            $multiplItems = [];
            foreach ($is_multiple as $key => $value) {
                $multiplItems[$key] = $this->getData(collect($request[$value]));
            }

            return response()->json(['status' => 'true', 'results' => $multiplItems]);
        } else {
            return response()->json(['status' => 'true', 'results' => $this->getData($request->all())]);
        }
    }

    // case 'users':
    // select('id as $key','name as $value')
    // select(\DB::raw("value $key,label $value"))
    // if($keyword != ''){
    //   $items->where('category_name', 'LIKE', $keyword.'%');
    // }
    //   break;
    protected function getData($request)
    {
        $items = [];

        $type = $request['type'] ?? null;
        $sub_type = $request['sub_type'] ?? null;

        $keyword = $request['q'] ?? null;

        switch ($type) {
            case 'employees':
                // Log what we're receiving
                \Log::info('SearchController employees search', [
                    'all_request' => $request,
                    'type' => $type,
                    'keyword_q' => $keyword,
                    'branch_id' => $request['branch_id'] ?? 'not set'
                ]);
                
                // Filter by roles that can be assigned to bookings
                $items = User::role(['employee', 'manager'])->select('id', \DB::raw("CONCAT(first_name,' ',last_name) AS text"));
                
                $authUser = auth()->user();
                // For employees (staff), only show their own data
                if ($authUser && $authUser->hasRole('employee') && !$authUser->hasRole('admin') && !$authUser->hasRole('manager')) {
                    $items->where('users.id', $authUser->id);
                } else {
                $branch_id = $request['branch_id'] ?? session('selected_branch');
                if ($branch_id) {
                    $items->whereHas('branches', function($q) use ($branch_id) {
                        $q->where('branch_id', $branch_id);
                    });
                    }
                }

                if ($keyword != '') {
                    \Log::info('Applying keyword filter', ['keyword' => $keyword]);
                    $items->where(function($q) use ($keyword) {
                        $q->where('first_name', 'LIKE', '%'.$keyword.'%')
                          ->orWhere('last_name', 'LIKE', '%'.$keyword.'%')
                          ->orWhere(\DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', '%'.$keyword.'%');
                    });
                } else {
                    \Log::info('NO keyword filter - keyword is empty');
                }
                $items = $items->limit(50)->get();
                \Log::info('Employees search results', ['count' => $items->count()]);
                break;
            case 'customers':
                $items = User::role('user')->select('id', \DB::raw("CONCAT(first_name,' ',last_name) AS text"));
                if ($keyword != '') {
                    $items->where(\DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', '%'.$keyword.'%');
                }
                $items = $items->limit(50)->get();
                break;
            case 'services':
                $items = Service::select('id', 'name as text');

                $branch_id = $request['branch_id'] ?? session('selected_branch');

                // If an employee is logged in (not manager) OR a manager is in My Work mode,
                // only show services assigned to that logged-in user.
                $authUser = auth()->user();
                $isManager = $authUser && $authUser->hasRole('manager');
                $isEmployee = $authUser && $authUser->hasRole('employee');
                $isManagerMyWork = $isManager && session('my_work_mode', false);
                $restrictToEmployee = ($isEmployee && ! $isManager) || $isManagerMyWork;

                if ($restrictToEmployee && $authUser) {
                    $employeeId = $authUser->id;

                    // Limit services to those explicitly assigned to this employee
                    $items->whereHas('employee', function ($q) use ($employeeId) {
                        $q->where('employee_id', $employeeId);
                    });
                }

                if ($branch_id) {
                    $items->whereHas('branches', function ($q) use ($branch_id) {
                        $q->where('branch_id', $branch_id);
                    });
                }

                if ($keyword != '') {
                    $items->where('name', 'LIKE', '%'.$keyword.'%');
                }

                $items = $items->limit(50)->get();
                break;
                case 'earning_payment_method':
                    $query = Constant::getAllConstant()
                        ->where('type', 'EARNING_PAYMENT_TYPE');
                    foreach ($query as $key => $data) {
                        if ($data->name === 'Cash' || $data->name === 'Wallet') { 
                            if ($keyword != '') {
                                if (strpos($data->name, str_replace(' ', '_', strtolower($keyword))) !== false) {
                                    $items[] = [
                                        'id' => $data->name,
                                        'text' => $data->value,
                                    ];
                                }
                            } else {
                                $items[] = [
                                    'id' => $data->name,
                                    'text' => $data->value,
                                ];
                            }
                        }
                    }
                break;

            case 'booking_status':
                $query = Constant::getAllConstant()
                    ->where('type', 'BOOKING_STATUS');
                foreach ($query as $key => $data) {
                    if ($keyword != '') {
                        if (strpos($data->name, str_replace(' ', '_', strtolower($keyword))) !== false) {
                            $items[] = [
                                'id' => $data->name,
                                'text' => $data->value,
                            ];
                        }
                    } else {
                        $items[] = [
                            'id' => $data->name,
                            'text' => $data->value,
                        ];
                    }
                }
                break;

            case 'time_zone':
                $items = timeZoneList();

                // foreach ($items as $k => $v) {

                //    if($value !=''){
                //         if (strpos($v, $value) !== false) {

                //         }else{
                //              unset($items[$k]);
                //         }
                //    }
                // }

                $data = [];
                $i = 0;
                foreach ($items as $key => $row) {
                    $data[$i] = [
                        'id' => $key,
                        'text' => $row,
                    ];

                    $i++;
                }

                $items = $data;

                break;

            case 'additional_permissions':
                $query = Constant::getAllConstant()
                    ->where('type', 'additional_permissions');
                foreach ($query as $key => $data) {
                    if ($keyword != '') {
                        if (strpos($data->name, str_replace(' ', '_', strtolower($keyword))) !== false) {
                            $items[] = [
                                'id' => $data->name,
                                'text' => $data->value,
                            ];
                        }
                    } else {
                        $items[] = [
                            'id' => $data->name,
                            'text' => $data->value,
                        ];
                    }
                }

                break;

            case 'constant':
                $query = Constant::getAllConstant()->where('type', $sub_type);
                foreach ($query as $key => $data) {
                    if ($sub_type === 'ORDER_STATUS' && strcasecmp($data->name, 'pending') === 0) {
                        continue;
                    }

                    if ($keyword != '') {
                        if (strpos($data->name, str_replace(' ', '_', strtolower($keyword))) !== false) {
                            $items[] = [
                                'id' => $data->name,
                                'text' => $data->value,
                            ];
                        }
                    } else {
                        $items[] = [
                            'id' => $data->name,
                            'text' => $data->value,
                        ];
                    }
                }

                break;

            case 'role':
                $query = Role::all();
                foreach ($query as $key => $data) {
                    if ($keyword != '') {
                        if (strpos($data->name, str_replace(' ', '_', strtolower($keyword))) !== false) {
                            $items[] = [
                                'id' => $data->id,
                                'text' => $data->name,
                            ];
                        }
                    } else {
                        $items[] = [
                            'id' => $data->id,
                            'text' => $data->name,
                        ];
                    }
                }

                break;
        }

        return $items;
    }
}
