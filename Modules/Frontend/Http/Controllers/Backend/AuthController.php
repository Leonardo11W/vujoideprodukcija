<?php

namespace Modules\Frontend\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Auth\Trait\AuthTrait;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Modules\Frontend\Models\UserBranch;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{

    public function Signup(Request $request)
    {

        return view('frontend::auth.register');
    }

    public function SignupUser(Request $request)
    {
        try {
            $request->validate([
                'first_name' => ['required', 'string', 'max:191'],
                'last_name' => ['required', 'string', 'max:191'],
                'email' => ['required', 'string', 'email', 'max:191', 'unique:users'],
                'password' => ['required', 'min:8', 'max:14'],
                'confirm_password' => ['required', 'same:password'],
                'mobile' => ['required', 'string', 'unique:users,mobile'],
                'gender' => ['required'],
                'country_code' => ['nullable', 'string', 'max:2'],
            ], [
                'mobile.unique' => 'This contact number is already registered. Please use a different number.',
                'email.unique' => 'The email has already been taken.',
            ]);
    
            $arr = [
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'name'       => $request->first_name . ' ' . $request->last_name,
                'email'      => $request->email,
                'mobile'     => $request->mobile,
                'gender'     => $request->gender,
                'country_code' => $request->country_code,
                'password'   => Hash::make($request->password),
                'user_type'  => 'user',
                'status'     => 1,
                'email_verified_at' => now(),
            ];
    
            $user = User::create($arr);
    
            // Assign role
            $user->syncRoles('user');

            Artisan::call('cache:clear');
    
    
            // Login user directly
            $isLogin = Auth::login($user);
            $request->session()->regenerate();

      if(Auth::check()){
        return response()->json([
            'success' => true,
            'message' => 'Account created successfully!',
            'redirect_url' => url('/'), // redirect to homepage
        ]);
      }
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['error' => $e->getMessage()]
            ], 422);
        }
    }



    public function Login(Request $request)
    {
        // Check for active branches
        $activeBranches = \App\Models\Branch::where('status', 1)->count();

        // if ($activeBranches === 0) {
        //     // No active branches, redirect to home
        //     return redirect()->intended('/');
        // }

        // If active branches exist, fetch all branches and show login
        $branches = \App\Models\Branch::where('status', 1)->get();
        return view('frontend::auth.login', compact('branches'));
    }


    public function ForgotPassword()
    {
        return view('frontend::auth.forgotpassword');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = Password::sendResetLink(
            $request->only('email')
        );
        $user = User::where('email', $request->email)->first();

        if ($user == null) {
            return redirect()->back()->with('status', [
                'message' => __($response),
                'status' => $response == Password::RESET_LINK_SENT
            ]);
        }

        return redirect()->back()->with('status', [
            'message' => __($response),
            'status' => $response == Password::RESET_LINK_SENT
        ]);
    }

    public function resetPassword($token)
    {
        return view('frontend::auth.resetpassword', [
            'token' => $token,
            'email' => request('email')
        ]);
    }

    public function updateResetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function NewPassword(Request $request)
    {

        return view('frontend::auth.newpassword');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|max:14|different:old_password',
            'confirm_password' => 'required|same:new_password'
        ], [
            'new_password.min' => 'Password length should be 8 to 14 Characters Long',
            'new_password.max' => 'Password length should be 8 to 14 Characters Long',
            'new_password.different' => 'The new password must be different from your old password.'
        ]);

        $user = auth()->user();

        // Check if old password matches
        if (!\Hash::check($request->old_password, $user->password)) {
            return back()->withErrors(['old_password' => 'The old password is incorrect']);
        }

        // Update password
        $user->password = \Hash::make($request->new_password);
        $user->save();

        return redirect()->back()->with('success', 'Password updated successfully');
    }

    public function logout(Request $request)
    {

        $selectedBranch = $request->session()->get('selected_branch');

        UserBranch::updateOrCreate(
            ['user_id' => auth()->id()],
            ['branch_id' => $selectedBranch]
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('index')->with('success', 'You have been successfully logged out.');
    }
    public function loginUser(Request $request)
    {
        try {
            $request->validate([
                'email' => 'nullable|email',
                'password' => 'nullable',
            ]);

            $isAjax = $request->expectsJson() || $request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json';

            $remember = $request->has('remember_me') || $request->has('remember');
            if (Auth::attempt($request->only('email', 'password'), $remember)) {

                // Frontend access control:
                // - Normal customers ('user' role) go to frontend home
                // - Staff (employee / manager / admin) are redirected to backend staff/app area
                if (auth()->user()->hasRole('employee') || auth()->user()->hasRole('manager') || auth()->user()->hasRole('admin')) {
                    $request->session()->regenerate();

                    $redirectUrl = url(RouteServiceProvider::EMPLOYEE_LOGIN_REDIRECT);

                    if ($isAjax) {
                        return response()->json([
                            'success'         => true,
                            'redirect_url'    => $redirectUrl,
                            'user_branch_id'  => $user_branch_id ?? null,
                        ]);
                    }

                    return redirect($redirectUrl);
                }

                // Only allow users with the 'user' role to remain on the frontend
                if (!auth()->user()->hasRole('user')) {
                    Log::warning('Frontend login blocked due to unauthorized role.', [
                        'email' => $request->input('email'),
                        'user_id' => auth()->id(),
                        'roles' => auth()->user()->getRoleNames(),
                    ]);

                    Auth::logout();
                    if ($isAjax) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Unauthorized role. You are not allowed to log in from the frontend.',
                        ], 403);
                    }
                    return back()->withErrors([
                        'email' => 'Unauthorized role. You are not allowed to log in from the frontend.',
                    ])->onlyInput('email');
                }
                $request->session()->regenerate();

                $userBranch = UserBranch::where('user_id', auth()->id())->first();
                $user_branch_id = $userBranch ? $userBranch->branch_id : null;


                // Store selected branch in session if provided
                if ($request->filled('branch_id') || $user_branch_id) {

                    $branchId = $request->input('branch_id') ?? $user_branch_id;

                    Session::put('selected_branch_id', $branchId);
                    Session::put('selected_branch', $branchId);
                }


                // Use intended URL if present (from modal)
                $intended = $request->input('intended');
                if ($intended) {
                    Log::info('Frontend login succeeded with intended redirect.', [
                        'user_id' => auth()->id(),
                        'email' => $request->input('email'),
                        'redirect' => $intended,
                    ]);
                    if ($isAjax) {
                        return response()->json([
                            'success' => true,
                            'redirect_url' => $intended,
                            'user_branch_id' => $user_branch_id ?? null
                        ]);
                    }
                    return redirect($intended);
                }

                Log::info('Frontend login succeeded.', [
                    'user_id' => auth()->id(),
                    'email' => $request->input('email'),
                    'user_branch_id' => $user_branch_id ?? null,
                ]);

                // fallback
                if ($isAjax) {
                    return response()->json([
                        'success' => true,
                        'message' => 'You have been successfully logged in.',
                        'redirect_url' => url('/'),
                        'user_branch_id' => $user_branch_id ?? null,
                    ]);
                }
                return redirect('/')->with('success', 'You have been successfully logged in.');
            }

            Log::warning('Frontend login failed due to invalid credentials.', [
                'email' => $request->input('email'),
                'is_ajax' => $isAjax,
            ]);

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials. Please check your email and password.',
                ], 401);
            }

            return back()->withErrors([
                'email' => 'Invalid credentials. Please check your email and password.',
            ])->onlyInput('email');
        } catch (\Throwable $th) {
            Log::error('Frontend login exception.', [
                'email' => $request->input('email'),
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            $isAjax = $request->expectsJson() || $request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json';

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong while logging in. Please try again.',
                ], 500);
            }

            return back()->withErrors([
                'email' => 'Something went wrong while logging in. Please try again.',
            ])->onlyInput('email');
        }
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser || !$googleUser->getEmail()) {
                return redirect('/login')->with('error', 'Unable to get user information from Google. Please try again.');
            }

            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $fullName = $googleUser->getName();
                $nameParts = explode(' ', $fullName);

                $firstName = isset($nameParts[0]) ? $nameParts[0] : '';
                $lastName = isset($nameParts[1]) ? $nameParts[1] : $firstName;

                $data = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => $firstName . ' ' . $lastName,
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(Str::random(8)),
                    'user_type' => 'user',
                    'login_type' => 'google',
                    'status' => 1,
                ];

                $user = User::create($data);
                $user->assignRole('user');
                $user->save();

                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                // Artisan::call('view:clear');
                // Artisan::call('config:cache');
                // Artisan::call('route:clear');

                // $user = $user->fresh();
            }
            // dd($user,$user->hasRole('user'),$user->getRoleNames());
            if (!$user->hasRole('user')) {
                return redirect('/login')->with('error', 'Unauthorized role. You are not allowed to log in from the frontend.');
            }

            Auth::login($user);
            $request->session()->regenerate();

            $branchId = $request->get('branch_id') ?? $request->input('branch_id');
            if ($branchId) {
                $request->session()->put('selected_branch', $branchId);
            }

            return redirect('/');
        } catch (\Exception $e) {

            return redirect('/login')->with('error', 'Something went wrong during Google authentication. Please try again.');
        }
    }

    /**
     * Check if contact number already exists
     */
    public function checkContactNumber(Request $request)
    {
        try {
            $request->validate([
                'contact_number' => 'required|string|min:8|max:20'
            ]);

            $contactNumber = $request->contact_number;

            // Check if contact number exists in users table
            $exists = User::where('mobile', $contactNumber)->exists();

            return response()->json([
                'exists' => $exists,
                'message' => $exists ? 'Contact number already exists' : 'Contact number is available'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'exists' => false,
                'message' => 'Error checking contact number',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if email already exists
     */
    public function checkEmailAvailability(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $email = $request->email;

            // Check if email exists in users table
            $exists = User::where('email', $email)->exists();

            return response()->json([
                'exists' => $exists,
                'message' => $exists ? 'Email already exists' : 'Email is available'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'exists' => false,
                'message' => 'Error checking email availability',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
