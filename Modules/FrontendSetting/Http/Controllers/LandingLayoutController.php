<?php

namespace Modules\FrontendSetting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Branch;
use App\Models\Product;
use Modules\FrontendSetting\Models\FrontendSetting;

class LandingLayoutController extends Controller
{
    /**
     * View for managing landing page settings.
     */
    public function getLandingPageSettingsView()
    {
        $branches = Branch::all();
        $tabpage = 'section_1'; // default tab

        return view('frontendsetting::landing-settings', compact('branches', 'tabpage'));
    }

    /**
     * Return stored config for a specific landing page section.
     */
    public function getLandingLayoutPageConfig(Request $request)
    {
        $data = FrontendSetting::where('type', $request->type)
            ->where('page', $request->page)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Save or update landing page section config.
     */
    public function saveLandingLayoutPageConfig(Request $request)
    {
        $type = $request->input('type');
        $page = $request->input('page');

        $validated = $request->validate([
            'type' => 'required|string',
            'page' => 'required|string',
            'status' => 'required|in:0,1',
        ]);

        $data = [
            'status' => $validated['status']
        ];

        // Special case for section_8: save product IDs
        if ($type === 'section_8') {
            $request->validate([
                'product_id' => 'nullable|array',
                'product_id.*' => 'exists:products,id',
            ]);

            $data['product_id'] = $validated['status'] ? $request->input('product_id', []) : [];
        }

        // Optional: section_3 or others can still save branch_id
        if ($type === 'section_3') {
            $request->validate([
                'branch_id' => 'nullable|exists:branches,id',
            ]);
            $data['branch_id'] = $validated['status'] ? $request->input('branch_id') : null;
        }

        FrontendSetting::updateOrCreate(
            ['type' => $type, 'page' => $page],
            ['value' => json_encode($data)]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Return products for Select2 dropdown (AJAX).
     */
    public function getProducts(Request $request)
    {
        $search = $request->input('q');

        $query = Product::query()->select('id', 'name')->where('status', 1);

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $products = $query->limit(20)->get();

        $formatted = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'text' => $product->name,
            ];
        });

        return response()->json(['results' => $formatted]);
    }
}
