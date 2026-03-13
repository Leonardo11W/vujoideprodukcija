<?php

namespace Modules\Frontend\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use App\Models\Branch;
use Modules\Employee\Models\EmployeeRating;
use Modules\Employee\Models\BranchEmployee;
use Yajra\DataTables\DataTables;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branches = Branch::all();
        return view('frontend::branch', compact('branches'));
    }

    /**
     * Handle branch selection via AJAX
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function selectBranch(Request $request)
    {
        \Log::info('Branch selection request received', [
            'request_data' => $request->all(),
            'headers' => $request->headers->all(),
            'method' => $request->method()
        ]);

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        try {
            Session::put('selected_branch_id', $request->branch_id);

            Session::put('selected_branch', $request->branch_id);

            return response()->json([
                'success' => true,
                'message' => 'Branch selected successfully',
                'branch_id' => $request->branch_id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to select branch: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function BranchDetail($id, Request $request)
    {
        $branch = Branch::with([
            'address.country_data',
            'address.state_data',
            'address.city_data',
            'branchEmployee.employee',
            'gallerys',
            'bussinesshours' => function($query) {
                $query->orderByRaw("CASE
                    WHEN day = 'monday' THEN 1
                    WHEN day = 'tuesday' THEN 2
                    WHEN day = 'wednesday' THEN 3
                    WHEN day = 'thursday' THEN 4
                    WHEN day = 'friday' THEN 5
                    WHEN day = 'saturday' THEN 6
                    WHEN day = 'sunday' THEN 7
                    ELSE 8
                END");
            },
            'services' => function($query) {
                $query->where('status', 1);
            }
        ])->findOrFail($id);

        $perPage = 6;

        // Get employee IDs for this branch
        $employeeIds = BranchEmployee::where('branch_id', $id)->pluck('employee_id');

        // Calculate dynamic rating and total reviews
        $averageRating = EmployeeRating::whereIn('employee_id', $employeeIds)->avg('rating');
        $totalReviews = EmployeeRating::whereIn('employee_id', $employeeIds)->count();

        // Add dynamic rating data to branch
        $branch->rating = $averageRating ? round($averageRating, 1) : 0;
        $branch->total_review = $totalReviews;

        // Paginate services (pivot data including service_price is loaded via Branch->services() relationship)
        $services = $branch->services()->where('status', 1)->paginate($perPage, ['*'], 'services_page');

        // Get reviews with user data
        $reviews = EmployeeRating::with(['user', 'employee'])
            ->whereIn('employee_id', $employeeIds)
            ->whereNotNull('user_id') // Only get reviews that have a user
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage, ['*'], 'reviews_page');

        return view('frontend::branch-details', compact('branch', 'services', 'reviews'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('frontend::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $branch = Branch::findOrFail($id); // Fetch by ID or fail
        return view('frontend::branch-details', compact('branch'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('frontend::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }

    // public function index1()
    // {
    //      $branches = Branch::all();
    //     return view('frontend::branch', compact('branches'));
    // }

    // /**
    //  * Display the details of a single branch.
    //  */
    // public function show1($id)
    // {
    //     $branch = Branch::findOrFail($id); // Fetch by ID or fail
    //     return view('frontend::branch-details', compact('branch'));
    // }

    /**
     * Yajra DataTable for branches (4 per page)
     */
    public function branchesData(Request $request)
    {
        $query = Branch::with(['address.city_data', 'address.state_data', 'address.country_data'])
            ->where('status', 1);
        
        return DataTables::of($query)
            ->addColumn('card', function ($branch) {
                return view('frontend::components.card.branch_card', compact('branch'))->render();
            })
            ->addColumn('name', function ($branch) {
                return $branch->name;
            })
            ->filterColumn('name', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->where(function ($q) use ($keyword) {
                        // Search by branch name
                        $q->where('name', 'like', "%{$keyword}%")
                          // Search by city name
                          ->orWhereHas('address', function ($addressQuery) use ($keyword) {
                              $addressQuery->where('city', 'like', "%{$keyword}%")
                                         ->orWhereHas('city_data', function ($cityQuery) use ($keyword) {
                                             $cityQuery->where('name', 'like', "%{$keyword}%");
                                         });
                          })
                          // Search by country name
                          ->orWhereHas('address', function ($addressQuery) use ($keyword) {
                              $addressQuery->where('country', 'like', "%{$keyword}%")
                                         ->orWhereHas('country_data', function ($countryQuery) use ($keyword) {
                                             $countryQuery->where('name', 'like', "%{$keyword}%");
                                         });
                          })
                          // Search by state name
                          ->orWhereHas('address', function ($addressQuery) use ($keyword) {
                              $addressQuery->where('state', 'like', "%{$keyword}%")
                                         ->orWhereHas('state_data', function ($stateQuery) use ($keyword) {
                                             $stateQuery->where('name', 'like', "%{$keyword}%");
                                         });
                          })
                          // Search by address line
                          ->orWhereHas('address', function ($addressQuery) use ($keyword) {
                              $addressQuery->where('address_line_1', 'like', "%{$keyword}%")
                                         ->orWhere('address_line_2', 'like', "%{$keyword}%");
                          });
                    });
                }
            })
            ->rawColumns(['card'])
            ->make(true);
    }
}
