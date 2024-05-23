<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Features;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 退出登录自定义跳转
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse{
            public function toResponse($request): RedirectResponse
            {
                return redirect('/login');
            }
        });

        // 登录成功后自定义跳转
        $this->app->instance(LoginResponse::class, new class implements LoginResponse{
            public function toResponse($request): RedirectResponse
            {
                return redirect('/');
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(function () {
            return view('auth.login');
        });
        Fortify::registerView(function () {
            return view('auth.register');
        });
        Fortify::verifyEmailView(function () {
            return view('auth.verify'); 
        });
        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.passwords.email');
        });
        Fortify::resetPasswordView(function ($request) {
            return view('auth.passwords.reset', ['request' => $request]);
        });
        Fortify::confirmPasswordView(function () {
            return view('auth.password-confirm'); 
        });
        Fortify::twoFactorChallengeView(function () {
            return view('auth.two-factor-challenge'); 
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // 自定义用户认证
        // Fortify::authenticateUsing(function(Request $request){
        //     $user = User::where('email', $request->email)->first();
        //     if($user && Hash::check($request->password, $user->password)){
        //         return $user;
        //     }
        // });
        // 自定义用户认证2
        Fortify::authenticateUsing(function(Request $request){
            // $credentials = $request->only('email','password');
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);
            if(Auth::attempt($credentials)){
                $user = Auth::user();
                return $user;
            }
        });
        
        // 自定义身份验证管道
        // Fortify::authenticateThrough(function (Request $request) {
        //     return array_filter([
        //             config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
        //             Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class : null,
        //             AttemptToAuthenticate::class,
        //             PrepareAuthenticatedSession::class,
        //     ]);
        // });
    }
}
