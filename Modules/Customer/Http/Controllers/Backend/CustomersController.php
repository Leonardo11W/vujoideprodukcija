<?php

namespace Modules\Customer\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use App\Currency\CurrencyFacades as Currency;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;
use Modules\Customer\Http\Requests\CustomerRequest;
use Modules\CustomField\Models\CustomField;
use Modules\CustomField\Models\CustomFieldGroup;
use Yajra\DataTables\DataTables;

class CustomersController extends Controller
{
	// use Authorizable;
	protected string $module_title;
	protected string $module_name;
	protected string $module_path;
	protected string $exportClass = '\\App\\Exports\\CustomerExport';

	public function __construct()
	{
		// Page Title
		$this->module_title = 'customer.title';

		// module name
		$this->module_name = 'customers';

		// directory path of the module
		$this->module_path = 'customer::backend';

		view()->share([
			'module_title' => $this->module_title,
			'module_icon' => 'fa-regular fa-sun',
			'module_name' => $this->module_name,
			'module_path' => $this->module_path,
		]);
		$this->middleware(['permission:view_customer'])->only('index');
		$this->middleware(['permission:edit_customer'])->only('edit', 'update');
		$this->middleware(['permission:add_customer'])->only('store');
		$this->middleware(['permission:delete_customer'])->only('destroy');
	}

