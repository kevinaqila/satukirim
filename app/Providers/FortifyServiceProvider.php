<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, fn () => new class($this) implements LoginResponseContract
        {
            public function __construct(private FortifyServiceProvider $provider) {}

            public function toResponse($request): Response
            {
                $this->provider->clearDisallowedIntendedUrl($request);

                return $request->wantsJson()
                    ? response()->json(['two_factor' => false])
                    : redirect()->intended(Fortify::redirects('login'));
            }
        });

        $this->app->singleton(TwoFactorLoginResponseContract::class, fn () => new class($this) implements TwoFactorLoginResponseContract
        {
            public function __construct(private FortifyServiceProvider $provider) {}

            public function toResponse($request): Response
            {
                $this->provider->clearDisallowedIntendedUrl($request);

                return $request->wantsJson()
                    ? response()->noContent()
                    : redirect()->intended(Fortify::redirects('login'));
            }
        });
    }

    /**
     * Remove disallowed intended redirect URLs (admin, foreign shipment, or foreign store routes) after sign in.
     */
    public function clearDisallowedIntendedUrl(Request $request): void
    {
        $intended = $request->session()->get('url.intended');
        if (! is_string($intended) || $intended === '') {
            return;
        }

        $path = parse_url($intended, PHP_URL_PATH) ?: '';
        $user = $request->user();

        if (! $user?->isSuperAdmin() && ($path === '/admin' || str_starts_with($path, '/admin/'))) {
            $request->session()->forget('url.intended');

            return;
        }

        if (preg_match('#^/shipments/(\d+)(?:/.*)?$#', $path, $matches)) {
            $shipment = Shipment::find((int) $matches[1]);

            if (! $shipment || (! $user?->isSuperAdmin() && $shipment->user_id !== $user?->id)) {
                $request->session()->forget('url.intended');
            }

            return;
        }

        if (preg_match('#^/stores/(\d+)(?:/.*)?$#', $path, $matches)) {
            $store = Store::find((int) $matches[1]);

            if (! $store || $store->user_id !== $user?->id) {
                $request->session()->forget('url.intended');
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if ($user && filled($user->password) && Hash::check($request->password, $user->password)) {
                if (! $user->is_active) {
                    throw ValidationException::withMessages([
                        Fortify::username() => ['Akun Anda sedang dinonaktifkan oleh Administrator.'],
                    ]);
                }

                return $user;
            }

            return null;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
