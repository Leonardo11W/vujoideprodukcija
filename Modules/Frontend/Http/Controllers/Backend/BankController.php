<?php

namespace Modules\Frontend\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Bank\Models\Bank;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\View;

class BankController extends Controller
{
    /**
     * Show list of user's banks.
     */
    public function index(Request $request)
    {
        // Classic pagination for non-AJAX requests
        $banks = Bank::where('user_id', auth()->id())
            ->orderBy('is_default', 'desc')
            ->latest()
            ->paginate(6);

        // For AJAX requests, you can use getBankCardsData route instead
        return view('frontend::bank_list', compact('banks'));
    }

    /**
     * Set a bank as default for the user.
     */
    public function setDefault(Bank $bank)
    {
        if ($bank->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        // Set all banks to non-default
        Bank::where('user_id', auth()->id())->update(['is_default' => false]);

        // Set the selected bank as default
        $bank->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => __('messages.default_bank_updated')
        ]);
    }

    /**
     * Store a new bank record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name'   => 'required|string|max:191',
            'branch_name' => 'required|string|max:191',
            'account_no'  => 'required|string|max:191|unique:banks,account_no,NULL,id,user_id,' . auth()->id(),
            'ifsc_no'     => 'nullable|string|max:191',
            'status'      => 'nullable',
        ]);

        $validated['user_id'] = auth()->id();

        // Set status to false if not present
        if (!isset($validated['status'])) {
            $validated['status'] = 0;
        } else {
            $validated['status'] = 1;
        }

        // Check if this is the user's first bank
        // $hasAnyBank = Bank::where('user_id', auth()->id())->exists();
        
        // if ($hasAnyBank) {
          
        //     Bank::where('user_id', auth()->id())->update(['is_default' => 0]);
        //     $validated['is_default'] = 1; 
        // } else {
        //     $validated['is_default'] = 1; 
        // }

        $bank = Bank::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Bank details stored successfully!',
                'data' => $bank
            ]);
        }

        return redirect()->route('bank.index')->with('success', 'Bank details stored successfully!');
    }

    /**
     * Update an existing bank record.
     */
    public function update(Request $request, Bank $bank)
    {
        $validated = $request->validate([
            'bank_name'   => 'required|string|max:191',
            'branch_name' => 'required|string|max:191',
            'account_no'  => 'required|string|max:191|unique:banks,account_no,' . $bank->id . ',id,user_id,' . auth()->id(),
            'ifsc_no'     => 'nullable|string|max:191',
            'status'      => 'nullable',
        ]);

        if (!isset($validated['status'])) {
            $validated['status'] = 0;
        } else {
            $validated['status'] = 1;
        }

        $bank->update($validated);

        return redirect()->route('bank.index')->with('success', __('messages.bank_updated'));
    }

    /**
     * Delete a bank record.
     */
    public function destroy(Bank $bank, Request $request)
    {
        if ($bank->user_id !== auth()->id()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        if ($bank->is_default) {
            $msg = __('messages.cannot_delete_default_bank');
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }
            return redirect()->back()->with('error', $msg);
        }

        $bank->delete();

        $msg = __('messages.bank_deleted');
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return redirect()->route('bank.index')->with('success', $msg);
    }

    /**
     * Get bank cards data with search, sorting, and pagination (AJAX).
     * Returns each bank as a rendered card partial for use in a card grid.
     */
    public function getBankCardsData(Request $request)
    {
        $query = Bank::where('user_id', auth()->id())
            ->orderByDesc('is_default')
            ->latest();

        // DataTables search (search[value] is standard for DataTables)
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('bank_name', 'like', '%' . $searchValue . '%')
                    ->orWhere('branch_name', 'like', '%' . $searchValue . '%')
                    ->orWhere('account_no', 'like', '%' . $searchValue . '%')
                    ->orWhere('ifsc_no', 'like', '%' . $searchValue . '%');
            });
        }

        return DataTables::of($query)
            ->addColumn('card', function ($bank) {
                return view('frontend::components.card.bank_card', compact('bank'))->render();
            })
            ->rawColumns(['card'])
            ->make(true);
    }
}
