<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

class LoginController extends Controller {
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        // Check both deleted_at is null AND status is not 0 (if status field exists)
        // The status check is added to prevent users with status=0 from logging in
        $credentials = $request->only($this->username(), 'password');
        $credentials['deleted_at'] = null;
        
        // We'll check if the status column exists in the users table
        // If it does, we'll add it to the credentials
        if (Schema::hasColumn('users', 'status')) {
            $credentials['status'] = 1;
        }
        
        return $credentials;
    }

    /*Extended Function from AuthenticatesUsers*/
    protected function sendFailedLoginResponse(Request $request) {
        $user = User::where('email', $request->get('email'))->withTrashed()->first();
        
        // Check if user is inactive either by being soft-deleted or having status=0
        if (!empty($user->deleted_at) || (isset($user->status) && (int)$user->status === 0)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(403, 'Your account has been disabled. Please contact support.');
            }
            
            throw ValidationException::withMessages([
                $this->username() => [trans('auth.user_inactive')],
            ])->status(403); // Set the status code to 403 Forbidden
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }
}
