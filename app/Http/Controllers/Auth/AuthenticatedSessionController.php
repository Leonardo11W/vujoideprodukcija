<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Trait\AuthTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    use AuthTrait;

    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $isLogin = $this->loginTrait($request);
        if ($isLogin) {
            $authUser = auth()->user();

            if (
                $authUser->hasRole('user') ||
                (! $authUser->hasAnyRole(['manager', 'admin', 'employee']))
            ) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Access denied. You don\'t have the required permissions.',
                ])->onlyInput('email');
            }

            // Ensure employee has default permissions on login
            if ($authUser->hasRole('employee')) {
                \App\Helpers\AuthHelper::ensureEmployeeDefaultPermissions($authUser);
            }

            // Clear permission cache to ensure fresh permissions on login
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            // Refresh user model to get latest permissions
            $authUser->refresh();
            $authUser->load('roles', 'permissions');

            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Destroy an authenticated session.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
