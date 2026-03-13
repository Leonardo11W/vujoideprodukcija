<?php

namespace Modules\Frontend\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Package\Models\Package;
use Yajra\DataTables\DataTables;
use Modules\Tax\Models\Tax;

class PackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $packages = Package::with(['serviceItems', 'branch'])
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->paginate(3);
            
        return view('frontend::package', compact('packages'));
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

    public function packagecheckout(Request $request)
    {
        $package_id = $request->query('id');
        $package = \Modules\Package\Models\Package::with(['serviceItems', 'branch'])
            ->findOrFail($package_id);

        $tax = Tax::all();
        return view('frontend::package_purchase', compact('package','tax'));
    }

    public function packagesData(Request $request)
    {
        $query = Package::with(['serviceItems', 'branch'])
            ->where('status', 1)
            ->orderBy('created_at', 'desc');

        return \Yajra\DataTables\DataTables::of($query)
            ->addColumn('card', function ($package) {
                $totalServicePrice = $package->serviceItems->sum(function($service) {
                    return $service->pivot->service_price * $service->pivot->qty;
                });

                $discountBadge = '';
                if ($totalServicePrice > $package->package_price) {
                    $discountPercentage = round((($totalServicePrice - $package->package_price) / $totalServicePrice) * 100);
                    $discountBadge = '<span class="badge bg-success text-white package-discount flex-shrink-0 font-size-14 py-1 lh-base px-3">' . $discountPercentage . '% OFF</span>';
                }

                $branchName = $package->branch ? $package->branch->name : 'All Branches';
                $description = $package->description;
                $maxLen = 30;
                if (strlen($description) > $maxLen) {
                    $description = mb_substr($description, 0, $maxLen) . '...';
                }

                $serviceItems = '';
                foreach ($package->serviceItems as $service) {
                    $serviceItems .= '
                        <li class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <span class="package-check">
                                <i class="ph ph-check icon-color"></i>
                            </span>
                            <span class="flex-grow-1">' . $service->pivot->service_name . ' Qty: ' . $service->pivot->qty . '</span>
                        </li>';
                }

                return '<div class="pricing-card rounded-3 position-relative h-100">'
                    . $discountBadge
                    . '<div class="d-flex flex-wrap align-items-center column-gap-3 row-gap-2 package-wrap">'
                    . '<span class="badge bg-purple text-body border rounded-pill text-uppercase">' . $branchName . '</span>'
                    . '<h6 class="mb-0 font-size-18">' . $package->name . '</h6>'
                    . '</div>'
                    . '<p class="mb-5 package-desc-ellipsis test-truncate">' . $description . '</p>'
                    . '<div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">'
                    . '<div class="flex-grow-1">'
                    . '<span class="d-flex align-items-center gap-3 mb-2">'
                    . '<h4 class="package-price m-0 text-primary">' . \Currency::format($package->package_price) . '</h4>'
                    . ($totalServicePrice > $package->package_price ? '<del class="fw-semibold">' . \Currency::format($totalServicePrice) . '</del>' : '')
                    . '</span>'
                    . '</div>'
                    . '<span class="package-duration">/ ' . ($package->package_validity ?? 1) . ' month' . ($package->package_validity > 1 ? 's' : '') . '</span>'
                    . '</div>'
                    . '<a href="' . route('package-checkout', ['id' => $package->id]) . '" class="btn btn-secondary w-100 buy-btn">Purchase Now</a>'
                    . '<div>'
                    . '<h6 class="package-included-title">What\'s included:</h6>'
                    . '<ul class="list-unstyled m-0 package-included-list d-flex flex-column gap-1">' . $serviceItems . '</ul>'
                    . '</div>'
                    . '</div>';
            })
            ->addColumn('name', function($package) {
                return $package->name;
            })
            ->rawColumns(['card'])
            ->make(true);
    }
}
