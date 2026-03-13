<?php

namespace Modules\Frontend\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Category\Models\Category;
use Yajra\DataTables\Facades\DataTables; // use Yajra DataTables

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Category::whereNull('parent_id')->where('status', 1);
        
        if (request()->has('search')) {
            $searchTerm = request()->get('search');
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }

        $categories = $query->paginate(10);

        if (request()->ajax()) {
            return view('frontend::category', compact('categories'))->render();
        }

        return view('frontend::category', compact('categories'));
    }

    // public function showCategories()
    // {
    //     $categories = Category::paginate(6);
    //     return view('frontend.categories.index', compact('categories'));
    // }

   public function categoriesData(Request $request)
{
    $query = Category::whereNull('parent_id')
        ->where('status', 1)
        ->whereHas('services'); // Only show categories that have at least one service

    return DataTables::of($query)
        ->addColumn('card', function ($category) {
            return view('frontend::components.card.category_card', compact('category'))->render();
        })
        ->addColumn('name', function ($category) {
            return $category->name;
        })
        ->filterColumn('name', function ($query, $keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        })
        ->rawColumns(['card'])
        ->make(true);
}

    public function SubCategory()
    {
        return view('frontend::subcategory');
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
}
