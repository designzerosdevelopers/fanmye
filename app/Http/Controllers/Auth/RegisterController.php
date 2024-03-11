<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\AuthServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Rules\IsEmailDelivrable;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Model\Link; 
use App\Model\Visitor; 

use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
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
        $redirectRoute = route('feed');
        if(getSetting('site.redirect_page_after_register') && getSetting('site.redirect_page_after_register') == 'settings'){
            $redirectRoute = route('my.settings');
        }
        $this->redirectTo = $redirectRoute;
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {

        $additionalRules = [];
        if(getSetting('security.recaptcha_enabled')){
            $additionalRules = [
                'g-recaptcha-response' => 'required|captcha'
            ];
        }

        $emailValidationRule = ['required', 'string', 'email', 'max:255', 'unique:users'];
        if(getSetting('security.enforce_email_valid_check') && getSetting('security.email_abstract_api_key')){
            $emailValidationRule = ['required', 'string', 'email', 'max:255', 'unique:users', new IsEmailDelivrable];
        }

        // If abstract api enabled, check if email is delivrable
        return Validator::make($data, array_merge([
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailValidationRule,
            'password' => ['min:6', 'required', 'string', 'confirmed'],
            'password_confirmation' => ['required', 'min:6'],
            'terms' => ['required'],
            ],$additionalRules)
        );
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        return AuthServiceProvider::createUser($data);
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        $visitor = Visitor::where('ip_address', $request->ip())->first();

        if ($visitor) {
            $lastUpdated = Carbon::parse($visitor->updated_at);
            $currentTime = Carbon::now();
            $differenceInMinutes = $lastUpdated->diffInMinutes($currentTime);

                if ($lastUpdated->diffInHours($currentTime) <= 24) {
                    Link::where('id', $visitor->link_id)->increment('sign_up');
                }

        }
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Register successful.']);
        }
    }
}
