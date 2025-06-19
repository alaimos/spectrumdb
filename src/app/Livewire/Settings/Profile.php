<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public ?string $language = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->language = Auth::user()->language;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        $validLocales = array_keys(SetLocale::AVAILABLE_LOCALES);

        $validated = $this->validate(
            [
                'name' => ['required', 'string', 'max:255'],

                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    'email',
                    'max:255',
                    Rule::unique(User::class)->ignore($user->id),
                ],

                'language' => [
                    'nullable',
                    Rule::in($validLocales),
                ],
            ]
        );

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $reload = $user->isDirty('language');

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
        if ($reload) {
            $this->redirectRoute('settings.profile', ['refresh' => now()->timestamp], navigate: true);
        }
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}
