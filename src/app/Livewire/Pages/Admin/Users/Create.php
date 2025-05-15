<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Users;

use App\Enums\Role;
use App\Models\User;
use Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

final class Create extends Component
{
    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('required|email|unique:users,email')]
    public string $email = '';

    #[Rule('required|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public ?string $role = null;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'in:'.implode(',', array_column(Role::cases(), 'value'))],
        ];
    }

    public function save(): void
    {
        $this->authorize('create', User::class);

        $validated = $this->validate();

        $validated['password'] = Hash::make($validated['password']);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
        ]);

        $this->redirect(route('admin.users.index'), navigate: true);

        Flux::toast(
            text: 'User created successfully',
            variant: 'success'
        );
    }

    #[Title('Create User')]
    public function render(): View
    {
        return view('livewire.pages.admin.users.create');
    }
}
