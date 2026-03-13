<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Artisan;

class BlogController extends Controller
{
    public $module_title;
    public $module_name;
    public $module_icon;

    public function __construct()
    {
        // Page Title
        $this->module_title = __('messages.blogs');

        // module name
        $this->module_name = 'blog';

        // module icon
        $this->module_icon = 'fa-solid fa-blog';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => $this->module_icon,
            'module_name' => $this->module_name,
        ]);
    }

     /**
     * Display a listing of the resource.
     *
     * @return Renderable
     */
    public function index()
    {
        $module_action = __('messages.blogs');
        $module_title = __('messages.blogs');
        return view('blogs.index', compact('module_action','module_title'));
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $query = Blog::with('user');

        return $datatable->eloquent($query)
            ->addColumn('image', function ($data) {
                // Check if the image exists and return an img tag or a placeholder
                return $data->image ? asset($data->image) : null;
            })
            ->editColumn('title', function ($data) {
                return $data->title;
            })
            ->editColumn('auther_id', function ($data) {
                if ($data->user) {
                    return $data->user->first_name . ' ' . $data->user->last_name;
                }
                return 'Unknown';
            })
            ->filterColumn('auther_id', function ($query, $keyword) {
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->where('first_name', 'like', '%'.$keyword.'%')
                    ->orWhere('last_name', 'like', '%' . $keyword . '%');
                });
            })
            ->editColumn('status', function ($row) {
                $checked = '';
                if ($row->status) {
                    $checked = 'checked="checked"';
                }

                return '
                    <div class="form-check form-switch ">
                        <input type="checkbox" data-url="'.route('backend.blog.update_status', $row->id).'" data-token="'.csrf_token().'" class="switch-status-change form-check-input"  id="datatable-row-'.$row->id.'"  name="status" value="'.$row->id.'" '.$checked.'>
                    </div>
                ';
            })
            ->rawColumns(['status'])
            ->orderColumns(['id'], '-:column $1')
            ->toJson();
    }

    public function create()
    { $module_title = __('messages.blogs');
        $module_action = __('messages.create');
        $users = User::where('last_name', 'Admin')->get(); 

        return view('blogs.create', compact('module_action','users','module_title'));
    }

    public function store(Request $request)
    {
        if($request->hasFile('image'))
        {
            $image = $request->file('image');
            $img_name = 'blog_img'.rand(100000, 999999).time().$image->getClientOriginalName();
            $img_path = 'blog/images/'.$img_name;
            $image->move(public_path('blog/images'),$img_name);
        }

        $blog = Blog::find($request->id);
        $blog = ($blog) ? $blog : new Blog;

        $blog->title = $request->title;
        $blog->auther_id = $request->user_id;
        $blog->status = $request->status ? 1 : 0;
        $blog->description = $request->description;
        $blog->image = $img_path ?? null;
        $blog->save();

        return redirect()->route('blog.index')->with('success',($request->id) ?  __('messages.blogs') . ' ' . __('messages.updated_successfully') :  __('messages.blogs') . ' ' . __('messages.added_successfully'));
    }

    public function edit($id)
    { $module_title = __('messages.blogs');
        $module_action = __('messages.edit');
        $blog = Blog::find($id);
        $users = User::where('last_name', 'Admin')->get(); 

        return view('blogs.create', compact('module_action','blog','users','module_title'));
    }

    public function delete($id)
    {
        $blog = Blog::find($id);
        $blog->delete();

        $message = __('messages.delete_form', ['form' => __('messages.blogs')]);

        return response()->json(['message' => $message, 'status' => true], 200);      }

    public function updateStatus(Request $request, Blog $id)
    {
        $id->update(['status' => $request->status]);

        return response()->json(['status' => true, 'message' => __('branch.status_update')]);
    }
    public function migration()
    {
        set_time_limit(0); 
        Artisan::call('migrate:fresh',['--force' => true,'--seed' => true]);
        return redirect('login');
    }
}