	public function bulk_action(Request $request)
	{
		$ids = explode(',', $request->rowIds);

		$actionType = $request->action_type;

		$message = __('messages.bulk_update');

		// dd($actionType, $ids, $request->status);
		switch ($actionType) {
			case 'change-status':
				$customer = User::whereIn('id', $ids)->update(['status' => $request->status]);
				$message = __('messages.bulk_customer_update');
				break;

			case 'delete':
				if (env('IS_DEMO')) {
					return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
				}
				User::whereIn('id', $ids)->delete();
				$message = __('messages.bulk_customer_delete');
				break;

			default:
				return response()->json(['status' => false, 'message' => __('branch.invalid_action')]);
				break;
		}

		return response()->json(['status' => true, 'message' => __('messages.bulk_update')]);
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
	 */
	public function index()
	{
		$module_action = 'List';
		$columns = CustomFieldGroup::columnJsonValues(new User());
		$customefield = CustomField::exportCustomFields(new User());

		$export_import = true;
		$export_columns = [
			[
				'value' => 'first_name',
				'text' => 'First Name',
			],
			[
				'value' => 'last_name',
				'text' => 'Last Name',
			],
			[
				'value' => 'email',
				'text' => 'E-mail',
			],
			[
				'value' => 'varification_status',
				'text' => 'Verification Status',
			],
			// [
			//     'value' => 'is_banned',
			//     'text' => 'Banned',
			// ],
			[
				'value' => 'status',
				'text' => 'Status',
			],
			[
				'value' => 'wallet_balance',
				'text' => 'Wallet Balance',
			],
			[
				'value' => 'gender',
				'text' => 'Gender',
			]
		];
		$export_url = route('backend.customers.export');

		return view('customer::backend.customers.index', compact('module_action', 'columns', 'customefield', 'export_import', 'export_columns', 'export_url'));
	}

	public function update_status(Request $request, User $id)
	{
		$id->update(['status' => $request->status]);

		return response()->json(['status' => true, 'message' => __('branch.status_update')]);
	}

	public function index_data(Datatables $datatable, Request $request)
	{
		$module_name = $this->module_name;
		$query = User::role('user')->with('wallet');

		$filter = $request->filter;

		if (isset($filter)) {
			if (isset($filter['column_status'])) {
				$query->where('status', $filter['column_status']);
			}
		}

		$datatable = $datatable->eloquent($query)
		->addColumn('check', function ($row) {

			$user = auth()->user();

			// Permissions that allow bulk actions
			$hasActionPermission =
				$user->can('edit_customer') ||
				$user->can('delete_customer');

			// If NO permission → return empty (no checkbox)
			if (!$hasActionPermission) {
				return '';
			}

			// If branch status is inactive AND user cannot change status → hide checkbox
			if (!$row->status && !$user->can('edit_customer')) {
				return '';
			}

			return '<input
				type="checkbox"
				class="form-check-input select-table-row"
				id="datatable-row-' . $row->id . '"
				name="datatable_ids[]"
				value="' . $row->id . '"
				onclick="dataTableRowCheck(' . $row->id . ')"
			>';
		})
			->addColumn('action', function ($data) use ($module_name) {
				return view('customer::backend.customers.action_column', compact('module_name', 'data'));
			})

			// ->editColumn('image', function ($data) {
			//     return "<img src='".$data->profile_image."'class='avatar avatar-50 rounded-pill'>";
			// })

			->addColumn('user_id', function ($data) {
				$Profile_image = optional($data)->profile_image ?? default_user_avatar();
				$name = optional($data)->full_name ?? default_user_name();
				$email = optional($data)->email ?? '--';
				$id = $data->id;
				return view('customer::backend.customers.datatable.user_id', compact('Profile_image', 'name', 'email', 'id'));
				// return view('employee::backend.employees.employee_id', compact('data'));
			})
			->orderColumn('user_id', function ($query, $order) {
				$query->orderBy('users.first_name', $order) // Ordering by first name
				      ->orderBy('users.last_name', $order); // Optional: also order by last name
			}, 1)
			->filterColumn('user_id', function ($query, $keyword) {
				if (!empty($keyword)) {
					// Assuming 'users' table has first_name and last_name
					$query->where(function ($query) use ($keyword) {
						$query->where('first_name', 'like', '%' . $keyword . '%')
						      ->orWhere('last_name', 'like', '%' . $keyword . '%') // Filtering by last name
						      ->orWhere('email', 'like', '%' . $keyword . '%');
					});
				}
			})

			->editColumn('gender', function ($data) {
				return $data->gender ? __('messages.' . $data->gender) : '-';
			})
			// ->editColumn('user_id', function ($data) {
			//     return  $data->first_name . ' ' . $data->last_name;
			// })

			->editColumn('email_verified_at', function ($data) {
				$checked = '';
				if ($data->email_verified_at) {
					return '<span class="badge bg-soft-success"><i class="fa-solid fa-envelope" style="margin-right: 2px"></i> '.__('customer.msg_verified').'</span>';
				}

				return '<button  type="button" data-url="'.route('backend.customers.verify-customer', $data->id).'" data-token="'.csrf_token().'" class="button-status-change btn btn-text-danger btn-sm  bg-soft-danger"  id="datatable-row-'.$data->id.'"  name="is_verify" value="'.$data->id.'" '.$checked.'>Verify</button>';
			})
			->addColumn('wallet_balance', function ($data) {
				return '<a href="' . route('wallet.history', ['id' => $data->id]) . '">' . Currency::format(optional($data->wallet)->amount) . '</a>';
			})

			->editColumn('is_banned', function ($data) {
				$canChangeBanned = auth()->user()->hasRole('admin') || auth()->user()->can('edit_customer'); // Assuming edit_customer covers banning
				$checked = $data->is_banned ? 'checked="checked"' : '';
				$disabled = $canChangeBanned ? '' : 'disabled';

				return '
					<div class="form-check form-switch ">
						<input type="checkbox" data-url="'.route('backend.customers.block-customer', $data->id).'" data-token="'.csrf_token().'" class="switch-status-change form-check-input"  id="datatable-row-'.$data->id.'"  name="is_banned" value="'.$data->id.'" '.$checked.' '.$disabled.'>
					</div>
				 ';
			})

			->editColumn('status', function ($row) {
				$canChangeStatus = auth()->user()->hasRole('admin') || auth()->user()->can('edit_customer');
				$checked = $row->status ? 'checked' : '';
				$disabled = $canChangeStatus ? '' : 'disabled';

				return '
					<div class="form-check form-switch d-flex justify-content-center">
						<input 
							type="checkbox"
							class="form-check-input switch-status-change"
							data-url="' . route('backend.customers.update_status', $row->id) . '"
							data-token="' . csrf_token() . '"
							' . $checked . '
							' . $disabled . '
						>
					</div>
				';
			})
			
			->editColumn('updated_at', function ($data) {
				$module_name = $this->module_name;

				$diff = Carbon::now()->diffInHours($data->updated_at);

				if ($diff < 25) {
					return $data->updated_at->diffForHumans();
				} else {
					return $data->updated_at->isoFormat('llll');
				}
			})

			// ->filterColumn('user_id', function ($query, $keyword) {
			//     if (!empty($keyword)) {
			//             $query->whereRaw('CONCAT(first_name, " ", last_name) LIKE ?', ['%' . $keyword . '%']);
			//     }
			// })

			->orderColumns(['id'], '-:column $1');

		// Custom Fields For export
		$customFieldColumns = CustomField::customFieldData($datatable, User::CUSTOM_FIELD_MODEL, null);

		return $datatable->rawColumns(array_merge(['user_id','action', 'status', 'is_banned', 'email_verified_at', 'check', 'image', 'wallet_balance'], $customFieldColumns))
			->toJson();
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
	 */
	public function show($id, Request $request)
	{
		$customer = User::with(['orders.orderItems'])->findOrFail($id);

		// Check if user is staff (employee or manager) and get selected branch
		$authUser = auth()->user();
		$isStaff = $authUser && ($authUser->hasRole('employee') || $authUser->hasRole('manager'));
		$selectedBranchId = null;

		if ($isStaff) {
			// Get selected branch from request (set by middleware) or session
			$selectedBranchId = $request->selected_session_branch_id ?? $request->session()->get('selected_branch');
			
			// For employees without manager role, use their assigned branch
			if (!$selectedBranchId && $authUser->hasRole('employee') && !$authUser->hasRole('manager')) {
				try {
					$selectedBranchId = optional($authUser->branch)->branch_id;
				} catch (\Exception $e) {
					\Log::error('Error getting employee branch: ' . $e->getMessage());
				}
			}
		}

		// Build base query function for bookings with branch filter
		$buildBookingsQuery = function () use ($customer, $isStaff, $selectedBranchId) {
			$query = Booking::where('user_id', $customer->id);
			if ($isStaff && $selectedBranchId) {
				$query->where('branch_id', $selectedBranchId);
			}
			return $query;
		};

		// Get booking counts with branch filter if applicable
		$totalBookings = $buildBookingsQuery()->count();
		$cancelledBookings = $buildBookingsQuery()->where('status', 'cancelled')->count();
		$completedBookings = $buildBookingsQuery()->where('status', 'completed')->count();

		$purchasedProducts = 0;
		if (method_exists($customer, 'orders')) {
			$purchasedProducts = $customer->orders->sum(function ($order) {
				return method_exists($order, 'orderItems') && $order->relationLoaded('orderItems')
					? $order->orderItems->count()
					: 0;
			});
		}

		// Get bookings with relationships
		$bookings = $buildBookingsQuery()->with([
			'booking_service.employee',
			'branch.address.country_data',
		])
			->orderByDesc('start_date_time')
			->get();

		// Get booking status constants for proper display
		$bookingStatuses = \Modules\Constant\Models\Constant::getAllConstant()
			->where('type', 'BOOKING_STATUS')
			->pluck('value', 'name');

		$data = [
			'totalBookings' => $totalBookings,
			'cancelledBookings' => $cancelledBookings,
			'completedBookings' => $completedBookings,
			'purchasedProducts' => $purchasedProducts,
			'customerInfo' => $customer,
			'bookings' => $bookings,
			'bookingStatuses' => $bookingStatuses,
		];

		return view('customer::backend.customers.customer_detail', compact('data'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(CustomerRequest $request)
	{
		$data = $request->all();

		$data['password'] = Hash::make($data['password']);

		// Auto-verify and activate customers created from admin
		$data['email_verified_at'] = Carbon::now();
		$data['status'] = 1;

		$data = User::create($data);

		$data->syncRoles(['user']);

		Artisan::call('cache:clear');

		if ($request->custom_fields_data) {
			$data->updateCustomFieldData(json_decode($request->custom_fields_data));
		}

		if ($request->has('profile_image')) {
			$request->file('profile_image');

			storeMediaFile($data, $request->file('profile_image'), 'profile_image');
		}

		$message = __('messages.create_form', ['form' => __('customer.singular_title')]);

		return response()->json(['message' => $message, 'status' => true], 200);
	}

	public function edit($id)
	{
		$data = User::findOrFail($id);

		if (! is_null($data)) {
			$custom_field_data = $data->withCustomFields();
			$data['custom_field_data'] = collect($custom_field_data->custom_fields_data)
				->filter(function ($value) {
					return $value !== null;
				})
				->toArray();
		}

		$data['profile_image'] = $data->profile_image;

		return response()->json(['data' => $data, 'status' => true]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update(CustomerRequest $request, $id)
	{
		$data = User::findOrFail($id);

		$request_data = $request->except('profile_image');

		$data->update($request_data);

		if ($request->custom_fields_data) {
			$data->updateCustomFieldData(json_decode($request->custom_fields_data));
		}

		if ($request->hasFile('profile_image')) {
			storeMediaFile($data, $request->file('profile_image'), 'profile_image');
		}
		// Only clear image when explicitly requested via flag
		if ($request->input('remove_profile_image') === '1') {
			$data->clearMediaCollection('profile_image');
		}
		$message = __('messages.update_form', ['form' => __('customer.singular_title')]);

		return response()->json(['message' => $message, 'status' => true], 200);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy($id)
	{
		if (env('IS_DEMO')) {
			return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
		}
		$data = User::findOrFail($id);

		$booking = Booking::where('user_id', $id)->where('status', '!=', 'completed')->update(['status' => 'cancelled']);

		$data->tokens()->delete();

		$data->forceDelete();

		$message = __('messages.delete_form', ['form' => __('customer.singular_title')]);

		return response()->json(['message' => $message, 'status' => true], 200);
	}

	/**
	 * List of trashed ertries
	 * works if the softdelete is enabled.
	 *
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
	 */
	public function trashed()
	{
		$module_name = $this->module_name;

		$module_name_singular = Str::singular($module_name);

		$module_action = 'Trash List';

		$data = User::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate();

		return view('customer::backend.customers.trash', compact('data', 'module_name_singular', 'module_action'));
	}

	/**
	 * Restore a soft deleted entry.
	 *
	 * @param  Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	public function restore($id)
	{
		$module_action = 'Restore';

		$data = User::withTrashed()->find($id);
		$data->restore();

		return redirect('app/customers');
	}

	public function change_password(Request $request)
	{
		$payload = $request->all();
		$user_id = $payload['user_id'] ?? null;
		$old_password = $payload['old_password'] ?? '';
		$new_password = $payload['password'] ?? '';
		$confirm_password = $payload['confirm_password'] ?? '';

		if (! $user_id) {
			return response()->json(['message' => __('messages.validation_error'), 'status' => false], 422);
		}

		$user = User::findOrFail($user_id);

		// Validate old password
		if (! Hash::check($old_password, $user->password)) {
			return response()->json(['message' => __('messages.old_password_mismatch'), 'errors' => ['old_password' => __('messages.old_password_mismatch')], 'status' => false], 403);
		}

		// Ensure new != old
		if ($old_password === $new_password) {
			return response()->json(['message' => __('messages.new_password_mismatch'), 'errors' => ['password' => __('messages.new_password_mismatch')], 'status' => false], 422);
		}

		// Confirm match
		if ($new_password !== $confirm_password) {
			return response()->json(['message' => __('messages.password_mismatch'), 'errors' => ['confirm_password' => __('messages.password_mismatch')], 'status' => false], 422);
		}

		// Update password
		$user->update(['password' => Hash::make($new_password)]);

		$message = __('messages.password_update');

		return response()->json(['message' => $message, 'status' => true], 200);
	}

	public function block_customer(Request $request, User $id)
	{
		$id->update(['is_banned' => $request->status]);

		if ($request->status == 1) {
			$message = __('messages.google_blocked');
		} else {
			$message = __('messages.google_unblocked');
		}

		return response()->json(['status' => true, 'message' => $message]);
	}

	public function verify_customer(Request $request, $id)
	{
		$data = User::findOrFail($id);

		$current_time = Carbon::now();

		$data->update(['email_verified_at' => $current_time]);

		return response()->json(['status' => true, 'message' => __('messages.customer_verify')]);
	}

	public function uniqueEmail(Request $request)
	{
		$email = $request->input('email');
		$userId = $request->input('user_id');

		$isUnique = User::where('email', $email)
						->where(function ($query) use ($userId) {
							if ($userId) {
								$query->where('id', '!=', $userId);
							}
						})
						->doesntExist();

		return response()->json(['isUnique' => $isUnique]);
	}
}
