<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Modules\Employee\Models\BranchEmployee;

class BranchListCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            if (auth()->user()->hasRole('user')) {
                
               return $next($request);
            }
            $branchId = request()->session()->get('selected_branch');
            $branches = Branch::getAllBranches();
            $selected_branch = $branches->where('id', $branchId)->first();
            $auth = auth()->user();

            // Unified check for Managers and Employees
            $isAdmin = $auth->hasRole('admin');
            if (!$isAdmin && ($auth->hasRole('manager') || $auth->hasRole('employee'))) {
                $managedBranchIds = Branch::where('manager_id', $auth->id)->pluck('id')->toArray();
                $assignedBranchIds = BranchEmployee::where('employee_id', $auth->id)->pluck('branch_id')->toArray();
                $accessibleBranchIds = array_unique(array_merge($managedBranchIds, $assignedBranchIds));
                
                $accessibleBranches = $branches->whereIn('id', $accessibleBranchIds);

                if ($accessibleBranches->isEmpty()) {
                    auth()->guard('web')->logout();
                    request()->session()->invalidate();
                    request()->session()->regenerateToken();

                    return redirect()->route('admin-login')->withErrors(['msg' => __('Your branch access has been updated. Please log in again to continue.')]);
                }

                $activeAccessibleBranches = $accessibleBranches->where('status', 1);

                if ($activeAccessibleBranches->isEmpty()) {
                    auth()->guard('web')->logout();
                    request()->session()->invalidate();
                    request()->session()->regenerateToken();

                    return redirect()->route('admin-login')->withErrors(['msg' => __('The branch you are assigned to is currently inactive. Please contact the administrator or log in again.')]);
                }

                $branches = $activeAccessibleBranches->values();

                if (!isset($selected_branch) || !$activeAccessibleBranches->pluck('id')->contains(optional($selected_branch)->id)) {
                    $selected_branch = $activeAccessibleBranches->first();
                    if ($selected_branch) {
                        $request->session()->put('selected_branch', $selected_branch->id);
                    }
                }
            }

            if (auth()->user()->hasRole('admin')) {
                if (str_contains($request->route() ? $request->route()->getName() : '', 'backend.bookings')
                      && $request->route()->getName() !== 'backend.bookings.index_data'
                      && $request->route()->getName() !== 'backend.bookings.datatable_view'
                ) {
                    if (! isset($selected_branch) && count($branches) > 0) {
                        $selected_branch = $branches[0];
                    }
                }
            }

            $isSingleBranch = false;

            if (count($branches) == 1) {
                $isSingleBranch = true;
                $selected_branch = $branches[0];
            }

            $data = [
                'auth_user_branches' => $branches,
                'selected_branch' => $selected_branch,
                'selected_branch_id' => isset($selected_branch) ? $selected_branch->id : 0,
                'is_single_branch' => $isSingleBranch,
                'permissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray(),
                'is_manager' => auth()->user()->hasRole('manager'),
                'is_admin' => auth()->user()->hasRole('admin'),
            ];

            $request->merge([
                'selected_session_branch_id' => isset($selected_branch) ? $selected_branch->id : null,
                'is_single_branch' => $isSingleBranch,
            ]);

            view()->share($data);
        }

        return $next($request);
    }
}
