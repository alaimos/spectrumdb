<?php

namespace App\Livewire\Components;

use App\Enums\DatasetPermission;
use App\Models\Dataset;
use App\Models\User;
use Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DatasetPermissions extends Component
{
    public Dataset $dataset;

    public ?string $selectedUserId = null;

    public ?string $selectedPermission = null;

    public bool $showAddForm = false;

    public function mount(Dataset $dataset)
    {
        $this->dataset = $dataset;
        $this->selectedPermission = DatasetPermission::READ->value;
        $this->showAddForm = false;
    }

    public function toggleAddForm(): void
    {
        $this->showAddForm = ! $this->showAddForm;
    }

    #[Computed]
    public function availableUsers(): Collection
    {
        return User::query()
            ->where('id', '!=', $this->dataset->created_by)
            ->whereNotIn('id', $this->dataset->users->pluck('id'))
            ->get();
    }

    #[Computed]
    public function currentUsers(): Collection
    {
        return $this->dataset->users()
            ->select('users.*', 'dataset_user_permissions.permission')
            ->get()
            ->map(function ($user) {
                $user->pivot = (object) [
                    'permission' => $user->permission,
                ];
                unset($user->permission);

                return $user;
            });
    }

    public function grantAccess(): void
    {
        if (! $this->selectedUserId || ! $this->selectedPermission) {
            return;
        }

        $user = User::find($this->selectedUserId);
        $permission = DatasetPermission::from($this->selectedPermission);

        $this->dataset->grantPermission($user, $permission);

        $this->selectedUserId = null;
        Flux::toast('Permission granted successfully', 'success');
    }

    public function revokeAccess(int $userId): void
    {
        $user = User::find($userId);
        $this->dataset->revokeAllPermissions($user);
        Flux::toast('Permission revoked successfully', 'success');
    }

    public function render()
    {
        return view('livewire.components.dataset-permissions');
    }
}
