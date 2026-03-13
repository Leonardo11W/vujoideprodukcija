<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class FaqController extends Controller
{
    public $module_title;
    public $module_name;
    public $module_icon;

    public function __construct()
    {
        // Page Title
        $this->module_title = __('messages.faqs');

        // module name
        $this->module_name = 'faq';

        // module icon
        $this->module_icon = 'fa-solid fa-question-circle';

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
        if(!auth()->user()->can('view_faq')) {
            abort(403);
        }
        $module_action = __('messages.faqs');
        $module_title = __('messages.faqs');
        return view('faqs.index', compact('module_action','module_title'));
    }

    public function index_data(Datatables $datatable)
    {
        if(!auth()->user()->can('view_faq')) {
            abort(403);
        }
        $query = Faq::query();

        return $datatable->eloquent($query)
            ->editColumn('question', function ($row) {
                return wordwrap($row->question, 60, "<br>", true);
            })
            ->editColumn('answer', function ($row) {
                return wordwrap($row->answer, 60, "<br>", true);
            })
            ->editColumn('status', function ($row) {
                $checked = '';
                if ($row->status) {
                    $checked = 'checked="checked"';
                }

                $disabled = !auth()->user()->can('edit_faq') ? 'disabled' : '';

                return '
                <div class="form-check form-switch ">
                    <input type="checkbox" data-url="'.route('faq.update_status', $row->id).'" data-token="'.csrf_token().'" class="switch-status-change form-check-input"  id="datatable-row-'.$row->id.'"  name="status" value="'.$row->id.'" '.$checked.' '.$disabled.'>
                </div>
            ';
            })
            ->addColumn('action', function ($row) {
                $buttons = '';
                if (auth()->user()->can('edit_faq')) {
                    $buttons .= ' <button class="btn btn-primary btn-sm btn-edit" onclick="editFaq('.$row->id.')" title="'.__('messages.edit').'" data-bs-toggle="tooltip">
                                     <i class="fas fa-edit"></i>
                                 </button>';
                }
                if (auth()->user()->can('delete_faq')) {
                    $buttons .= '
                                     <a href="'.route('faq.delete', $row->id).'"
                                        id="delete-faq-'.$row->id.'"
                                        class="btn btn-danger btn-sm"
                                        data-type="ajax"
                                        data-method="DELETE"
                                        data-token="'.csrf_token().'"
                                        data-bs-toggle="tooltip"
                                        title="'.__('messages.delete').'"
                                        data-confirm="'.__('messages.are_you_sure?', ['module' => __('messages.lbl_faq'), 'name' => '']).'">
                                        <i class="fa-solid fa-trash"></i>
                                     </a>';
                }

                return $buttons;
            })
            ->rawColumns(['status', 'question', 'answer', 'action'])
            ->orderColumns(['id'], '-:column $1')
            ->toJson();
    }

    public function create()
    {
        if(!auth()->user()->can('add_faq')) {
            abort(403);
        }
        $module_action = __('messages.create');
        $module_title = __('messages.faqs');
        return view('faqs.create', compact('module_action','module_title'));
    }

    public function store(Request $request)
    {
        if ($request->id) {
            if(!auth()->user()->can('edit_faq')) {
                abort(403);
            }
        } else {
            if(!auth()->user()->can('add_faq')) {
                abort(403);
            }
        }
        $faq = Faq::find($request->id);
        $faq = ($faq) ? $faq : new Faq;

        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->status = $request->status ? 1 : 0;
        $faq->save();

        return redirect()->route('faq.index')->with('success',($request->id) ?  __('messages.faqs') . ' ' . __('messages.updated_successfully') :  __('messages.faqs') . ' ' . __('messages.added_successfully'));
    }

    public function edit($id)
    {   
        if(!auth()->user()->can('edit_faq')) {
            abort(403);
        }
        $module_title = __('messages.faqs');
        $module_action = __('messages.edit');
        $faq = Faq::find($id);

        return view('faqs.create', compact('module_action','faq','module_title'));
    }

    public function delete($id)
    {
        if(!auth()->user()->can('delete_faq')) {
            abort(403);
        }
        $faq = Faq::find($id);
        $faq->delete();

       
        $message = __('messages.delete_form', ['form' => __('messages.faqs')]);

        return response()->json(['message' => $message, 'status' => true], 200);    }

    public function updateStatus(Request $request, Faq $id)
    {
        if(!auth()->user()->can('edit_faq')) {
            abort(403);
        }
        $id->update(['status' => $request->status]);

        return response()->json(['status' => true, 'message' => __('branch.status_update')]);
    }
}
