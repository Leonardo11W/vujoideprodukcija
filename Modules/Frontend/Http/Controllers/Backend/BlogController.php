<?php

namespace Modules\Frontend\Http\Controllers\Backend;


use App\Http\Controllers\Controller;
use Yajra\DataTables\DataTables;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Blog\Models\Blog;


class BlogController extends Controller
{
    public function blogsList()
    {
        $blogs = Blog::with('author')->latest()->get();

        return view('frontend::blog', compact('blogs'));
    }

    public function index_data(Request $request)
    {

        $query = \Modules\Blog\Models\Blog::with('author')->where('status', 1);

        return \Yajra\DataTables\DataTables::of($query)
            ->addColumn('card', function ($blog) {
                return view('frontend::components.card.blog_card', compact('blog'))->render();
            })
            ->addColumn('title', function ($blog) {
                return $blog->title;
            })
            ->filterColumn('title', function ($query, $keyword) {
                $query->where('title', 'like', "%{$keyword}%");
            })
            ->rawColumns(['card'])
            ->make(true);
    }

    public function blogDetails($id)
    {
        $blog = Blog::with('author')->findOrFail($id);

        $previous_blog = Blog::where('id', '<', $id)->latest()->first();

        $next_blog = Blog::where('id', '>', $id)->oldest()->first();

        $related_blogs = Blog::where('id', '!=', $id)->latest()->take(6)->get();

        return view('frontend::blog-details', compact('blog', 'previous_blog', 'next_blog', 'related_blogs'));
    }
}
