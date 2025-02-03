<?php

namespace App\Livewire\Pages\Admin\Users;

use App\Enums\Role;
use App\Models\User;
use Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Rule as ValidationRule;
use Livewire\Attributes\Title;
use Livewire\Component;

class Edit extends Component
{
    public User $user;

    #[ValidationRule('required|string|max:255')]
    public string $name = '';

    #[ValidationRule('required|email')]
    public string $email = '';

    #[ValidationRule('nullable|min:8|confirmed')]
    public ?string $password = null;

    public ?string $password_confirmation = null;

    public ?string $role = null;

    public function mount(User $user): void
    {
        $this->authorize('update', $user);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'in:'.implode(',', array_column(Role::cases(), 'value'))],
        ];
    }

    public function save(): void
    {
        $this->authorize('update', $this->user);

        $validated = $this->validate();

        $this->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        if ($validated['password']) {
            $this->user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        $this->redirect(route('admin.users.index'), navigate: true);

        Flux::toast(
            text: 'User updated successfully',
            variant: 'success'
        );
    }

    #[Title('Edit User')]
    public function render(): View
    {
        return view('livewire.pages.admin.users.edit');
    }
}
