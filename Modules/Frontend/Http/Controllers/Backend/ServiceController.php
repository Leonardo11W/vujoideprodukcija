<?php

namespace Modules\Frontend\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DataTables;
use Modules\Service\Models\Service; // Correct Service Model
use Modules\Category\Models\Category; // Include Category Model if needed for future logic
use Illuminate\Support\Facades\View;
use Modules\Employee\Models\BranchEmployee;
use Modules\Employee\Models\Branch;
use DB;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($category = null)
    {

        $category = $category;



        $categories = Category::where('status', 1)
            ->whereNull('parent_id')
            ->whereHas('services', function($q) {
                $q->where('status', 1);
            })
            ->withCount(['services' => function($q) {
                $q->where('status', 1);
            }])
            ->orderBy('services_count', 'desc')
            ->get();

        // Remove it from the list if it already exists
        $categories = $categories->reject(function ($cat) use ($category) {
            return $cat->slug === $category;
        });

        // Fetch the selected category
        $selected = Category::where('slug', $category)
            ->where('status', 1)
            ->whereNull('parent_id')
            ->withCount(['services' => function($q) {
                $q->where('status', 1);
            }])
            ->first();

        // Insert at second position
        if ($selected) {
            $categories->prepend($selected);
        }

        $query = Service::query()
            ->with(['employee', 'branches'])
            ->where('status', 1);

        if (session()->has('selected_branch_id')) {
            $branchId = session('selected_branch_id');

            $barnchEmployee = BranchEmployee::where('branch_id', $branchId)->get();

            $query->whereHas('employee', function ($query) use ($barnchEmployee) {
                $query->whereIn('employee_id', $barnchEmployee->pluck('employee_id'));
            });
        }

        // Count all services with status 1, regardless of employee assignment
        $allServicesCount = Service::where('status', 1)->count();


        return view('frontend::service', compact('categories', 'allServicesCount', 'category'));
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
    public function show($branchId)
    {
        $branch = Branch::findOrFail($branchId);
        $services = $branch->services; // or your actual relationship
        return view('frontend::branch-details', compact('branch', 'services'));
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

    public function getServiceCardsData(Request $request)
    {
        // $query = Service::query()
        //     ->select('id', 'name', 'category_id', 'default_price', 'description', 'duration_min') // Added duration_min
        //     ->where('status', 1)
        //     ->withCount(['employee as staff_count', 'branches as branch_count']); // Eager load counts

        $branch_id = session('selected_branch_id');


        if ($branch_id) {
            $employee_id = BranchEmployee::where('branch_id', $branch_id)
                ->pluck('employee_id')
                ->toArray();
        } else {
            $employee_id = BranchEmployee::pluck('employee_id')->toArray();
        }


        $query = Service::query()
            ->select('id', 'name', 'category_id', 'default_price', 'description', 'duration_min')
            ->where('status', 1)
            ->with(['branches' => function($q) use ($branch_id) {
                if ($branch_id) {
                    $q->where('branch_id', $branch_id);
                }
            }])
            ->withCount([
                'employee as staff_count' => function ($q) use ($employee_id) {
                    $q->whereIn('employee_id', $employee_id);
                },
                'branches as branch_count'
            ]);

        // Debug: Log the query to see what's being filtered
        \Log::info('Service Query Debug', [
            'branch_id' => $branch_id,
            'employee_ids' => $employee_id,
            'total_services_before_filter' => Service::where('status', 1)->count(),
            'services_with_employees' => Service::where('status', 1)->whereHas('employee', function($q) use ($employee_id) {
                $q->whereIn('employee_id', $employee_id);
            })->count()
        ]);


        // Apply category filter if provided
        if ($request->has('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        // Ensure we have enough services by removing overly restrictive employee filtering
        // The service display should not be blocked by staff assignment at this stage
        // staff_count will still show the number of staff assigned in the specific branch where applicable

        // Remove the commented out employee filtering that was causing issues
        // The employee filtering is now handled above with better logic

        // Apply search
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', '%' . $searchValue . '%')
                    ->orWhere('description', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('category', function ($q) use ($searchValue) {
                        $q->where('name', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        // Apply sort filter
        if ($request->has('sort_filter') && !empty($request->sort_filter)) {
            switch ($request->sort_filter) {
                case 'newest':
                    // Only show services from last 10 days
                    $query->whereDate('created_at', '>=', now()->subDays(10))
                        ->orderBy('created_at', 'desc');
                    break;
                case 'trending':
                    $query->where('name', '>=', 10);
                    $query->orderBy('name', 'desc');
                    break;
            }
        }

        // Log the query here before DataTables processes it
        return DataTables::of($query)
            ->addColumn('card', function ($service) {
                // Get branch-specific price and duration if available
                $branchId = session('selected_branch_id');
                if ($branchId && $service->branches->isNotEmpty()) {
                    $branchService = $service->branches->first(); // Eager load is already filtered
                    
                    // Prioritize branch price if it exists (even if 0, though usually positive)
                    if ($branchService && !is_null($branchService->service_price)) {
                        $service->price = $branchService->service_price;
                    } else {
                        $service->price = $service->default_price;
                    }

                    // Prioritize branch duration if it exists
                    if ($branchService && !is_null($branchService->duration_min)) {
                        $service->duration_min = $branchService->duration_min;
                    }

                     \Log::info("Service ID: {$service->id}, Branch: {$branchId}, Branch Price: " . ($branchService->service_price ?? 'N/A') . ", Final Price: {$service->price}");

                } else {
                    $service->price = $service->default_price;
                }
                // Add duration, staff, and branch info for display
                $service->duration_text = $service->duration_min ? $service->duration_min . ' ' . __('frontend.min') : null;
                $service->staff_info = $service->staff_count > 0 ? $service->staff_count . '+ ' . __('frontend.staff') : null;
                $service->branch_info = $service->branch_count > 0 ? $service->branch_count . ' ' . ($service->branch_count > 1 ? __('frontend.branches') : __('frontend.branch')) : null;

                return View::make('frontend::components.card.service_card', compact('service'))->render();
            })
            ->rawColumns(['card'])
            ->make(true);
    }
}
