<?php

namespace App\Http\Middleware;

use App\Providers\AuthServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;

class Check2FA
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Session::has('force2fa') && Session::get('force2fa') == true) {
            return redirect()->route('2fa.index');
        }

        $user = Auth::user();
        if ($user && AuthServiceProvider::shouldRequireEmail2FA($user)) {
            $currentDeviceSignature = AuthServiceProvider::generate2FaDeviceSignature();
            $verifiedDevices = AuthServiceProvider::getUserDevices($user->id);

            if (!in_array($currentDeviceSignature, $verifiedDevices)) {
                $codeSent = AuthServiceProvider::generate2FACode();
                AuthServiceProvider::addNewUserDevice($user->id);
                Session::put('force2fa', true);

                if (!$codeSent) {
                    Session::flash('error', __('We could not send your 2FA code. Please check your SMTP settings and try resending the code.'));
                }

                return redirect()->route('2fa.index');
            }
        }

        return $next($request);
    }
}
