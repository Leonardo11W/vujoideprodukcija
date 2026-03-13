<?php

namespace Modules\Frontend\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Yajra\DataTables\Facades\DataTables;
use Modules\Employee\Models\EmployeeRating;
use Modules\Employee\Models\BranchEmployee;
use App\Models\User;

class ExpertController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get employee IDs from branch_employee table for the selected branch
        $query = User::role('employee')->where('status', 1);
        
        if (session()->has('selected_branch_id')) {
            $branchId = session('selected_branch_id');
            $employeeIds = \Modules\Employee\Models\BranchEmployee::where('branch_id', $branchId)
                ->pluck('employee_id');
            
            $query->whereIn('id', $employeeIds);
        }
        
        $experts = $query->with(['media', 'profile', 'services.service.category'])->get();
        
        return view('frontend::expert', compact('experts'));
    }

    public function expertDetail($id)
    {
        // Fetch the expert user data by ID
        $expert = User::where('id', $id)
            ->where('status', 1)
            ->where('is_banned', 0)
            ->first();

        // Check if expert exists
        if (!$expert) {
            abort(404, 'Expert not found');
        }

        // Check if expert belongs to the selected branch
        if (session()->has('selected_branch_id')) {
            $branchId = session('selected_branch_id');
            $isExpertInBranch = BranchEmployee::where('branch_id', $branchId)
                ->where('employee_id', $id)
                ->exists();
            
            if (!$isExpertInBranch) {
                abort(404, 'Expert not found in the selected branch');
            }
        }

        // DOB ko properly format karo
        $expert->formatted_dob = null;
        if (
            !empty($expert->date_of_birth) &&
            $expert->date_of_birth !== '0000-00-00' &&
            $expert->date_of_birth !== '0000-00-00 00:00:00'
        ) {
            try {
                // Direct Carbon parse karo
                $expert->formatted_dob = \Carbon\Carbon::parse($expert->date_of_birth)->format('M d, Y');
            } catch (\Exception $e) {
                // Agar parse nahi ho raha to raw value try karo
                $expert->formatted_dob = date('M d, Y', strtotime($expert->date_of_birth));
            }
        }

        // Fetch ratings and reviews for this specific employee/expert
        $allRatings = EmployeeRating::where('employee_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get first 5 ratings for initial display (changed from 6 to 5)
        $ratings = $allRatings->take(5);

        // Calculate average rating for this specific employee
        $averageRating = $allRatings->count() > 0 ? round($allRatings->avg('rating'), 1) : 0;

        // Calculate customer satisfaction percentage
        $satisfiedCustomers = $allRatings->where('rating', '>=', 4)->count();
        $totalCustomers = $allRatings->count();
        $customerSatisfaction = $totalCustomers > 0 ? round(($satisfiedCustomers / $totalCustomers) * 100) : 0;

        // Check if we need to show "View All" button (changed condition from 6 to 5)
        $totalRatings = $allRatings->count();

        // Get dynamic booking count
        $totalBookings = $expert->employeeBooking()->count();

        // Get services count
        $totalServices = $expert->services()->count();

        // Calculate experience from created_at to now
        $joinedDate = \Carbon\Carbon::parse($expert->created_at);
        $diff = $joinedDate->diff(\Carbon\Carbon::now());
        
        $experienceText = '';
        if ($diff->y > 0) {
            $experienceText = $diff->y . ' ' . ($diff->y > 1 ? __('frontend.years') : __('frontend.year'));
        } elseif ($diff->m > 0) {
            $experienceText = $diff->m . ' ' . ($diff->m > 1 ? __('frontend.months') : __('frontend.Month'));
        } else {
            $experienceText = $diff->d . ' ' . ($diff->d > 1 ? __('frontend.days') : __('frontend.day'));
        }

        return view('frontend::expert-details', compact(
            'expert',
            'ratings',
            'averageRating',
            'totalRatings',
            'customerSatisfaction',
            'totalBookings',
            'totalServices',
            'experienceText'
        ));
    }

    /**
     * Show all reviews for an expert with DataTable
     */
    public function expertReviews($id)
    {
        // Fetch the expert user data by ID
        $expert = User::where('id', $id)
            ->where('status', 1)
            ->where('is_banned', 0)
            ->first();

        // Check if expert exists
        if (!$expert) {
            abort(404, 'Expert not found');
        }

        // Check if expert belongs to the selected branch
        if (session()->has('selected_branch_id')) {
            $branchId = session('selected_branch_id');
            $isExpertInBranch = BranchEmployee::where('branch_id', $branchId)
                ->where('employee_id', $id)
                ->exists();
            
            if (!$isExpertInBranch) {
                abort(404, 'Expert not found in the selected branch');
            }
        }

        // Get total ratings count
        $totalRatings = EmployeeRating::where('employee_id', $id)->count();

        // Calculate average rating
        $averageRating = EmployeeRating::where('employee_id', $id)->avg('rating');
        $averageRating = $averageRating ? round($averageRating, 1) : 0;

        return view('frontend::expert-reviews', compact('expert', 'totalRatings', 'averageRating'));
    }

    /**
     * Get expert reviews data for DataTable
     */
    public function getExpertReviewsData($id)
    {
        // Verify expert exists
        $expert = User::where('id', $id)
            ->where('status', 1)
            ->where('is_banned', 0)
            ->first();

        if (!$expert) {
            return response()->json(['error' => 'Expert not found'], 404);
        }

        // Check if expert belongs to the selected branch
        if (session()->has('selected_branch_id')) {
            $branchId = session('selected_branch_id');
            $isExpertInBranch = BranchEmployee::where('branch_id', $branchId)
                ->where('employee_id', $id)
                ->exists();
            
            if (!$isExpertInBranch) {
                return response()->json(['error' => 'Expert not found in the selected branch'], 404);
            }
        }

        // Fetch all ratings with user relationship
        $ratings = EmployeeRating::where('employee_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Format data for DataTable
        $data = [];
        foreach ($ratings as $rating) {
            $stars = '';
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= $rating->rating) {
                    $stars .= '<i class="ph-fill ph-star text-warning"></i>';
                } else {
                    $stars .= '<i class="ph ph-star text-warning"></i>';
                }
            }

            $data[] = [
                'user_name' => $rating->user->full_name ?? 'Anonymous',
                'user_image' => $rating->user->profile_image ?? asset('img/frontend/export-image.jpg'),
                'rating' => $rating->rating,
                'stars' => $stars,
                'review_msg' => $rating->review_msg ?? 'No review message provided.',
                'created_at' => $rating->created_at->format('M d, Y'),
                'created_at_sort' => $rating->created_at->timestamp
            ];
        }

        return response()->json([
            'data' => $data
        ]);
    }

     
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
        return view('frontend::show');
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

    /**
     * DataTable server-side data for experts
     */
    public function expertsData(Request $request)
    {
        $query = User::role('employee')->where('status', 1);
        if (session()->has('selected_branch_id')) {
            $branchId = session('selected_branch_id');
            $employeeIds = \Modules\Employee\Models\BranchEmployee::where('branch_id', $branchId)
                ->pluck('employee_id');
            $query->whereIn('id', $employeeIds);
        }
        // Do NOT limit to 6 random experts here; let DataTable handle pagination
        $query = $query->with(['media', 'profile', 'services.service.category']);
        return \Yajra\DataTables\Facades\DataTables::of($query)
            ->addColumn('card', function ($expert) {
                // Calculate average rating for this expert
                $expert->avg_rating = round(
                    \Modules\Employee\Models\EmployeeRating::where('employee_id', $expert->id)->avg('rating'),
                    1
                );
                return view('frontend::components.card.expert_card', ['expert' => $expert])->render();
            })
            ->addColumn('name', function ($expert) {
                return $expert->full_name;
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$keyword}%"]);
            })
            ->rawColumns(['card'])
            ->make(true);
    }
}
