<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password');

        if (Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::clear($this->throttleKey());
            return;
        }

        // Intento de compatibilidad con contraseñas legadas (texto plano, md5, sha1)
        $user = \App\Models\User::where('email', $credentials['email'])->first();
        if ($user) {
            $plain = $credentials['password'];
            $stored = (string) $user->getAuthPassword();
            $matchesLegacy = hash_equals($stored, $plain)
                || hash_equals($stored, md5($plain))
                || hash_equals($stored, sha1($plain))
                || hash_equals($stored, hash('sha256', $plain))
                || hash_equals($stored, hash('sha512', $plain));

            if (! $matchesLegacy && str_starts_with($stored, '*') && strlen($stored) === 41) {
                $mysqlPassword = '*' . strtoupper(
                    bin2hex(sha1(sha1($plain, true), true))
                );
                $matchesLegacy = hash_equals($stored, $mysqlPassword);
            }

            if ($matchesLegacy) {
                // Rehash hacia el algoritmo actual (la declaración casts del modelo lo hace automáticamente)
                $user->password = $plain;
                $user->save();

                Auth::login($user, $this->boolean('remember'));
                RateLimiter::clear($this->throttleKey());
                return;
            }
        }

        RateLimiter::hit($this->throttleKey());

        if ($user) {
            \Log::warning('auth.failed_legacy_hash', [
                'email' => $credentials['email'],
                'hash_length' => strlen($user->getAuthPassword() ?? ''),
                'hash_starts_with' => substr($user->getAuthPassword() ?? '', 0, 10),
                'hash_info' => password_get_info((string) $user->getAuthPassword()),
            ]);
        }

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
