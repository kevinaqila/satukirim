<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the guest user to Google's OAuth consent screen.
     */
    public function redirect(): Response
    {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            return redirect()->route('login')->withErrors([
                'google' => 'Login dengan Google belum dikonfigurasi pada server.',
            ]);
        }

        return $this->googleDriver()->redirect();
    }

    /**
     * Handle the OAuth callback returned by Google.
     */
    public function callback(Request $request, LoginResponseContract $loginResponse): Response
    {
        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors([
                'google' => 'Proses masuk dengan Google dibatalkan.',
            ]);
        }

        try {
            $googleUser = $this->googleDriver()->user();
        } catch (Throwable $e) {
            Log::warning('Google OAuth callback failed', ['error' => $e->getMessage()]);

            return redirect()->route('login')->withErrors([
                'google' => 'Gagal memverifikasi akun Google. Silakan coba kembali.',
            ]);
        }

        $googleEmail = strtolower(trim((string) $googleUser->getEmail()));
        if ($googleEmail === '') {
            return redirect()->route('login')->withErrors([
                'google' => 'Akun Google tidak menyertakan alamat email yang valid.',
            ]);
        }

        $googleId = (string) $googleUser->getId();
        $avatar = $googleUser->getAvatar();

        $user = User::query()
            ->where('google_id', $googleId)
            ->orWhere('email', $googleEmail)
            ->first();

        if ($user) {
            if (! $user->is_active) {
                return $this->inactiveRedirect();
            }

            $updates = [];
            if ($user->google_id !== $googleId) {
                $updates['google_id'] = $googleId;
            }
            if (filled($avatar) && ! str_starts_with((string) $user->avatar, '/storage/') && $user->avatar !== $avatar) {
                $updates['avatar'] = $avatar;
            }
            if ($updates !== []) {
                $user->update($updates);
            }
            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        } else {
            $name = trim((string) $googleUser->getName());
            $user = User::create([
                'name' => $name !== '' ? $name : str($googleEmail)->before('@')->toString(),
                'email' => $googleEmail,
                'google_id' => $googleId,
                'avatar' => $avatar,
                'role' => 'umkm',
                'is_active' => true,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return $loginResponse->toResponse($request);
    }

    protected function googleDriver(): Provider
    {
        $driver = Socialite::driver('google');

        if (! app()->isProduction() && method_exists($driver, 'setHttpClient')) {
            $driver->setHttpClient(new GuzzleClient(['verify' => false]));
        }

        return $driver;
    }

    protected function inactiveRedirect(): RedirectResponse
    {
        return redirect()->route('login')->withErrors([
            'google' => 'Akun Anda sedang dinonaktifkan oleh Administrator.',
        ]);
    }
}
