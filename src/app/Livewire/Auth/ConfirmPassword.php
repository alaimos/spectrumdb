<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class ConfirmPassword extends Component
{
    #[Validate('required|string')]
    public string $password = '';

    public function confirm(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::guard('web')->validate(
            [
                'email' => auth()->user()->email,
                'password' => $this->password,
            ]
        )) {
            throw ValidationException::withMessages(
                [
                    'password' => __('auth.password'),
                ]
            );
        }

        session()->put('auth.password_confirmed_at', time());

        RateLimiter::clear($this->throttleKey());

        $this->redirectIntended(route('dashboard', absolute: false));
    }

    public function render(): View
    {
        return view('livewire.auth.confirm-password');
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages(
            [
                'password' => trans(
                    'auth.throttle',
                    [
                        'seconds' => $seconds,
                        'minutes' => ceil($seconds / 60),
                    ]
                ),
            ]
        );
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower(auth()->user()->email).'|'.request()->ip());
    }
}
