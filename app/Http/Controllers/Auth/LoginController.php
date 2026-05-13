<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\AuthServiceProvider;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
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
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->redirectTo = route('feed');
        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(Request $request, $user)
    {
        // Lida com o 2FA
        $force2FA = false;
        if (AuthServiceProvider::shouldRequireEmail2FA($user) && !in_array(AuthServiceProvider::generate2FaDeviceSignature(), AuthServiceProvider::getUserDevices($user->id))) {
            $codeSent = AuthServiceProvider::generate2FACode();
            AuthServiceProvider::addNewUserDevice($user->id);
            $force2FA = true;

            if (!$codeSent) {
                Session::flash('error', __('We could not send your 2FA code. Please check your SMTP settings and try resending the code.'));
            }
        }
        Session::put('force2fa', $force2FA);
    
        // Obtém a URL de intenção ou a URL anterior
        $intendedUrl = session()->pull('url.intended', url()->previous());
    
        if ($intendedUrl && preg_match('/\/[^\/]+\/checkout(\/[A-Z0-9-]+)?$/', $intendedUrl)) {
            $redirect = $intendedUrl;
        } else {
            $redirect = route('home');
        }
    
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Logged in successfully.', 'redirect' => $redirect]);
        }
    
        return redirect()->to($redirect);
    }
    


    /**
     * Redirect the user to the Facebook authentication page.
     */
    public function redirectToProvider(Request $request)
    {
        return Socialite::driver($request->route('provider'))->redirect();
    }

    /**
     * Obtain the user information from Facebook.
     */
    public function handleProviderCallback(Request $request)
    {
        $provider = $request->route('provider');

        try {
            $user = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            throw new \ErrorException($e->getMessage());
        }

        // Creating the user & Logging in the user
        $userCheck = User::where('auth_provider_id', $user->id)->first();
        if($userCheck){
            $authUser = $userCheck;
        }
        else{
            try {
                $authUser = AuthServiceProvider::createUser([
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'auth_provider' => $provider,
                    'auth_provider_id' => $user->id
                ]);
            }
            catch (\Exception $exception) {
                // Redirect to homepage with error
                return redirect(route('home'))->with('error', $exception->getMessage());
            }

        }

        Auth::login($authUser, true);
        $redirectTo = route('feed');
        if (Session::has('lastProfileUrl')) {
            $redirectTo = Session::get('lastProfileUrl');
        }
        return redirect($redirectTo);

    }

}
