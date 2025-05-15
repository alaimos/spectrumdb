<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Profile;

use App\Livewire\Actions\Logout;
use App\Models\User;
use Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class Index extends Component
{
    public string $name = '';

    public string $email = '';

    // Update Password Properties
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Delete User Property
    public string $delete_password = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorize('view', $user);

        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfileInformation(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorize('update', $user);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast('Profile updated successfully.', variant: 'success');
    }

    public function sendVerification(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast('Verification link sent!', variant: 'success');
    }

    public function updatePassword(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorize('update', $user);

        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');
            throw $e;
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast('Password updated successfully.', variant: 'success');
    }

    public function deleteUser(Logout $logout): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorize('delete', $user);

        $this->validate([
            'delete_password' => ['required', 'string', 'current_password'],
        ]);

        tap($user, $logout(...))->delete();

        Flux::toast('Account deleted successfully.', variant: 'success');

        $this->redirect('/', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.profile.index');
    }
}
